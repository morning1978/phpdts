<?php
/*
 * 强制编译模板文件
 * 解决模板未编译导致的RuleSet显示问题
 */

define('IN_GAME', true);
define('GAME_ROOT', './');
define('TPLDIR', './templates/default');
define('TEMPLATEID', 1);

// 包含必要文件
require_once GAME_ROOT . './include/global.func.php';
require_once GAME_ROOT . './include/template.func.php';

echo "<h1>强制编译模板文件</h1>";
echo "<style>
.pass { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
.info { color: blue; }
</style>";

// 创建模板目录
$template_dir = GAME_ROOT . './gamedata/templates/';
if (!is_dir($template_dir)) {
    if (mkdir($template_dir, 0755, true)) {
        echo "<span class='pass'>✓ 创建模板目录: {$template_dir}</span><br>";
    } else {
        echo "<span class='fail'>✗ 无法创建模板目录: {$template_dir}</span><br>";
        exit;
    }
} else {
    echo "<span class='info'>模板目录已存在: {$template_dir}</span><br>";
}

// 需要编译的模板文件列表
$templates = array(
    'header',
    'index', 
    'roomlist',
    'user',
    'game',
    'footer'
);

echo "<h2>开始编译模板文件</h2>";

foreach ($templates as $template) {
    echo "<h3>编译模板: {$template}</h3>";
    
    $tpl_file = GAME_ROOT . './templates/default/' . $template . '.htm';
    $obj_file = GAME_ROOT . './gamedata/templates/1_' . $template . '.tpl.php';
    
    echo "<span class='info'>源文件: {$tpl_file}</span><br>";
    echo "<span class='info'>目标文件: {$obj_file}</span><br>";
    
    if (!file_exists($tpl_file)) {
        echo "<span class='fail'>✗ 源文件不存在</span><br>";
        continue;
    }
    
    try {
        // 强制编译
        parse_template($template, 1, './templates/default');
        
        if (file_exists($obj_file)) {
            echo "<span class='pass'>✓ 编译成功</span><br>";
            echo "<span class='info'>文件大小: " . filesize($obj_file) . " 字节</span><br>";
        } else {
            echo "<span class='fail'>✗ 编译失败，目标文件不存在</span><br>";
        }
    } catch (Exception $e) {
        echo "<span class='fail'>✗ 编译出错: " . $e->getMessage() . "</span><br>";
    }
    
    echo "<br>";
}

echo "<h2>编译完成</h2>";
echo "<p class='info'>请刷新页面查看效果。</p>";

?>
