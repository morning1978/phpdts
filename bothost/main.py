#!/usr/bin/env python3
"""bothost: 远程托管 PHPDTS bot/revbotservice.php 的轻量守护程序。"""

from __future__ import annotations

import argparse
import json
import random
import signal
import socket
import ssl
import threading
import time
import urllib.error
import urllib.parse
import urllib.request
from dataclasses import dataclass, field
from datetime import datetime
from typing import Any, Dict, List, Optional


@dataclass
class WorkerState:
    worker_id: int
    status: str = "idle"
    last_line: str = ""
    last_seen_ts: float = 0.0
    current_game_state: Optional[int] = None
    bot_id: Optional[int] = None
    restarts: int = 0
    errors: int = 0
    last_error: str = ""


@dataclass
class TargetConfig:
    name: str
    revbotservice_url: str
    workers: int
    connect_timeout_sec: int = 10
    read_timeout_sec: int = 30
    restart_delay_sec: int = 2
    loop_interval_sec: float = 2.0
    mode: str = "oneshot"  # oneshot / stream
    headers: Dict[str, str] = field(default_factory=dict)
    query: Dict[str, str] = field(default_factory=dict)
    disable_env_proxy: bool = True
    insecure_skip_tls_verify: bool = False


class TargetRuntime:
    def __init__(self, config: TargetConfig):
        self.config = config
        self.states: Dict[int, WorkerState] = {
            i: WorkerState(worker_id=i) for i in range(1, config.workers + 1)
        }
        self.lock = threading.Lock()
        self.threads: List[threading.Thread] = []


class BotHost:
    def __init__(self, targets: List[TargetConfig], report_interval_sec: int = 15):
        self.targets = [TargetRuntime(t) for t in targets]
        self.stop_event = threading.Event()
        self.report_interval_sec = report_interval_sec
        self.report_thread: Optional[threading.Thread] = None

    def run(self) -> None:
        for target in self.targets:
            for worker_id in range(1, target.config.workers + 1):
                t = threading.Thread(
                    target=self._worker_loop,
                    args=(target, worker_id),
                    name=f"{target.config.name}-w{worker_id}",
                    daemon=True,
                )
                target.threads.append(t)
                t.start()

        self.report_thread = threading.Thread(target=self._report_loop, name="reporter", daemon=True)
        self.report_thread.start()

        while not self.stop_event.is_set():
            time.sleep(0.5)

    def shutdown(self) -> None:
        self.stop_event.set()
        for target in self.targets:
            for t in target.threads:
                t.join(timeout=1.0)
        if self.report_thread:
            self.report_thread.join(timeout=1.0)

    def _worker_loop(self, target: TargetRuntime, worker_id: int) -> None:
        cfg = target.config
        jitter = random.uniform(0, max(0.2, cfg.loop_interval_sec * 0.2))
        self.stop_event.wait(jitter)

        while not self.stop_event.is_set():
            self._set_state(target, worker_id, status="connecting")
            try:
                if cfg.mode == "oneshot":
                    self._oneshot_worker(target, worker_id)
                    self._set_state(target, worker_id, status="idle")
                    if self.stop_event.wait(cfg.loop_interval_sec):
                        break
                    continue
                self._stream_worker(target, worker_id)
            except urllib.error.HTTPError as exc:
                self._record_http_error(target, worker_id, exc)
            except urllib.error.URLError as exc:
                self._record_error(target, worker_id, f"URLError: {exc.reason}")
            except Exception as exc:  # noqa: BLE001
                self._record_error(target, worker_id, f"{type(exc).__name__}: {exc}")

            self._increment_restart(target, worker_id)
            self._set_state(target, worker_id, status="restarting")
            if self.stop_event.wait(cfg.restart_delay_sec):
                break

        self._set_state(target, worker_id, status="stopped")

    def _build_opener(self, cfg: TargetConfig) -> urllib.request.OpenerDirector:
        handlers: List[Any] = []
        if cfg.disable_env_proxy:
            handlers.append(urllib.request.ProxyHandler({}))
        if cfg.insecure_skip_tls_verify:
            ctx = ssl.create_default_context()
            ctx.check_hostname = False
            ctx.verify_mode = ssl.CERT_NONE
            handlers.append(urllib.request.HTTPSHandler(context=ctx))
        return urllib.request.build_opener(*handlers) if handlers else urllib.request.build_opener()

    def _build_url(self, cfg: TargetConfig) -> str:
        q = dict(cfg.query)
        if cfg.mode == "oneshot":
            q.setdefault("oneshot", "1")
        full_url = cfg.revbotservice_url
        if q:
            full_url += "?" + urllib.parse.urlencode(q)
        return full_url

    def _oneshot_worker(self, target: TargetRuntime, worker_id: int) -> None:
        cfg = target.config
        request = urllib.request.Request(self._build_url(cfg), headers=cfg.headers, method="GET")
        opener = self._build_opener(cfg)
        with opener.open(request, timeout=cfg.connect_timeout_sec) as response:
            self._set_state(target, worker_id, status="connected")
            raw = response.read().decode("utf-8", errors="ignore")
        any_line = False
        for line in raw.splitlines():
            text = line.strip()
            if not text:
                continue
            any_line = True
            self._consume_line(target, worker_id, text)
        if not any_line:
            self._set_state(target, worker_id, status="empty_response")

    def _stream_worker(self, target: TargetRuntime, worker_id: int) -> None:
        cfg = target.config
        request = urllib.request.Request(self._build_url(cfg), headers=cfg.headers, method="GET")
        opener = self._build_opener(cfg)

        with opener.open(request, timeout=cfg.connect_timeout_sec) as response:
            self._set_state(target, worker_id, status="connected")
            if hasattr(response, "fp") and hasattr(response.fp, "raw"):
                raw = response.fp.raw
                if hasattr(raw, "_sock") and raw._sock:
                    raw._sock.settimeout(cfg.read_timeout_sec)

            while not self.stop_event.is_set():
                try:
                    line = response.readline()
                except socket.timeout:
                    self._set_state(target, worker_id, status="read_timeout")
                    break

                if not line:
                    self._set_state(target, worker_id, status="disconnected")
                    break

                text = line.decode("utf-8", errors="ignore").strip()
                if text:
                    self._consume_line(target, worker_id, text)

    def _consume_line(self, target: TargetRuntime, worker_id: int, line: str) -> None:
        now = time.time()
        state = target.states[worker_id]
        with target.lock:
            state.last_line = line
            state.last_seen_ts = now
            if "当前游戏状态:" in line:
                try:
                    gs = int(line.split("当前游戏状态:", 1)[1])
                    state.current_game_state = gs
                except ValueError:
                    pass
            if "BOT初始化完成，id：" in line:
                try:
                    bot_id = int(line.split("BOT初始化完成，id：", 1)[1].split()[0])
                    state.bot_id = bot_id
                    state.status = "bot_spawned"
                except ValueError:
                    pass
            if "行动完成" in line:
                state.status = "running"
            if "等待中" in line:
                state.status = "waiting_lock"
            if "已死亡；已加入重生队列" in line:
                state.status = "bot_dead_queued"
                state.bot_id = None
            if "已死亡；不加入重生队列" in line:
                state.status = "bot_dead_retired"
                state.bot_id = None
            if "不在活动队列，进程退出" in line:
                state.status = "bot_retired"
                state.bot_id = None
            low = line.lower()
            if "fatal error" in low or "warning:" in low or "uncaught error" in low:
                state.last_error = line
                state.status = "remote_php_error"

    def _set_state(self, target: TargetRuntime, worker_id: int, status: str) -> None:
        with target.lock:
            target.states[worker_id].status = status
            target.states[worker_id].last_seen_ts = time.time()

    def _record_error(self, target: TargetRuntime, worker_id: int, err: str) -> None:
        with target.lock:
            state = target.states[worker_id]
            state.errors += 1
            state.last_line = err
            state.last_error = err
            state.last_seen_ts = time.time()
            state.status = "error"

    def _record_http_error(self, target: TargetRuntime, worker_id: int, err: urllib.error.HTTPError) -> None:
        body = ""
        try:
            body = err.read(300).decode("utf-8", errors="ignore").strip()
        except Exception:  # noqa: BLE001
            body = ""
        detail = f"HTTPError {err.code}: {err.reason}"
        if body:
            detail += f" | body={body}"
        self._record_error(target, worker_id, detail)

    def _increment_restart(self, target: TargetRuntime, worker_id: int) -> None:
        with target.lock:
            target.states[worker_id].restarts += 1

    def _report_loop(self) -> None:
        while not self.stop_event.wait(self.report_interval_sec):
            self.print_report()

    def print_report(self) -> None:
        now = time.time()
        print(f"\n[{datetime.now().isoformat(timespec='seconds')}] bothost 状态汇总")
        for target in self.targets:
            with target.lock:
                rows = []
                for worker_id in sorted(target.states):
                    st = target.states[worker_id]
                    age = int(now - st.last_seen_ts) if st.last_seen_ts else -1
                    rows.append(
                        f"w{worker_id}: status={st.status}, game={st.current_game_state}, "
                        f"bot={st.bot_id}, restart={st.restarts}, err={st.errors}, age={age}s"
                    )
                print(f"- {target.config.name}: {', '.join(rows)}")
                for worker_id in sorted(target.states):
                    st = target.states[worker_id]
                    if st.last_error:
                        print(f"  - w{worker_id} last_error: {st.last_error}")


def load_config(path: str) -> Dict[str, Any]:
    with open(path, "r", encoding="utf-8") as f:
        return json.load(f)


def parse_targets(config: Dict[str, Any]) -> List[TargetConfig]:
    targets: List[TargetConfig] = []
    for raw in config.get("targets", []):
        targets.append(
            TargetConfig(
                name=raw["name"],
                revbotservice_url=raw["revbotservice_url"],
                workers=int(raw.get("workers", 1)),
                connect_timeout_sec=int(raw.get("connect_timeout_sec", 10)),
                read_timeout_sec=int(raw.get("read_timeout_sec", 30)),
                restart_delay_sec=int(raw.get("restart_delay_sec", 2)),
                loop_interval_sec=float(raw.get("loop_interval_sec", 2.0)),
                mode=str(raw.get("mode", "oneshot")).strip().lower() or "oneshot",
                headers=dict(raw.get("headers", {})),
                query=dict(raw.get("query", {})),
                disable_env_proxy=bool(raw.get("disable_env_proxy", True)),
                insecure_skip_tls_verify=bool(raw.get("insecure_skip_tls_verify", False)),
            )
        )
    return targets


def build_parser() -> argparse.ArgumentParser:
    p = argparse.ArgumentParser(description="Remote bot host for PHPDTS revbotservice")
    p.add_argument("-c", "--config", default="bothost/config.json", help="配置文件路径")
    return p


def main() -> int:
    parser = build_parser()
    args = parser.parse_args()
    cfg = load_config(args.config)
    targets = parse_targets(cfg)
    if not targets:
        raise SystemExit("配置中没有 targets")

    host = BotHost(targets, report_interval_sec=int(cfg.get("report_interval_sec", 15)))

    def _signal_handler(signum: int, _frame: Any) -> None:  # noqa: ANN401
        print(f"接收到信号 {signum}，准备退出...")
        host.shutdown()

    signal.signal(signal.SIGINT, _signal_handler)
    signal.signal(signal.SIGTERM, _signal_handler)

    try:
        host.run()
    except KeyboardInterrupt:
        host.shutdown()

    return 0


if __name__ == "__main__":
    raise SystemExit(main())
