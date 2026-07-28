<?php
// 测试头像修复是否有效
define('GAME_ROOT', './');
define('CURSCRIPT', 'test');
define('IN_GAME', true);

// 包含必要的文件
require GAME_ROOT.'config.inc.php';
require GAME_ROOT.'include/db_mysql.class.php';
require GAME_ROOT.'include/global.func.php';
require GAME_ROOT.'gamedata/ruleset/ruleset_config.php';
require GAME_ROOT.'include/init.func.php';

echo "<h1>头像修复验证测试</h1>";
echo "<style>
.pass { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
.warn { color: orange; font-weight: bold; }
.info { color: blue; }
.avatar { border: 1px solid #ccc; margin: 5px; max-width: 100px; }
</style>";

// 模拟数据库连接
$db = new dbstuff;
$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

// 测试NPC头像路径生成
echo "<h2>1. 测试NPC头像路径生成</h2>";

// 模拟NPC数据
$test_npc = array(
    'type' => 12,  // NPC类型
    'icon' => 14,  // 头像ID
    'gd' => 'm'
);

// 模拟在ACBRA_2009房间中
$groomid = 1;
$gtablepre = $tablepre;

// 创建测试房间记录
$db->query("DELETE FROM {$gtablepre}game WHERE groomid = 1");
$db->query("INSERT INTO {$gtablepre}game (groomid, gruleset, gamestate) VALUES (1, 'ACBRA_2009', 0)");

echo "<h3>测试场景：ACBRA_2009房间中的NPC</h3>";
echo "<span class='info'>NPC类型: {$test_npc['type']}, 头像ID: {$test_npc['icon']}</span><br>";

// 调用init_icon函数
init_icon($test_npc);

echo "<span class='info'>生成的头像路径: {$test_npc['iconImg']}</span><br>";

// 检查路径是否正确
if (strpos($test_npc['iconImg'], 'gamedata/ruleset/ACBRA_2009/img/n_14.gif') !== false) {
    echo "<span class='pass'>✓ NPC头像路径正确</span><br>";
    
    // 检查是否没有重复的img/前缀
    if (strpos($test_npc['iconImg'], 'img/img/') === false && strpos($test_npc['iconImg'], 'img/gamedata/') === false) {
        echo "<span class='pass'>✓ 没有重复的img/前缀</span><br>";
    } else {
        echo "<span class='fail'>✗ 仍然有重复的img/前缀</span><br>";
    }
    
    // 检查文件是否存在
    if (file_exists($test_npc['iconImg'])) {
        echo "<span class='pass'>✓ NPC头像文件存在</span><br>";
        echo "<img src='{$test_npc['iconImg']}' class='avatar' alt='NPC头像'>";
    } else {
        echo "<span class='warn'>⚠ NPC头像文件不存在（但路径格式正确）</span><br>";
    }
} else {
    echo "<span class='fail'>✗ NPC头像路径不正确</span><br>";
    echo "<span class='info'>期望包含: gamedata/ruleset/ACBRA_2009/img/n_14.gif</span><br>";
    echo "<span class='info'>实际路径: {$test_npc['iconImg']}</span><br>";
}

// 测试玩家头像路径生成
echo "<h2>2. 测试玩家头像路径生成</h2>";

$test_player = array(
    'type' => 0,   // 玩家类型
    'icon' => 5,   // 头像ID
    'gd' => 'f'    // 女性
);

echo "<h3>测试场景：ACBRA_2009房间中的女性玩家</h3>";
echo "<span class='info'>玩家类型: {$test_player['type']}, 头像ID: {$test_player['icon']}, 性别: {$test_player['gd']}</span><br>";

init_icon($test_player);

echo "<span class='info'>生成的头像路径: {$test_player['iconImg']}</span><br>";

// 检查路径是否正确
if (strpos($test_player['iconImg'], 'gamedata/ruleset/ACBRA_2009/img/f_5.gif') !== false) {
    echo "<span class='pass'>✓ 玩家头像路径正确</span><br>";
    
    // 检查文件是否存在
    if (file_exists($test_player['iconImg'])) {
        echo "<span class='pass'>✓ 玩家头像文件存在</span><br>";
        echo "<img src='{$test_player['iconImg']}' class='avatar' alt='玩家头像'>";
    } else {
        echo "<span class='warn'>⚠ 玩家头像文件不存在（但路径格式正确）</span><br>";
    }
} else {
    echo "<span class='fail'>✗ 玩家头像路径不正确</span><br>";
    echo "<span class='info'>期望包含: gamedata/ruleset/ACBRA_2009/img/f_5.gif</span><br>";
    echo "<span class='info'>实际路径: {$test_player['iconImg']}</span><br>";
}

// 测试默认房间（非RuleSet）
echo "<h2>3. 测试默认房间头像生成</h2>";

$groomid = 0; // 默认房间

$test_default_npc = array(
    'type' => 12,
    'icon' => 14,
    'gd' => 'm'
);

echo "<h3>测试场景：默认房间中的NPC</h3>";
init_icon($test_default_npc);

echo "<span class='info'>生成的头像路径: {$test_default_npc['iconImg']}</span><br>";

if (strpos($test_default_npc['iconImg'], 'img/n_14.gif') !== false && strpos($test_default_npc['iconImg'], 'gamedata/ruleset') === false) {
    echo "<span class='pass'>✓ 默认房间NPC头像路径正确</span><br>";
} else {
    echo "<span class='fail'>✗ 默认房间NPC头像路径不正确</span><br>";
}

// 测试用户头像选择界面
echo "<h2>4. 测试用户头像选择界面</h2>";

$groomid = 1; // 回到RuleSet房间
$gender = 'm';
$icon = 3;

include_once GAME_ROOT.'include/user.func.php';
$iconarray = get_iconlist();

echo "<h3>测试场景：ACBRA_2009房间中的男性用户头像选择</h3>";
echo "<span class='info'>头像选项数量: " . count($iconarray) . "</span><br>";

// 检查头像限制是否正确应用
$config = get_ruleset_config('ACBRA_2009');
if ($config && isset($config['avatar_config'])) {
    $expected_limit = $config['avatar_config']['male_avatars'];
    $actual_limit = count($iconarray);
    
    if ($actual_limit == $expected_limit) {
        echo "<span class='pass'>✓ 头像数量限制正确应用 (期望: {$expected_limit}, 实际: {$actual_limit})</span><br>";
    } else {
        echo "<span class='fail'>✗ 头像数量限制不正确 (期望: {$expected_limit}, 实际: {$actual_limit})</span><br>";
    }
} else {
    echo "<span class='fail'>✗ 无法获取RuleSet配置</span><br>";
}

// 清理测试数据
$db->query("DELETE FROM {$gtablepre}game WHERE groomid = 1");

echo "<h2>测试总结</h2>";
echo "<p class='info'>如果所有测试都通过，说明头像路径修复成功。</p>";
echo "<p class='info'>现在可以在实际游戏中验证头像显示效果。</p>";

echo "<h3>预期效果</h3>";
echo "<ul>";
echo "<li class='pass'>✅ NPC头像路径: gamedata/ruleset/ACBRA_2009/img/n_14.gif</li>";
echo "<li class='pass'>✅ 玩家头像路径: gamedata/ruleset/ACBRA_2009/img/f_5.gif</li>";
echo "<li class='pass'>✅ 默认房间头像路径: img/n_14.gif</li>";
echo "<li class='pass'>✅ 用户头像选择界面显示RuleSet专用头像</li>";
echo "</ul>";

?>
