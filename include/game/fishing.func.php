<?php

if(!defined('IN_GAME')) {
    exit('Access Denied');
}

/**
 * 钓鱼功能的核心逻辑
 */

// 可以钓鱼的地点列表
$fishing_places_list = array(
    3,  // 雪之镇（靠海的北海道，可以钓鱼）
    7,  // 清水池（是一个湖泊，可以钓鱼）
    12, // 夏之镇（设定中靠海，可以钓鱼）
    4,  // 索拉利斯（致敬了1961年的波兰科幻小说Solaris，可以钓上外星鱼和其他部件）
    24, // 永恒的世界（设定中是一片云海，可以钓上奇幻风格的东西）
);

/**
 * 检查当前位置是否可以钓鱼
 *
 * @param int $pls 当前位置ID
 * @return bool 是否可以钓鱼
 */
function can_fishing($pls) {
    global $fishing_places_list;
    return in_array($pls, $fishing_places_list);
}

/**
 * 开始钓鱼
 *
 * @param array &$data 玩家数据
 * @return void
 */
function start_fishing(&$data) {
    global $log, $mode, $now;
    extract($data, EXTR_REFS);

    // 检查当前位置是否可以钓鱼
    if (!can_fishing($pls)) {
        $log .= '<span class="yellow">这个地方没有水，不能钓鱼！</span><br>';
        $mode = 'command';
        return;
    }

    // 初始化钓鱼状态
    $clbpara['fishing'] = array(
        'start_time' => $now,
        'last_check_time' => $now,
        'caught_items' => array()
    );

    $log .= '你拿出鱼竿，开始在这里钓鱼...<br>';
    $log .= '你可以一边钓鱼，一边' . ($state == 1 ? '休息' : ($state == 2 ? '治疗' : '静养')) . '。<br>';
    $log .= '钓到的鱼会自动存入鱼篓子中。<br>';

    $mode = 'fishing';
}

/**
 * 结束钓鱼
 *
 * @param array &$data 玩家数据
 * @return void
 */
function end_fishing(&$data) {
    global $log, $mode;
    extract($data, EXTR_REFS);

    // 检查是否有钓鱼状态
    if (empty($clbpara['fishing'])) {
        $log .= '你没有在钓鱼！<br>';
        $mode = 'command';
        return;
    }

    // 最后一次检查钓鱼
    check_fishing($data);

    // 获取钓到的物品
    $caught_items = $clbpara['fishing']['caught_items'];
    $count = count($caught_items);

    if ($count > 0) {
        $log .= '你结束了钓鱼，总共钓到了 <span class="yellow">' . $count . '</span> 件物品：<br>';

        foreach ($caught_items as $item) {
            $log .= '- <span class="yellow">' . $item['name'] . '</span><br>';
        }

        // 创建鱼篓子
        create_fish_basket($data, $caught_items);
    } else {
        $log .= '你结束了钓鱼，但是什么都没钓到...<br>';
    }

    // 显示属性提升总结
    if (!empty($clbpara['fishing']['stat_increases'])) {
        $stat_increases = $clbpara['fishing']['stat_increases'];
        $stat_summary = array(
            '基础攻击' => 0,
            '基础防御' => 0,
            '生命上限' => 0,
            '体力上限' => 0
        );

        foreach ($stat_increases as $increase) {
            $stat_summary[$increase['type']] += $increase['amount'];
        }

        $log .= '<br><span class="lime">钓鱼期间，你的属性得到了提升：</span><br>';

        foreach ($stat_summary as $type => $amount) {
            if ($amount > 0) {
                $log .= '<span class="lime">- ' . $type . ' +' . $amount . '</span><br>';
            }
        }
    }

    // 清除钓鱼状态
    unset($clbpara['fishing']);

    $mode = 'command';
}

/**
 * 检查钓鱼状态，看是否钓到了新物品或提升属性
 *
 * @param array &$data 玩家数据
 * @return void
 */
function check_fishing(&$data) {
    global $log, $now;
    extract($data, EXTR_REFS);

    // 检查是否有钓鱼状态
    if (empty($clbpara['fishing'])) {
        return;
    }

    // 获取上次检查时间
    $last_check_time = $clbpara['fishing']['last_check_time'];

    // 每分钟检查一次是否钓到鱼
    $check_interval = 60; // 60秒
    $elapsed_time = $now - $last_check_time;

    // 如果时间不足，不检查
    if ($elapsed_time < $check_interval) {
        return;
    }

    // 检查玩家是否装备了钓竿
    $has_fishing_rod = false;
    $fishing_rod_bonus = 1.0; // 默认没有加成

    // 检查手臂装备是否有钓竿
    if (strpos($ara, '钓竿') !== false || strpos($ara, '钓鱼竿') !== false) {
        $has_fishing_rod = true;
        $fishing_rod_bonus = 1.5; // 装备钓竿时提高 50% 效率

        // 如果钓竿有特殊属性，可以进一步提高效率
        if (strpos($arask, 'z') !== false) { // 天然属性
            $fishing_rod_bonus += 0.3; // 再提高 30%
        }
        if (strpos($arask, 'Z') !== false) { // 精英属性
            $fishing_rod_bonus += 0.5; // 再提高 50%
        }
    }

    // 计算应该检查多少次
    $base_check_times = floor($elapsed_time / $check_interval);

    // 如果有钓竿，增加检查次数
    $check_times = $has_fishing_rod ? floor($base_check_times * $fishing_rod_bonus) : $base_check_times;

    // 更新最后检查时间
    $clbpara['fishing']['last_check_time'] = $last_check_time + ($base_check_times * $check_interval);

    // 记录钓竿信息
    if ($has_fishing_rod && !isset($clbpara['fishing']['rod_message_shown'])) {
        $log .= '<span class="lime">你使用了' . $ara . '，钓鱼效率提高了 ' . round(($fishing_rod_bonus - 1) * 100) . '%！</span><br>';
        $clbpara['fishing']['rod_message_shown'] = true;
    }

    // 加载钓鱼物品配置
    include_once GAME_ROOT . './gamedata/cache/fishing.php';

    // 直接使用地点ID获取可钓的物品列表
    $available_items = isset($fishing_places[$pls]) ? $fishing_places[$pls] : $fishing_places['default'];

    // 获取当前时间段
    $hour = (int)date('H', $now);
    $time_period = '';
    if ($hour >= 5 && $hour < 11) {
        $time_period = 'morning';
    } elseif ($hour >= 11 && $hour < 17) {
        $time_period = 'noon';
    } elseif ($hour >= 17 && $hour < 22) {
        $time_period = 'evening';
    } else {
        $time_period = 'night';
    }

    // 获取当前天气
    global $weather, $wthinfo;
    $weather_name = $wthinfo[$weather];

    // 对每次检查进行处理
    for ($i = 0; $i < $check_times; $i++) {
        // 基础成功率是 70%，装备钓竿可提高成功率
        $catch_chance = $has_fishing_rod ? min(95, 70 + round(($fishing_rod_bonus - 1) * 30)) : 70;

        if (rand(1, 100) <= $catch_chance) {
            // 从可用物品中随机选择一个
            $item_name = $available_items[array_rand($available_items)];

            // 获取物品信息
            $item_info = $fishing_items[$item_name];

            // 应用时间和天气加成
            $probability_multiplier = 1.0;
            if (isset($fishing_time_bonus[$time_period][$item_name])) {
                $probability_multiplier *= $fishing_time_bonus[$time_period][$item_name];
            }
            if (isset($fishing_weather_bonus[$weather_name][$item_name])) {
                $probability_multiplier *= $fishing_weather_bonus[$weather_name][$item_name];
            }

            // 如果有钓竿，提高稀有物品的概率
            if ($has_fishing_rod) {
                // 稀有物品的概率提高
                if ($item_info[4] < 20) { // 如果是稀有物品（概率权重小于 20）
                    $probability_multiplier *= $fishing_rod_bonus;
                }
            }

            // 根据物品的概率权重和加成决定是否钓到
            $base_probability = $item_info[4];
            $adjusted_probability = $base_probability * $probability_multiplier;

            if (rand(1, 1000) <= $adjusted_probability * 10) {
                // 钓到了物品，添加到已钓到的物品列表中
                $clbpara['fishing']['caught_items'][] = array(
                    'name' => $item_name,
                    'type' => $item_info[0],
                    'effect' => $item_info[1],
                    'quantity' => $item_info[2],
                    'attribute' => $item_info[3]
                );

                // 根据是否使用钓竿显示不同的消息
                if ($has_fishing_rod) {
                    $log .= '你熟练地操作' . $ara . '，钓到了一个 <span class="yellow">' . $item_name . '</span>！<br>';
                } else {
                    $log .= '你感觉鱼竿一沉，钓到了一个 <span class="yellow">' . $item_name . '</span>！<br>';
                }
            }
        }

        // 基础概率是 15%，装备钓竿可提高属性提升概率
        $stat_boost_chance = $has_fishing_rod ? min(30, 15 + round(($fishing_rod_bonus - 1) * 15)) : 15;

        if (rand(1, 100) <= $stat_boost_chance) {
            // 随机选择一个属性提升
            $stat_type = rand(1, 4);
            $stat_increase = 0;
            $stat_name = '';

            // 如果有钓竿，提高属性提升幅度
            $stat_boost_multiplier = $has_fishing_rod ? $fishing_rod_bonus : 1.0;

            switch ($stat_type) {
                case 1: // 提升攻击
                    $base_increase = rand(1, 3); // 基础提升 1-3 点
                    $stat_increase = $has_fishing_rod ? ceil($base_increase * $stat_boost_multiplier) : $base_increase;
                    $att += $stat_increase;
                    $stat_name = '基础攻击';
                    break;
                case 2: // 提升防御
                    $base_increase = rand(1, 3); // 基础提升 1-3 点
                    $stat_increase = $has_fishing_rod ? ceil($base_increase * $stat_boost_multiplier) : $base_increase;
                    $def += $stat_increase;
                    $stat_name = '基础防御';
                    break;
                case 3: // 提升生命上限
                    $base_increase = rand(5, 15); // 基础提升 5-15 点
                    $stat_increase = $has_fishing_rod ? ceil($base_increase * $stat_boost_multiplier) : $base_increase;
                    $mhp += $stat_increase;
                    $hp = min($hp + $stat_increase, $mhp); // 同时提升当前生命
                    $stat_name = '生命上限';
                    break;
                case 4: // 提升体力上限
                    $base_increase = rand(5, 15); // 基础提升 5-15 点
                    $stat_increase = $has_fishing_rod ? ceil($base_increase * $stat_boost_multiplier) : $base_increase;
                    $msp += $stat_increase;
                    $sp = min($sp + $stat_increase, $msp); // 同时提升当前体力
                    $stat_name = '体力上限';
                    break;
            }

            // 记录属性提升
            if (!isset($clbpara['fishing']['stat_increases'])) {
                $clbpara['fishing']['stat_increases'] = array();
            }

            $clbpara['fishing']['stat_increases'][] = array(
                'type' => $stat_name,
                'amount' => $stat_increase
            );

            // 根据是否使用钓竿显示不同的消息
            if ($has_fishing_rod) {
                $log .= '<span class="lime">使用' . $ara . '钓鱼的过程中，你的手臂和意志得到了锻炼，你的' . $stat_name . '提升了 ' . $stat_increase . ' 点！</span><br>';
            } else {
                $log .= '<span class="lime">长时间的钓鱼让你感到心旷神怡，你的' . $stat_name . '提升了 ' . $stat_increase . ' 点！</span><br>';
            }
        }
    }
}

/**
 * 创建鱼篓子并将钓到的物品放入其中
 *
 * @param array &$data 玩家数据
 * @param array $caught_items 钓到的物品列表
 * @return void
 */
function create_fish_basket(&$data, $caught_items) {
    global $log, $itm0, $itmk0, $itme0, $itms0, $itmsk0, $itmpara0;
    extract($data, EXTR_REFS);

    // 检查是否已有鱼篓子
    $has_basket = false;
    $basket_index = -1;

    for ($i = 1; $i <= 6; $i++) {
        if (${'itmk'.$i} == 'DA' && ${'itm'.$i} == '鱼篓子' && ${'itmsk'.$i} == 'Z') {
            $has_basket = true;
            $basket_index = $i;
            break;
        }
    }

    // 如果没有鱼篓子，创建一个新的
    if (!$has_basket) {
        // 创建鱼篓子并放入玩家物品栏
        $itm0 = '鱼篓子';
        $itmk0 = 'Z';
        $itme0 = count($caught_items); // 效果值表示容量
        $itms0 = count($caught_items); // 数量表示已使用空间
        $itmsk0 = 'Z'; // 标记为特殊物品（菁英）

        // 将钓到的物品放入鱼篓子
        $basket_items = array();
        foreach ($caught_items as $index => $item) {
            $basket_items[$index] = array(
                'itm' => $item['name'],
                'itmk' => $item['type'],
                'itme' => $item['effect'],
                'itms' => $item['quantity'],
                'itmsk' => $item['attribute'],
                'itmpara' => ''
            );
        }

        // 将物品列表转换为JSON并存储在itmpara0中
        $itmpara0 = json_encode($basket_items, JSON_UNESCAPED_UNICODE);

        $log .= '你将钓到的物品都放入了一个新的<span class="yellow">鱼篓子</span>中。<br>';
    } else {
        // 如果已有鱼篓子，将新物品添加到现有鱼篓子中
        $basket_items = json_decode(${'itmpara'.$basket_index}, true);
        if (!$basket_items) $basket_items = array();

        $start_index = count($basket_items);
        foreach ($caught_items as $index => $item) {
            $basket_items[$start_index + $index] = array(
                'itm' => $item['name'],
                'itmk' => $item['type'],
                'itme' => $item['effect'],
                'itms' => $item['quantity'],
                'itmsk' => $item['attribute'],
                'itmpara' => ''
            );
        }

        // 更新鱼篓子信息
        ${'itme'.$basket_index} = count($basket_items); // 更新容量
        ${'itms'.$basket_index} = count($basket_items); // 更新已使用空间
        ${'itmpara'.$basket_index} = json_encode($basket_items, JSON_UNESCAPED_UNICODE);

        $log .= '你将钓到的物品都放入了现有的<span class="yellow">鱼篓子</span>中。<br>';
    }
}

/**
 * 处理钓鱼状态下的命令
 *
 * @param string $command 命令
 * @param array &$data 玩家数据
 * @return void
 */
function fishing_command($command, &$data) {
    global $log;

    if ($command == 'back') {
        // 结束钓鱼
        end_fishing($data);
    } elseif ($command == 'rest') {
        // 继续钓鱼
        $log .= '你继续钓鱼...<br>';
        check_fishing($data);
    } else {
        $log .= '你正在钓鱼，不能执行其他命令！<br>';
    }
}

?>
