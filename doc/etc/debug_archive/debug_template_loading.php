<?php
// 调试模板加载过程
define('CURSCRIPT', 'debug');
require './include/common.inc.php';

echo "<pre>";
echo "=== TEMPLATE LOADING DEBUG ===\n\n";

// 显示当前用户信息
echo "User Information:\n";
echo "cuser: " . ($cuser ? $cuser : 'NOT LOGGED IN') . "\n";
echo "udata exists: " . (isset($udata) && $udata ? 'YES' : 'NO') . "\n";

if (isset($udata) && $udata) {
    echo "u_templateid: " . (isset($udata['u_templateid']) ? $udata['u_templateid'] : 'NOT SET') . "\n";
}

echo "\nSystem Constants:\n";
echo "TEMPLATEID: " . (defined('TEMPLATEID') ? TEMPLATEID : 'NOT DEFINED') . "\n";
echo "TPLDIR: " . (defined('TPLDIR') ? TPLDIR : 'NOT DEFINED') . "\n";
echo "tplrefresh: " . (isset($tplrefresh) ? $tplrefresh : 'NOT SET') . "\n";

// 测试template函数的实际行为
echo "\n=== TESTING TEMPLATE FUNCTION ===\n";

$test_files = ['header', 'game', 'index'];

foreach ($test_files as $test_file) {
    echo "\nTesting template: $test_file\n";
    
    // 直接调用template函数
    $template_path = template($test_file);
    echo "Returned path: $template_path\n";
    echo "File exists: " . (file_exists($template_path) ? 'YES' : 'NO') . "\n";
    
    if (file_exists($template_path)) {
        echo "File size: " . filesize($template_path) . " bytes\n";
        
        // 检查文件内容的前几行来确认是哪个模板
        $content = file_get_contents($template_path);
        $first_lines = implode("\n", array_slice(explode("\n", $content), 0, 5));
        echo "First few lines:\n" . htmlspecialchars($first_lines) . "\n";
    }
    
    // 检查对应的源文件
    $source_nouveau = GAME_ROOT . './templates/nouveau/' . $test_file . '.htm';
    $source_default = GAME_ROOT . './templates/default/' . $test_file . '.htm';
    
    echo "Nouveau source exists: " . (file_exists($source_nouveau) ? 'YES' : 'NO') . "\n";
    echo "Default source exists: " . (file_exists($source_default) ? 'YES' : 'NO') . "\n";
    
    // 检查编译文件
    $compiled_nouveau = GAME_ROOT . './gamedata/templates/2_' . $test_file . '.tpl.php';
    $compiled_default = GAME_ROOT . './gamedata/templates/1_' . $test_file . '.tpl.php';
    
    echo "Nouveau compiled exists: " . (file_exists($compiled_nouveau) ? 'YES' : 'NO') . "\n";
    echo "Default compiled exists: " . (file_exists($compiled_default) ? 'YES' : 'NO') . "\n";
    
    if (file_exists($compiled_nouveau) && file_exists($source_nouveau)) {
        $source_time = filemtime($source_nouveau);
        $compiled_time = filemtime($compiled_nouveau);
        echo "Source newer than compiled: " . ($source_time > $compiled_time ? 'YES' : 'NO') . "\n";
        echo "Source time: " . date('Y-m-d H:i:s', $source_time) . "\n";
        echo "Compiled time: " . date('Y-m-d H:i:s', $compiled_time) . "\n";
    }
}

// 手动测试不同templateid的template函数调用
echo "\n=== MANUAL TEMPLATE ID TESTING ===\n";

foreach ([1, 2] as $tid) {
    $tdir = ($tid == 1) ? './templates/default' : './templates/nouveau';
    echo "\nTesting with templateid=$tid, tpldir=$tdir:\n";
    
    $manual_path = template('header', $tid, $tdir);
    echo "Returned path: $manual_path\n";
    echo "File exists: " . (file_exists($manual_path) ? 'YES' : 'NO') . "\n";
    
    // 检查是否是预期的文件
    $expected_file = GAME_ROOT . './gamedata/templates/' . $tid . '_header.tpl.php';
    echo "Expected file: $expected_file\n";
    echo "Matches expected: " . ($manual_path == $expected_file ? 'YES' : 'NO') . "\n";
}

// 检查gamedata/system.php中的模板设置逻辑
echo "\n=== CHECKING SYSTEM.PHP LOGIC ===\n";

// 模拟不同的u_templateid值
$test_templateids = [0, 1, 2];

foreach ($test_templateids as $test_id) {
    echo "\nSimulating u_templateid = $test_id:\n";
    
    $user_templateid = $test_id;
    
    // 复制gamedata/system.php中的逻辑
    switch($user_templateid) {
        case 1:
            $sim_TEMPLATEID = 1;
            $sim_TPLDIR = './templates/luluxia';
            echo "Would set: LULUXIA template\n";
            break;
        case 2:
            $sim_TEMPLATEID = 2;
            $sim_TPLDIR = './templates/nouveau';
            echo "Would set: NOUVEAU template\n";
            break;
        default:
            $sim_TEMPLATEID = 1;
            $sim_TPLDIR = './templates/default';
            echo "Would set: DEFAULT template\n";
            break;
    }
    
    echo "TEMPLATEID: $sim_TEMPLATEID\n";
    echo "TPLDIR: $sim_TPLDIR\n";
    
    // 检查目录是否存在
    $dir_path = GAME_ROOT . $sim_TPLDIR;
    echo "Directory exists: " . (is_dir($dir_path) ? 'YES' : 'NO') . "\n";
    
    if (!is_dir($dir_path) && $sim_TPLDIR != './templates/default') {
        echo "Would fallback to default template\n";
    }
}

echo "\n=== Debug completed ===\n";
echo "</pre>";
?>
