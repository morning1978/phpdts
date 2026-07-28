<?php
// 测试nouveau模板编译功能 - 修复版本
define('CURSCRIPT', 'test');
require './include/common.inc.php';

echo "<pre>";
echo "=== NOUVEAU TEMPLATE COMPILATION TEST (FIXED) ===\n\n";

// 显示当前配置
echo "Current Configuration:\n";
echo "TEMPLATEID: " . TEMPLATEID . "\n";
echo "TPLDIR: " . TPLDIR . "\n";
echo "tplrefresh: " . $tplrefresh . "\n";
echo "GAME_ROOT: " . GAME_ROOT . "\n\n";

// 检查gamedata/templates目录
echo "Checking gamedata/templates directory:\n";
$template_dir = GAME_ROOT . './gamedata/templates/';
echo "Template directory: " . $template_dir . "\n";
echo "Directory exists: " . (is_dir($template_dir) ? 'YES' : 'NO') . "\n";
echo "Directory writable: " . (is_writable($template_dir) ? 'YES' : 'NO') . "\n\n";

// 测试不同模板ID的编译
$test_templates = ['header', 'game', 'index'];
$test_template_ids = [1, 2]; // 默认模板和nouveau模板

foreach ($test_template_ids as $tid) {
    $tdir = ($tid == 1) ? './templates/default' : './templates/nouveau';
    echo "=== Testing Template ID: $tid ($tdir) ===\n";
    
    foreach ($test_templates as $template_name) {
        echo "\nTesting template: $template_name\n";
        
        // 检查源文件 - 修复路径拼接
        $source_file = GAME_ROOT . $tdir . '/' . $template_name . '.htm';
        echo "Source file: $source_file\n";
        echo "Source exists: " . (file_exists($source_file) ? 'YES' : 'NO') . "\n";
        
        if (file_exists($source_file)) {
            // 尝试编译
            require_once GAME_ROOT.'./include/template.func.php';
            
            try {
                error_reporting(E_ALL);
                ini_set('display_errors', 1);
                
                parse_template($template_name, $tid, $tdir);
                echo "Compilation: SUCCESS\n";
                
                // 检查编译后的文件
                $compiled_file = GAME_ROOT.'./gamedata/templates/'.$tid.'_'.$template_name.'.tpl.php';
                echo "Compiled file: $compiled_file\n";
                echo "Compiled exists: " . (file_exists($compiled_file) ? 'YES' : 'NO') . "\n";
                
                if (file_exists($compiled_file)) {
                    echo "File size: " . filesize($compiled_file) . " bytes\n";
                }
                
            } catch (Exception $e) {
                echo "Compilation ERROR: " . $e->getMessage() . "\n";
            } catch (Error $e) {
                echo "Compilation FATAL: " . $e->getMessage() . "\n";
            }
        } else {
            echo "Source file not found, skipping compilation.\n";
        }
    }
    echo "\n";
}

// 列出所有编译后的文件
echo "=== All compiled template files ===\n";
if (is_dir($template_dir)) {
    $files = scandir($template_dir);
    $found_files = false;
    foreach ($files as $file) {
        if ($file != '.' && $file != '..') {
            $filepath = $template_dir . $file;
            echo "$file (" . filesize($filepath) . " bytes)\n";
            $found_files = true;
        }
    }
    if (!$found_files) {
        echo "(No compiled files found)\n";
    }
} else {
    echo "(Template directory does not exist)\n";
}

// 额外检查：显示所有模板目录的状态
echo "\n=== Template Directories Status ===\n";
$template_dirs = ['default', 'nouveau', 'luluxia'];
foreach ($template_dirs as $dir) {
    $dir_path = GAME_ROOT . "./templates/$dir";
    echo "Template '$dir':\n";
    echo "  Path: $dir_path\n";
    echo "  Exists: " . (is_dir($dir_path) ? 'YES' : 'NO') . "\n";
    
    if (is_dir($dir_path)) {
        $htm_files = glob($dir_path . '/*.htm');
        echo "  .htm files: " . count($htm_files) . "\n";
        if (count($htm_files) > 0 && count($htm_files) <= 10) {
            foreach ($htm_files as $file) {
                echo "    " . basename($file) . "\n";
            }
        } elseif (count($htm_files) > 10) {
            echo "    (Too many files to list: " . count($htm_files) . ")\n";
        }
    }
    echo "\n";
}

echo "=== Test completed ===\n";
echo "</pre>";
?>
