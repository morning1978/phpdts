<?php
// 调试RuleSet权限问题
define('GAME_ROOT', './');
define('CURSCRIPT', 'debug');
define('IN_GAME', true);

// 包含必要的文件
require GAME_ROOT.'config.inc.php';
require GAME_ROOT.'include/db_mysql.class.php';
require GAME_ROOT.'include/global.func.php';
require GAME_ROOT.'gamedata/ruleset/ruleset_config.php';

// 模拟数据库连接
$db = new dbstuff;
$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

echo "<h1>RuleSet权限调试</h1>";
echo "<style>
.pass { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
.warn { color: orange; font-weight: bold; }
.info { color: blue; }
</style>";

// 测试get_ruleset_config函数
echo "<h2>1. 测试get_ruleset_config函数</h2>";
$all_configs = get_ruleset_config();
if ($all_configs && is_array($all_configs)) {
    echo "<span class='pass'>✓ get_ruleset_config()返回配置数组</span><br>";
    echo "<span class='info'>可用RuleSet: " . implode(', ', array_keys($all_configs)) . "</span><br>";
    
    foreach ($all_configs as $id => $config) {
        echo "<h3>RuleSet: {$id}</h3>";
        echo "<span class='info'>名称: {$config['name']}</span><br>";
        echo "<span class='info'>切糕费用: {$config['credits_cost']}</span><br>";
        echo "<span class='info'>管理员免费: " . ($config['admin_free'] ? '是' : '否') . "</span><br>";
        if (isset($config['avatar_config'])) {
            echo "<span class='pass'>✓ 包含avatar_config</span><br>";
        } else {
            echo "<span class='fail'>✗ 缺少avatar_config</span><br>";
        }
    }
} else {
    echo "<span class='fail'>✗ get_ruleset_config()返回错误</span><br>";
}

// 测试不同用户权限
echo "<h2>2. 测试用户权限</h2>";

$test_users = array(
    array('groupid' => 9, 'credits2' => 0, 'desc' => '超级管理员(groupid=9, credits2=0)'),
    array('groupid' => 4, 'credits2' => 0, 'desc' => '管理员(groupid=4, credits2=0)'),
    array('groupid' => 2, 'credits2' => 0, 'desc' => '版主(groupid=2, credits2=0)'),
    array('groupid' => 1, 'credits2' => 500, 'desc' => '普通用户(groupid=1, credits2=500)'),
    array('groupid' => 1, 'credits2' => 50, 'desc' => '普通用户(groupid=1, credits2=50)'),
);

foreach ($test_users as $user) {
    echo "<h3>{$user['desc']}</h3>";
    
    foreach (array('ACBRA_2009', 'ACDTS_2011', 'ACDTS_298SP4') as $ruleset_id) {
        $can_create = can_create_ruleset_room($ruleset_id, $user);
        if ($can_create) {
            echo "<span class='pass'>✓ 可以创建 {$ruleset_id}</span><br>";
        } else {
            echo "<span class='fail'>✗ 不能创建 {$ruleset_id}</span><br>";
        }
    }
}

// 检查调试文件
echo "<h2>3. 检查调试文件</h2>";
$debug_files = glob(GAME_ROOT.'./doc/etc/can_create_debug_*.txt');
if ($debug_files) {
    echo "<span class='info'>找到 " . count($debug_files) . " 个调试文件</span><br>";
    
    // 显示最新的调试文件内容
    $latest_file = end($debug_files);
    echo "<h3>最新调试文件: " . basename($latest_file) . "</h3>";
    echo "<pre>" . htmlspecialchars(file_get_contents($latest_file)) . "</pre>";
} else {
    echo "<span class='warn'>⚠ 没有找到调试文件</span><br>";
}

// 测试当前登录用户（如果有）
echo "<h2>4. 测试当前用户</h2>";
if (isset($cuser) && $cuser) {
    echo "<span class='info'>当前用户: {$cuser}</span><br>";
    
    // 获取用户数据
    $result = $db->query("SELECT * FROM {$tablepre}users WHERE username='$cuser'");
    if ($db->num_rows($result)) {
        $udata = $db->fetch_array($result);
        echo "<span class='info'>用户组ID: {$udata['groupid']}</span><br>";
        echo "<span class='info'>切糕数量: {$udata['credits2']}</span><br>";
        
        foreach (array('ACBRA_2009', 'ACDTS_2011', 'ACDTS_298SP4') as $ruleset_id) {
            $can_create = can_create_ruleset_room($ruleset_id, $udata);
            if ($can_create) {
                echo "<span class='pass'>✓ 当前用户可以创建 {$ruleset_id}</span><br>";
            } else {
                echo "<span class='fail'>✗ 当前用户不能创建 {$ruleset_id}</span><br>";
            }
        }
    } else {
        echo "<span class='fail'>✗ 找不到用户数据</span><br>";
    }
} else {
    echo "<span class='warn'>⚠ 没有登录用户</span><br>";
}

echo "<h2>调试完成</h2>";
echo "<p class='info'>如果权限检查失败，请检查用户组ID和切糕数量是否满足要求。</p>";

?>
