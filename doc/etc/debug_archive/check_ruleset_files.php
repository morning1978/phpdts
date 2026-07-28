<?php
/*
 * 检查RuleSet文件是否存在
 * 诊断资源文件切换问题
 */

define('IN_GAME', true);
define('GAME_ROOT', './');

echo "<h1>RuleSet文件检查</h1>";
echo "<style>
.pass { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
.warn { color: orange; font-weight: bold; }
.info { color: blue; }
</style>";

$rulesets = ['ACBRA_2009', 'ACDTS_2011', 'ACDTS_298SP4'];
$files_to_check = [
    'npc_1.php',
    'resources_1.php', 
    'addnpc_1.php',
    'item_1.php',
    'shopitem_1.php'
];

foreach ($rulesets as $ruleset_id) {
    echo "<h2>检查 {$ruleset_id}</h2>";
    
    $base_path = GAME_ROOT . "gamedata/ruleset/{$ruleset_id}/cache/";
    echo "<span class='info'>基础路径: {$base_path}</span><br>";
    
    if (!is_dir($base_path)) {
        echo "<span class='fail'>✗ 目录不存在</span><br>";
        continue;
    } else {
        echo "<span class='pass'>✓ 目录存在</span><br>";
    }
    
    foreach ($files_to_check as $file) {
        $file_path = $base_path . $file;
        echo "<span class='info'>检查文件: {$file}</span> - ";
        
        if (file_exists($file_path)) {
            $size = filesize($file_path);
            echo "<span class='pass'>✓ 存在 ({$size} 字节)</span><br>";
        } else {
            echo "<span class='fail'>✗ 不存在</span><br>";
        }
    }
    
    echo "<br>";
}

// 检查默认文件
echo "<h2>检查默认文件</h2>";
$default_path = GAME_ROOT . "gamedata/cache/";
echo "<span class='info'>默认路径: {$default_path}</span><br>";

foreach ($files_to_check as $file) {
    $file_path = $default_path . $file;
    echo "<span class='info'>检查文件: {$file}</span> - ";
    
    if (file_exists($file_path)) {
        $size = filesize($file_path);
        echo "<span class='pass'>✓ 存在 ({$size} 字节)</span><br>";
    } else {
        echo "<span class='fail'>✗ 不存在</span><br>";
    }
}

// 测试config函数
echo "<h2>测试config函数</h2>";

// 模拟不同的groomid
$test_groomids = [0, 1, 2, 3];

foreach ($test_groomids as $test_groomid) {
    echo "<h3>测试 groomid = {$test_groomid}</h3>";
    
    // 模拟全局变量
    $GLOBALS['groomid'] = $test_groomid;
    
    // 需要数据库连接来测试config函数
    if ($test_groomid > 0) {
        echo "<span class='warn'>需要数据库连接来测试实际的config函数</span><br>";
    } else {
        // groomid = 0时应该返回默认文件
        $result = GAME_ROOT . "gamedata/cache/npc_1.php";
        echo "<span class='info'>预期返回: {$result}</span><br>";
        if (file_exists($result)) {
            echo "<span class='pass'>✓ 默认文件存在</span><br>";
        } else {
            echo "<span class='fail'>✗ 默认文件不存在</span><br>";
        }
    }
}

echo "<h2>建议</h2>";
echo "<p class='info'>1. 确保RuleSet目录和文件存在</p>";
echo "<p class='info'>2. 检查房间创建时groomid是否正确设置</p>";
echo "<p class='info'>3. 验证数据库中gruleset字段是否正确保存</p>";

?>
