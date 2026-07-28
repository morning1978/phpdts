<?php
/*
 * RuleSet系统测试脚本
 * 用于验证时光重现系统的基本功能
 */

// 模拟游戏环境
define('IN_GAME', true);
define('GAME_ROOT', '../../');

// 包含必要文件
require_once GAME_ROOT . './gamedata/ruleset/ruleset_config.php';
require_once GAME_ROOT . './gamedata/ruleset/story_config.php';

echo "<h1>RuleSet系统（时光重现）测试</h1>\n";

// 测试1: 配置文件加载
echo "<h2>测试1: 配置文件加载</h2>\n";
$config_test = get_ruleset_config();
if ($config_test) {
    echo "<span style='color: green;'>✓ 配置文件加载成功</span><br>\n";
    echo "可用的RuleSet数量: " . count($config_test) . "<br>\n";
    foreach ($config_test as $id => $config) {
        echo "- {$id}: {$config['name']}<br>\n";
    }
} else {
    echo "<span style='color: red;'>✗ 配置文件加载失败</span><br>\n";
}

// 测试2: 单个RuleSet配置获取
echo "<h2>测试2: 单个RuleSet配置获取</h2>\n";
$acbra_config = get_ruleset_config('ACBRA_2009');
if ($acbra_config) {
    echo "<span style='color: green;'>✓ ACBRA_2009配置获取成功</span><br>\n";
    echo "名称: {$acbra_config['name']}<br>\n";
    echo "描述: {$acbra_config['description']}<br>\n";
    echo "费用: {$acbra_config['credits_cost']} 切糕<br>\n";
} else {
    echo "<span style='color: red;'>✗ ACBRA_2009配置获取失败</span><br>\n";
}

// 测试3: 权限检查功能
echo "<h2>测试3: 权限检查功能</h2>\n";
$test_user_admin = array('groupid' => 4, 'credits2' => 50);
$test_user_normal = array('groupid' => 1, 'credits2' => 150);
$test_user_poor = array('groupid' => 1, 'credits2' => 50);

$admin_can_create = can_create_ruleset_room('ACBRA_2009', $test_user_admin);
$normal_can_create = can_create_ruleset_room('ACBRA_2009', $test_user_normal);
$poor_cannot_create = can_create_ruleset_room('ACBRA_2009', $test_user_poor);

echo "管理员（50切糕）: " . ($admin_can_create ? "<span style='color: green;'>✓ 可以创建</span>" : "<span style='color: red;'>✗ 不能创建</span>") . "<br>\n";
echo "普通用户（150切糕）: " . ($normal_can_create ? "<span style='color: green;'>✓ 可以创建</span>" : "<span style='color: red;'>✗ 不能创建</span>") . "<br>\n";
echo "贫穷用户（50切糕）: " . ($poor_cannot_create ? "<span style='color: red;'>✗ 应该不能创建</span>" : "<span style='color: green;'>✓ 正确，不能创建</span>") . "<br>\n";

// 测试4: 资源文件路径获取
echo "<h2>测试4: 资源文件路径获取</h2>\n";
$resource_path = get_ruleset_resource_path('ACBRA_2009', 'cache');
if ($resource_path) {
    echo "<span style='color: green;'>✓ 资源路径获取成功</span><br>\n";
    echo "路径: {$resource_path}<br>\n";
    
    // 检查资源文件是否存在
    $resources_exists = ruleset_resource_exists('ACBRA_2009', 'resources_1.php', 'cache');
    $gamecfg_exists = ruleset_resource_exists('ACBRA_2009', 'gamecfg_1.php', 'cache');
    
    echo "resources_1.php: " . ($resources_exists ? "<span style='color: green;'>✓ 存在</span>" : "<span style='color: red;'>✗ 不存在</span>") . "<br>\n";
    echo "gamecfg_1.php: " . ($gamecfg_exists ? "<span style='color: green;'>✓ 存在</span>" : "<span style='color: red;'>✗ 不存在</span>") . "<br>\n";
} else {
    echo "<span style='color: red;'>✗ 资源路径获取失败</span><br>\n";
}

// 测试5: 剧情配置测试
echo "<h2>测试5: 剧情配置测试</h2>\n";
$opening_story = get_ruleset_story('ACBRA_2009', 'opening');
$ending_story = get_ruleset_story('ACBRA_2009', 'ending');

if ($opening_story) {
    echo "<span style='color: green;'>✓ 开场剧情获取成功</span><br>\n";
    echo "标题: {$opening_story['title']}<br>\n";
} else {
    echo "<span style='color: red;'>✗ 开场剧情获取失败</span><br>\n";
}

if ($ending_story) {
    echo "<span style='color: green;'>✓ 结束剧情获取成功</span><br>\n";
    echo "标题: {$ending_story['title']}<br>\n";
} else {
    echo "<span style='color: red;'>✗ 结束剧情获取失败</span><br>\n";
}

// 测试6: 所有RuleSet的完整性检查
echo "<h2>测试6: 所有RuleSet完整性检查</h2>\n";
$all_rulesets = get_ruleset_config();
if ($all_rulesets) {
    foreach ($all_rulesets as $ruleset_id => $config) {
        echo "<h3>{$config['name']} ({$ruleset_id})</h3>\n";
        
        // 检查必要的配置项
        $required_keys = ['name', 'description', 'credits_cost', 'initial_setup', 'story_config'];
        $missing_keys = array_diff($required_keys, array_keys($config));
        
        if (empty($missing_keys)) {
            echo "<span style='color: green;'>✓ 配置完整</span><br>\n";
        } else {
            echo "<span style='color: red;'>✗ 缺少配置项: " . implode(', ', $missing_keys) . "</span><br>\n";
        }
        
        // 检查资源文件
        $resources_exist = ruleset_resource_exists($ruleset_id, 'resources_1.php');
        $gamecfg_exist = ruleset_resource_exists($ruleset_id, 'gamecfg_1.php');
        
        echo "资源文件: " . ($resources_exist ? "<span style='color: green;'>✓</span>" : "<span style='color: red;'>✗</span>") . " ";
        echo "游戏配置: " . ($gamecfg_exist ? "<span style='color: green;'>✓</span>" : "<span style='color: red;'>✗</span>") . "<br>\n";
        
        // 检查剧情配置
        $has_opening = has_ruleset_story($ruleset_id);
        echo "剧情配置: " . ($has_opening ? "<span style='color: green;'>✓</span>" : "<span style='color: red;'>✗</span>") . "<br>\n";
    }
}

echo "<h2>测试完成</h2>\n";
echo "<p>如果所有测试都显示绿色的✓，说明RuleSet系统基本功能正常。</p>\n";
echo "<p>如果有红色的✗，请检查对应的配置文件和资源文件。</p>\n";

?>
