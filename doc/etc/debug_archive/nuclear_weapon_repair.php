<?php
/**
 * 核子武器数据修复工具
 * 
 * 用于修复因数据库字符转义问题导致的核子武器itmpara/weppara数据损坏
 * 
 * 使用方法：
 * 1. 将此文件放在admin目录下
 * 2. 通过浏览器访问此文件
 * 3. 选择要修复的操作
 */

// 错误报告设置
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 安全检查
if (!defined('GAME_ROOT')) {
    define('GAME_ROOT', dirname(__DIR__) . '/');
}

// 检查文件是否存在
if (!file_exists(GAME_ROOT . 'include/common.inc.php')) {
    die('错误：找不到 include/common.inc.php 文件。请确保此文件位于正确的目录中。');
}

try {
    require_once GAME_ROOT . 'include/common.inc.php';
    require_once GAME_ROOT . 'include/global.func.php';
} catch (Exception $e) {
    die('错误：加载文件失败 - ' . $e->getMessage());
}

// 简化的权限检查 - 在生产环境中应启用
// if (!isset($_SESSION['admin']) || $_SESSION['admin'] !== true) {
//     die('需要管理员权限才能访问此工具');
// }

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>核子武器数据修复工具</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .section { margin: 20px 0; padding: 15px; border: 1px solid #ddd; border-radius: 5px; }
        .button { padding: 10px 20px; margin: 5px; background: #007cba; color: white; border: none; border-radius: 3px; cursor: pointer; }
        .button:hover { background: #005a87; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; border-radius: 3px; margin: 10px 0; }
        .success { background: #d4edda; border: 1px solid #c3e6cb; padding: 10px; border-radius: 3px; margin: 10px 0; }
        .error { background: #f8d7da; border: 1px solid #f5c6cb; padding: 10px; border-radius: 3px; margin: 10px 0; }
        .log { background: #f8f9fa; border: 1px solid #dee2e6; padding: 10px; border-radius: 3px; font-family: monospace; white-space: pre-wrap; max-height: 400px; overflow-y: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>核子武器数据修复工具</h1>
        
        <div class="warning">
            <strong>警告：</strong>此工具会直接修改数据库数据，请在使用前备份数据库！
        </div>

        <div class="warning">
            <strong>注意：</strong>当前版本跳过了管理员权限检查以便调试。在生产环境中请启用权限检查。
        </div>

        <div class="section">
            <h2>问题诊断</h2>
            <p>检查数据库中是否存在损坏的核子武器数据</p>
            <button class="button" onclick="diagnose()">开始诊断</button>
            <div id="diagnose-result"></div>
        </div>

        <div class="section">
            <h2>数据修复</h2>
            <p>修复检测到的损坏数据</p>
            <button class="button" onclick="repair()">开始修复</button>
            <div id="repair-result"></div>
        </div>

        <div class="section">
            <h2>手动修复</h2>
            <p>为指定玩家的武器添加核子武器属性</p>
            <form onsubmit="manualRepair(event)">
                <label>玩家ID: <input type="number" name="pid" required></label><br><br>
                <label>武器名称: <input type="text" name="weapon_name" placeholder="留空则使用当前装备的武器"></label><br><br>
                <button type="submit" class="button">手动修复</button>
            </form>
            <div id="manual-result"></div>
        </div>
    </div>

    <script>
        function diagnose() {
            const resultDiv = document.getElementById('diagnose-result');
            resultDiv.innerHTML = '<p>正在诊断...</p>';
            
            fetch('?action=diagnose')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.innerHTML = `
                            <div class="success">
                                <h3>诊断完成</h3>
                                <div class="log">${data.log}</div>
                            </div>
                        `;
                    } else {
                        resultDiv.innerHTML = `
                            <div class="error">
                                <h3>诊断失败</h3>
                                <p>${data.error}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h3>请求失败</h3>
                            <p>${error.message}</p>
                        </div>
                    `;
                });
        }

        function repair() {
            const resultDiv = document.getElementById('repair-result');
            resultDiv.innerHTML = '<p>正在修复...</p>';
            
            fetch('?action=repair')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.innerHTML = `
                            <div class="success">
                                <h3>修复完成</h3>
                                <div class="log">${data.log}</div>
                            </div>
                        `;
                    } else {
                        resultDiv.innerHTML = `
                            <div class="error">
                                <h3>修复失败</h3>
                                <p>${data.error}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h3>请求失败</h3>
                            <p>${error.message}</p>
                        </div>
                    `;
                });
        }

        function manualRepair(event) {
            event.preventDefault();
            const resultDiv = document.getElementById('manual-result');
            const formData = new FormData(event.target);
            
            resultDiv.innerHTML = '<p>正在修复...</p>';
            
            fetch('?action=manual', {
                method: 'POST',
                body: formData
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        resultDiv.innerHTML = `
                            <div class="success">
                                <h3>修复完成</h3>
                                <div class="log">${data.log}</div>
                            </div>
                        `;
                    } else {
                        resultDiv.innerHTML = `
                            <div class="error">
                                <h3>修复失败</h3>
                                <p>${data.error}</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    resultDiv.innerHTML = `
                        <div class="error">
                            <h3>请求失败</h3>
                            <p>${error.message}</p>
                        </div>
                    `;
                });
        }
    </script>
</body>
</html>

<?php
// 处理AJAX请求
if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    
    try {
        switch ($_GET['action']) {
            case 'diagnose':
                echo json_encode(diagnoseNuclearWeapons());
                break;
            case 'repair':
                echo json_encode(repairNuclearWeapons());
                break;
            case 'manual':
                echo json_encode(manualRepairWeapon());
                break;
            default:
                echo json_encode(['success' => false, 'error' => '未知操作']);
        }
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

/**
 * 诊断核子武器数据
 */
function diagnoseNuclearWeapons() {
    global $db, $tablepre;

    $log = "开始诊断核子武器数据...\n\n";
    $issues = [];

    // 检查数据库连接
    if (!isset($db) || !is_object($db)) {
        return [
            'success' => false,
            'error' => '数据库连接不可用'
        ];
    }

    // 检查表前缀
    if (empty($tablepre)) {
        return [
            'success' => false,
            'error' => '数据库表前缀未设置'
        ];
    }

    try {
        // 查找所有可能的核子武器
        $query = "SELECT pid, name, wep, weppara FROM {$tablepre}players WHERE wep LIKE '%☢%' OR weppara LIKE '%Nuclear%' OR weppara LIKE '%1]%'";
        $result = $db->query($query);

        if (!$result) {
            return [
                'success' => false,
                'error' => '数据库查询失败：' . $db->error()
            ];
        }
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => '查询异常：' . $e->getMessage()
        ];
    }
    
    $total_checked = 0;
    $damaged_count = 0;
    
    while ($player = $db->fetch_array($result)) {
        $total_checked++;
        $log .= "检查玩家 {$player['name']} (ID: {$player['pid']}):\n";
        $log .= "  武器: {$player['wep']}\n";
        $log .= "  weppara: {$player['weppara']}\n";
        
        // 检查weppara是否损坏
        if (isDamagedNuclearWeapon($player['weppara'])) {
            $damaged_count++;
            $issues[] = $player;
            $log .= "  状态: ❌ 数据损坏\n";
        } else {
            $log .= "  状态: ✅ 数据正常\n";
        }
        $log .= "\n";
    }
    
    $log .= "诊断完成:\n";
    $log .= "总共检查: {$total_checked} 个玩家\n";
    $log .= "发现损坏: {$damaged_count} 个\n";
    
    return [
        'success' => true,
        'log' => $log,
        'issues' => $issues,
        'total_checked' => $total_checked,
        'damaged_count' => $damaged_count
    ];
}

/**
 * 检查是否为损坏的核子武器数据
 */
function isDamagedNuclearWeapon($weppara) {
    // 检查是否包含损坏的标识
    if (strpos($weppara, '1]') !== false) {
        return true;
    }
    
    // 尝试解析JSON
    $para = get_itmpara($weppara);
    if (empty($para) && !empty($weppara)) {
        return true;
    }
    
    return false;
}

/**
 * 修复核子武器数据
 */
function repairNuclearWeapons() {
    global $db, $tablepre;
    
    $log = "开始修复核子武器数据...\n\n";
    
    // 先诊断问题
    $diagnosis = diagnoseNuclearWeapons();
    if (!$diagnosis['success'] || $diagnosis['damaged_count'] == 0) {
        return [
            'success' => true,
            'log' => "没有发现需要修复的数据。\n"
        ];
    }
    
    $repaired_count = 0;
    
    foreach ($diagnosis['issues'] as $player) {
        $log .= "修复玩家 {$player['name']} (ID: {$player['pid']})...\n";
        
        // 重建正确的weppara数据
        $correct_para = ['isNuclearWeapon' => 1];
        $correct_para_json = json_encode($correct_para, JSON_UNESCAPED_UNICODE);
        
        // 更新数据库
        $update_data = ['weppara' => $correct_para_json];
        $db->array_update("{$tablepre}players", $update_data, "pid='{$player['pid']}'");
        
        $repaired_count++;
        $log .= "  ✅ 修复完成\n";
    }
    
    $log .= "\n修复完成:\n";
    $log .= "总共修复: {$repaired_count} 个玩家\n";
    
    return [
        'success' => true,
        'log' => $log,
        'repaired_count' => $repaired_count
    ];
}

/**
 * 手动修复指定玩家的武器
 */
function manualRepairWeapon() {
    global $db, $tablepre;
    
    $pid = intval($_POST['pid']);
    $weapon_name = trim($_POST['weapon_name']);
    
    if ($pid <= 0) {
        return ['success' => false, 'error' => '无效的玩家ID'];
    }
    
    $log = "开始手动修复玩家 ID: {$pid}...\n\n";
    
    // 获取玩家数据
    $query = "SELECT * FROM {$tablepre}players WHERE pid='{$pid}'";
    $result = $db->query($query);
    $player = $db->fetch_array($result);
    
    if (!$player) {
        return ['success' => false, 'error' => '找不到指定的玩家'];
    }
    
    $log .= "玩家信息:\n";
    $log .= "  姓名: {$player['name']}\n";
    $log .= "  当前武器: {$player['wep']}\n";
    $log .= "  当前weppara: {$player['weppara']}\n\n";
    
    // 如果指定了武器名称，更新武器名称
    if (!empty($weapon_name)) {
        $player['wep'] = $weapon_name;
        $log .= "更新武器名称为: {$weapon_name}\n";
    }
    
    // 确保武器名称包含核子标识
    if (strpos($player['wep'], '☢') === false) {
        $player['wep'] = '☢' . $player['wep'];
        $log .= "添加核子标识: {$player['wep']}\n";
    }
    
    // 重建weppara数据
    $current_para = get_itmpara($player['weppara']);
    $current_para['isNuclearWeapon'] = 1;
    $new_para_json = json_encode($current_para, JSON_UNESCAPED_UNICODE);
    
    // 更新数据库
    $update_data = [
        'wep' => $player['wep'],
        'weppara' => $new_para_json
    ];
    $db->array_update("{$tablepre}players", $update_data, "pid='{$pid}'");
    
    $log .= "更新后的weppara: {$new_para_json}\n";
    $log .= "\n✅ 修复完成！\n";
    
    return [
        'success' => true,
        'log' => $log
    ];
}
?>
