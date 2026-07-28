<?php
/*
 * 测试变量作用域修复
 * 模拟房间创建过程中的权限检查
 */

define('IN_GAME', true);
define('GAME_ROOT', './');

echo "<h1>变量作用域修复测试</h1>";
echo "<style>
.pass { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
.info { color: blue; }
</style>";

// 模拟函数内部调用（类似roommng_create_new_room）
function test_function_scope() {
    echo "<h2>函数内部测试</h2>";
    
    // 包含配置文件（模拟roommng.func.php中的调用）
    include_once GAME_ROOT.'./gamedata/ruleset/ruleset_config.php';
    
    // 确保配置加载
    ensure_ruleset_config_loaded();
    
    // 测试用户数据
    $test_user = array(
        'groupid' => 9,
        'credits2' => 745,
        'username' => 'test_admin'
    );
    
    // 测试权限检查
    $result = can_create_ruleset_room('ACBRA_2009', $test_user);
    
    if ($result) {
        echo "<span class='pass'>✓ 权限检查通过</span><br>";
    } else {
        echo "<span class='fail'>✗ 权限检查失败</span><br>";
    }
    
    // 检查全局变量状态
    global $ruleset_enabled, $ruleset_config;
    echo "<span class='info'>ruleset_enabled: " . (isset($ruleset_enabled) ? $ruleset_enabled : 'undefined') . "</span><br>";
    echo "<span class='info'>ruleset_config存在: " . (isset($ruleset_config) ? 'yes' : 'no') . "</span><br>";
    
    return $result;
}

// 执行测试
$test_result = test_function_scope();

echo "<h2>测试结果</h2>";
if ($test_result) {
    echo "<span class='pass'>✓ 作用域问题已修复</span><br>";
} else {
    echo "<span class='fail'>✗ 作用域问题仍然存在</span><br>";
}

echo "<p class='info'>请检查生成的调试文件以获取详细信息。</p>";

?>
