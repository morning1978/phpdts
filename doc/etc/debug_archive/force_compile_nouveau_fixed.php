<?php
// 强制编译nouveau模板的所有文件 - 修复版本
define('CURSCRIPT', 'force_compile');
require './include/common.inc.php';

echo "<pre>";
echo "=== FORCE COMPILE NOUVEAU TEMPLATES (FIXED) ===\n\n";

// 强制设置为nouveau模板
$nouveau_template_id = 2;
$nouveau_template_dir = './templates/nouveau';

echo "Forcing compilation for NOUVEAU template:\n";
echo "Template ID: $nouveau_template_id\n";
echo "Template Dir: $nouveau_template_dir\n";
echo "GAME_ROOT: " . GAME_ROOT . "\n\n";

// 检查nouveau模板目录 - 修复路径拼接
$nouveau_dir = GAME_ROOT . $nouveau_template_dir;
echo "Nouveau directory: $nouveau_dir\n";
echo "Directory exists: " . (is_dir($nouveau_dir) ? 'YES' : 'NO') . "\n";

// 如果还是不存在，尝试其他路径
if (!is_dir($nouveau_dir)) {
    $nouveau_dir_alt = GAME_ROOT . './templates/nouveau';
    echo "Alternative path: $nouveau_dir_alt\n";
    echo "Alternative exists: " . (is_dir($nouveau_dir_alt) ? 'YES' : 'NO') . "\n";
    
    if (is_dir($nouveau_dir_alt)) {
        $nouveau_dir = $nouveau_dir_alt;
        echo "Using alternative path.\n";
    } else {
        echo "ERROR: Nouveau template directory not found!\n";
        echo "Checked paths:\n";
        echo "  - $nouveau_dir\n";
        echo "  - $nouveau_dir_alt\n";
        exit;
    }
}

echo "\n";

// 获取所有.htm文件
$htm_files = glob($nouveau_dir . '/*.htm');
echo "Found " . count($htm_files) . " .htm files in nouveau directory:\n";
foreach ($htm_files as $file) {
    echo "  " . basename($file) . "\n";
}
echo "\n";

if (count($htm_files) == 0) {
    echo "ERROR: No .htm files found in nouveau directory!\n";
    exit;
}

require_once GAME_ROOT.'./include/template.func.php';

$success_count = 0;
$error_count = 0;

foreach ($htm_files as $htm_file) {
    $template_name = basename($htm_file, '.htm');
    echo "Compiling: $template_name.htm\n";
    
    try {
        // 强制编译
        parse_template($template_name, $nouveau_template_id, $nouveau_template_dir);
        
        // 检查编译结果
        $compiled_file = GAME_ROOT.'./gamedata/templates/'.$nouveau_template_id.'_'.$template_name.'.tpl.php';
        if (file_exists($compiled_file)) {
            echo "  SUCCESS: " . basename($compiled_file) . " (" . filesize($compiled_file) . " bytes)\n";
            $success_count++;
        } else {
            echo "  ERROR: Compiled file not created at $compiled_file\n";
            $error_count++;
        }
        
    } catch (Exception $e) {
        echo "  ERROR: " . $e->getMessage() . "\n";
        $error_count++;
    } catch (Error $e) {
        echo "  FATAL: " . $e->getMessage() . "\n";
        $error_count++;
    }
}

echo "\n=== COMPILATION SUMMARY ===\n";
echo "Total files processed: " . count($htm_files) . "\n";
echo "Successful compilations: $success_count\n";
echo "Failed compilations: $error_count\n\n";

// 列出所有nouveau编译文件
echo "=== NOUVEAU COMPILED FILES ===\n";
$template_dir = GAME_ROOT . './gamedata/templates/';
echo "Template directory: $template_dir\n";
echo "Directory exists: " . (is_dir($template_dir) ? 'YES' : 'NO') . "\n";

if (is_dir($template_dir)) {
    $files = scandir($template_dir);
    $nouveau_files = array_filter($files, function($file) use ($nouveau_template_id) {
        return strpos($file, $nouveau_template_id . '_') === 0;
    });
    
    if (count($nouveau_files) > 0) {
        foreach ($nouveau_files as $file) {
            $filepath = $template_dir . $file;
            echo "$file (" . filesize($filepath) . " bytes)\n";
        }
    } else {
        echo "(No nouveau compiled files found)\n";
    }
} else {
    echo "(Template directory does not exist)\n";
}

echo "\n=== Force compilation completed ===\n";
echo "</pre>";
?>
