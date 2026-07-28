<?php
/*
 * RuleSet权限测试脚本
 * 用于验证ruleset房间创建权限是否正常工作
 * 
 * 使用方法：
 * 1. 将此文件上传到游戏根目录
 * 2. 在浏览器中访问：http://your-domain/test_ruleset_permission.php
 * 3. 查看测试结果
 */

// 设置基本常量
define('IN_GAME', true);
define('GAME_ROOT', './');

// 包含必要的文件
require_once './include/config.inc.php';
require_once './include/db_mysqli.class.php';
require_once './gamedata/ruleset/ruleset_config.php';

echo "<h1>RuleSet权限测试</h1>";
echo "<style>
.pass { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
.info { color: blue; }
.debug { background: #f0f0f0; padding: 10px; margin: 10px 0; border-left: 3px solid #ccc; }
</style>";

// 测试1: 检查RuleSet系统是否启用
echo "<h2>测试1: RuleSet系统状态</h2>";
if (isset($ruleset_enabled) && $ruleset_enabled) {
    echo "<span class='pass'>✓ RuleSet系统已启用</span><br>";
} else {
    echo "<span class='fail'>✗ RuleSet系统未启用</span><br>";
}

// 测试2: 检查RuleSet配置是否加载
echo "<h2>测试2: RuleSet配置</h2>";
if (isset($ruleset_config) && is_array($ruleset_config) && !empty($ruleset_config)) {
    echo "<span class='pass'>✓ RuleSet配置已加载</span><br>";
    echo "<span class='info'>可用的RuleSet: " . implode(', ', array_keys($ruleset_config)) . "</span><br>";
} else {
    echo "<span class='fail'>✗ RuleSet配置未加载或为空</span><br>";
}

// 测试3: 测试权限检查函数
echo "<h2>测试3: 权限检查函数测试</h2>";

// 模拟不同权限等级的用户
$test_users = array(
    'normal_user' => array('groupid' => 1, 'credits2' => 50, 'username' => 'test_normal'),
    'low_admin' => array('groupid' => 2, 'credits2' => 50, 'username' => 'test_admin2'),
    'mid_admin' => array('groupid' => 4, 'credits2' => 50, 'username' => 'test_admin4'),
    'high_admin' => array('groupid' => 9, 'credits2' => 50, 'username' => 'test_admin9'),
    'rich_user' => array('groupid' => 1, 'credits2' => 500, 'username' => 'test_rich'),
);

foreach ($test_users as $user_type => $user_data) {
    echo "<h3>用户类型: {$user_type} (groupid: {$user_data['groupid']}, credits2: {$user_data['credits2']})</h3>";
    
    foreach ($ruleset_config as $ruleset_id => $config) {
        $can_create = can_create_ruleset_room($ruleset_id, $user_data);
        $status_class = $can_create ? 'pass' : 'fail';
        $status_text = $can_create ? '✓ 可以创建' : '✗ 无法创建';
        
        echo "<div class='debug'>";
        echo "<strong>{$ruleset_id}</strong>: <span class='{$status_class}'>{$status_text}</span><br>";
        echo "费用: {$config['credits_cost']} 切糕, 管理员免费: " . ($config['admin_free'] ? '是' : '否') . "<br>";
        
        // 详细分析
        if ($config['admin_free'] && $user_data['groupid'] >= 2) {
            echo "<span class='info'>→ 管理员免费通过</span><br>";
        } elseif ($user_data['credits2'] >= $config['credits_cost']) {
            echo "<span class='info'>→ 切糕数量足够</span><br>";
        } else {
            echo "<span class='info'>→ 权限不足且切糕不够</span><br>";
        }
        echo "</div>";
    }
}

// 测试4: 检查调试文件目录
echo "<h2>测试4: 调试文件目录</h2>";
$debug_dir = GAME_ROOT . 'doc/etc/';
if (is_dir($debug_dir) && is_writable($debug_dir)) {
    echo "<span class='pass'>✓ 调试目录可写: {$debug_dir}</span><br>";
} else {
    echo "<span class='fail'>✗ 调试目录不存在或不可写: {$debug_dir}</span><br>";
}

echo "<h2>测试完成</h2>";
echo "<p class='info'>如果所有测试都通过，但仍然无法创建ruleset房间，请检查：</p>";
echo "<ul>";
echo "<li>用户登录状态是否正常</li>";
echo "<li>数据库连接是否正常</li>";
echo "<li>用户数据是否正确加载</li>";
echo "<li>是否有其他错误日志</li>";
echo "</ul>";

?>
