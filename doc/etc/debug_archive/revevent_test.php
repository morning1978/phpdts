<?php
define('IN_GAME', true);
define('GAME_ROOT', '../');
include_once '../include/global.func.php';
include_once '../include/game/revevent.func.php';

// 创建测试玩家数据
$test_player = array(
    'name' => 'Test Player',
    'clbpara' => array(
        'randver1' => 64,  // 中等值，应该每次提升2点
        'randver2' => 150, // 中等值，应该每次提升50点
        'charge1' => 0,
        'charge2' => 0,
        'charge3' => 0,
        'charge4' => 0
    )
);

// 测试update_charge_values函数
echo "测试 update_charge_values 函数:\n";
echo "初始值: charge1={$test_player['clbpara']['charge1']}, charge2={$test_player['clbpara']['charge2']}, ";
echo "charge3={$test_player['clbpara']['charge3']}, charge4={$test_player['clbpara']['charge4']}\n";

// 模拟5次移动或探索
for ($i = 1; $i <= 5; $i++) {
    update_charge_values($test_player);
    echo "第{$i}次更新后: charge1={$test_player['clbpara']['charge1']}, charge2={$test_player['clbpara']['charge2']}, ";
    echo "charge3={$test_player['clbpara']['charge3']}, charge4={$test_player['clbpara']['charge4']}\n";
}

// 测试get_charge_value函数
echo "\n测试 get_charge_value 函数:\n";
$charge1 = get_charge_value($test_player, 'charge1');
echo "charge1 = {$charge1}\n";

$all_charges = get_charge_value($test_player);
echo "所有charge值: ";
print_r($all_charges);

// 测试set_charge_value函数
echo "\n测试 set_charge_value 函数:\n";
echo "设置 charge1 = 100\n";
set_charge_value($test_player, 'charge1', 100);
echo "设置后 charge1 = " . get_charge_value($test_player, 'charge1') . "\n";

echo "设置 charge1 = 200 (应该被限制为101)\n";
set_charge_value($test_player, 'charge1', 200);
echo "设置后 charge1 = " . get_charge_value($test_player, 'charge1') . "\n";

echo "设置 charge3 = 200 (应该被限制为128)\n";
set_charge_value($test_player, 'charge3', 200);
echo "设置后 charge3 = " . get_charge_value($test_player, 'charge3') . "\n";

echo "设置 charge3 = -200 (应该被限制为-128)\n";
set_charge_value($test_player, 'charge3', -200);
echo "设置后 charge3 = " . get_charge_value($test_player, 'charge3') . "\n";

echo "设置 charge2 = 1000 (没有上限)\n";
set_charge_value($test_player, 'charge2', 1000);
echo "设置后 charge2 = " . get_charge_value($test_player, 'charge2') . "\n";

echo "设置 charge4 = -5000 (没有下限)\n";
set_charge_value($test_player, 'charge4', -5000);
echo "设置后 charge4 = " . get_charge_value($test_player, 'charge4') . "\n";

// 测试边界情况
echo "\n测试边界情况:\n";
$test_player['clbpara']['randver1'] = 1;   // 最小值，应该每次提升1点
$test_player['clbpara']['randver2'] = 1;   // 最小值，应该每次提升1点
$test_player['clbpara']['charge1'] = 99;  // 接近上限
$test_player['clbpara']['charge3'] = 127; // 接近上限

echo "设置极限值: randver1=1, randver2=1, charge1=99, charge3=127\n";
update_charge_values($test_player);
echo "更新后: charge1={$test_player['clbpara']['charge1']}, charge2={$test_player['clbpara']['charge2']}, ";
echo "charge3={$test_player['clbpara']['charge3']}, charge4={$test_player['clbpara']['charge4']}\n";

$test_player['clbpara']['randver1'] = 128; // 最大值，应该每次提升4点
$test_player['clbpara']['randver2'] = 256; // 最大值，应该每次提升100点
$test_player['clbpara']['charge1'] = 98;  // 接近上限
$test_player['clbpara']['charge3'] = -127; // 接近下限

echo "设置极限值: randver1=128, randver2=256, charge1=98, charge3=-127\n";
update_charge_values($test_player);
echo "更新后: charge1={$test_player['clbpara']['charge1']}, charge2={$test_player['clbpara']['charge2']}, ";
echo "charge3={$test_player['clbpara']['charge3']}, charge4={$test_player['clbpara']['charge4']}\n";

echo "\n测试完成!\n";
?>
