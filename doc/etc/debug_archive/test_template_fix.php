<?php
// 测试模板修复是否有效
define('CURSCRIPT', 'template_fix_test');
require './include/common.inc.php';

echo "<pre>";
echo "=== TEMPLATE FIX VERIFICATION ===\n\n";

// 显示用户信息
echo "User Information:\n";
echo "cuser: " . ($cuser ? $cuser : 'NOT LOGGED IN') . "\n";
echo "udata exists: " . (isset($udata) && $udata ? 'YES' : 'NO') . "\n";

if (isset($udata) && $udata) {
    echo "u_templateid: " . (isset($udata['u_templateid']) ? $udata['u_templateid'] : 'NOT SET') . "\n";
}

// 显示系统常量
echo "\nSystem Constants:\n";
echo "TEMPLATEID: " . (defined('TEMPLATEID') ? TEMPLATEID : 'NOT DEFINED') . "\n";
echo "TPLDIR: " . (defined('TPLDIR') ? TPLDIR : 'NOT DEFINED') . "\n";

// 显示覆盖变量
echo "\nOverride Variables:\n";
global $TEMPLATEID_OVERRIDE, $TPLDIR_OVERRIDE;
echo "TEMPLATEID_OVERRIDE: " . (isset($TEMPLATEID_OVERRIDE) ? $TEMPLATEID_OVERRIDE : 'NOT SET') . "\n";
echo "TPLDIR_OVERRIDE: " . (isset($TPLDIR_OVERRIDE) ? $TPLDIR_OVERRIDE : 'NOT SET') . "\n";

// 测试template函数
echo "\n=== TESTING TEMPLATE FUNCTION ===\n";

$test_files = ['header', 'game'];

foreach ($test_files as $test_file) {
    echo "\nTesting template: $test_file\n";
    
    $template_path = template($test_file);
    echo "Returned path: $template_path\n";
    echo "File exists: " . (file_exists($template_path) ? 'YES' : 'NO') . "\n";
    
    if (file_exists($template_path)) {
        echo "File size: " . filesize($template_path) . " bytes\n";
        
        // 检查是否是nouveau模板（通过文件大小判断）
        $is_nouveau = (filesize($template_path) > 10000); // nouveau模板文件通常更大
        echo "Appears to be nouveau template: " . ($is_nouveau ? 'YES' : 'NO') . "\n";
        
        // 检查文件内容的前几行
        $content = file_get_contents($template_path);
        if (strpos($content, 'nouveau') !== false || strpos($content, 'cyber') !== false) {
            echo "Contains nouveau/cyber keywords: YES\n";
        } else {
            echo "Contains nouveau/cyber keywords: NO\n";
        }
    }
}

// 手动测试不同参数的template函数调用
echo "\n=== MANUAL TEMPLATE TESTING ===\n";

// 测试默认调用
echo "\nDefault template() call:\n";
$default_path = template('header');
echo "Path: $default_path\n";

// 测试强制nouveau模板
echo "\nForced nouveau template() call:\n";
$nouveau_path = template('header', 2, './templates/nouveau');
echo "Path: $nouveau_path\n";

// 比较两个路径
echo "\nComparison:\n";
echo "Default and nouveau paths are different: " . ($default_path != $nouveau_path ? 'YES' : 'NO') . "\n";

// 检查实际使用的模板ID和目录
echo "\n=== EFFECTIVE TEMPLATE SETTINGS ===\n";

// 模拟template函数内部逻辑
global $TEMPLATEID_OVERRIDE, $TPLDIR_OVERRIDE;
$effective_templateid = isset($TEMPLATEID_OVERRIDE) && $TEMPLATEID_OVERRIDE ? $TEMPLATEID_OVERRIDE : TEMPLATEID;
$effective_tpldir = isset($TPLDIR_OVERRIDE) && $TPLDIR_OVERRIDE ? $TPLDIR_OVERRIDE : TPLDIR;

echo "Effective TEMPLATEID: $effective_templateid\n";
echo "Effective TPLDIR: $effective_tpldir\n";

// 检查对应的编译文件
$expected_compiled = GAME_ROOT . './gamedata/templates/' . $effective_templateid . '_header.tpl.php';
echo "Expected compiled file: $expected_compiled\n";
echo "Expected file exists: " . (file_exists($expected_compiled) ? 'YES' : 'NO') . "\n";

if (file_exists($expected_compiled)) {
    echo "Expected file size: " . filesize($expected_compiled) . " bytes\n";
}

// 最终验证
echo "\n=== FINAL VERIFICATION ===\n";

if (isset($udata) && $udata && isset($udata['u_templateid']) && $udata['u_templateid'] == 2) {
    echo "User has nouveau template selected (u_templateid=2)\n";
    
    if (isset($TEMPLATEID_OVERRIDE) && $TEMPLATEID_OVERRIDE == 2) {
        echo "✓ TEMPLATEID_OVERRIDE correctly set to 2\n";
    } else {
        echo "✗ TEMPLATEID_OVERRIDE not set correctly\n";
    }
    
    if (isset($TPLDIR_OVERRIDE) && $TPLDIR_OVERRIDE == './templates/nouveau') {
        echo "✓ TPLDIR_OVERRIDE correctly set to nouveau\n";
    } else {
        echo "✗ TPLDIR_OVERRIDE not set correctly\n";
    }
    
    $header_path = template('header');
    if (strpos($header_path, '2_header.tpl.php') !== false) {
        echo "✓ template() function returns nouveau compiled file\n";
    } else {
        echo "✗ template() function does not return nouveau compiled file\n";
    }
    
    if (file_exists($header_path) && filesize($header_path) > 10000) {
        echo "✓ Nouveau template file exists and appears to be correct size\n";
        echo "SUCCESS: Nouveau template should be working!\n";
    } else {
        echo "✗ Nouveau template file missing or incorrect\n";
    }
} else {
    echo "User does not have nouveau template selected\n";
}

echo "\n=== Test completed ===\n";
echo "</pre>";
?>
