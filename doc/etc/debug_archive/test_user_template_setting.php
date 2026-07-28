<?php
// 测试用户模板设置
define('CURSCRIPT', 'user_template_test');
require './include/common.inc.php';

echo "<pre>";
echo "=== USER TEMPLATE SETTING TEST ===\n\n";

// 检查用户是否登录
if (!$cuser || !$udata) {
    echo "ERROR: User not logged in or user data not available.\n";
    echo "Please log in first to test template settings.\n";
    echo "cuser: " . ($cuser ? $cuser : 'NULL') . "\n";
    echo "udata: " . (isset($udata) ? 'SET' : 'NULL') . "\n";
    exit;
}

echo "User Information:\n";
echo "Username: $cuser\n";
echo "User ID: " . (isset($udata['uid']) ? $udata['uid'] : 'N/A') . "\n";
echo "Current u_templateid: " . (isset($udata['u_templateid']) ? $udata['u_templateid'] : 'NOT SET') . "\n";

// 显示当前系统设置
echo "\nCurrent System Settings:\n";
echo "TEMPLATEID: " . TEMPLATEID . "\n";
echo "TPLDIR: " . TPLDIR . "\n";

// 测试设置不同的模板ID
echo "\n=== TESTING TEMPLATE ID CHANGES ===\n";

// 首先备份当前设置
$original_templateid = isset($udata['u_templateid']) ? $udata['u_templateid'] : 0;

$test_ids = [0, 1, 2];

foreach ($test_ids as $test_id) {
    echo "\n--- Testing u_templateid = $test_id ---\n";
    
    // 更新数据库中的用户模板设置
    $db->query("UPDATE {$gtablepre}users SET u_templateid = '$test_id' WHERE username = '$cuser'");
    
    // 重新获取用户数据
    $test_udata = fetch_userdata_by_username($cuser);
    echo "Database updated u_templateid: " . $test_udata['u_templateid'] . "\n";
    
    // 模拟系统重新加载时的行为
    $user_templateid = intval($test_udata['u_templateid']);
    
    switch($user_templateid) {
        case 1:
            $expected_TEMPLATEID = 1;
            $expected_TPLDIR = './templates/luluxia';
            $template_name = 'LULUXIA';
            break;
        case 2:
            $expected_TEMPLATEID = 2;
            $expected_TPLDIR = './templates/nouveau';
            $template_name = 'NOUVEAU';
            break;
        default:
            $expected_TEMPLATEID = 1;
            $expected_TPLDIR = './templates/default';
            $template_name = 'DEFAULT';
            break;
    }
    
    echo "Expected template: $template_name\n";
    echo "Expected TEMPLATEID: $expected_TEMPLATEID\n";
    echo "Expected TPLDIR: $expected_TPLDIR\n";
    
    // 检查模板目录
    $template_dir = GAME_ROOT . $expected_TPLDIR;
    echo "Template directory: $template_dir\n";
    echo "Directory exists: " . (is_dir($template_dir) ? 'YES' : 'NO') . "\n";
    
    // 检查编译文件
    $compiled_header = GAME_ROOT . './gamedata/templates/' . $expected_TEMPLATEID . '_header.tpl.php';
    echo "Compiled header: $compiled_header\n";
    echo "Compiled exists: " . (file_exists($compiled_header) ? 'YES' : 'NO') . "\n";
    
    if (file_exists($compiled_header)) {
        echo "Compiled size: " . filesize($compiled_header) . " bytes\n";
    }
    
    // 如果是nouveau模板，检查是否会fallback
    if ($user_templateid == 2) {
        $nouveau_source = GAME_ROOT . './templates/nouveau/header.htm';
        echo "Nouveau source exists: " . (file_exists($nouveau_source) ? 'YES' : 'NO') . "\n";
        
        if (!is_dir($template_dir)) {
            echo "WARNING: Nouveau directory not found, would fallback to default!\n";
        } elseif (!file_exists($nouveau_source)) {
            echo "WARNING: Nouveau header.htm not found, would fallback to default!\n";
        }
    }
}

// 恢复原始设置
echo "\n=== RESTORING ORIGINAL SETTING ===\n";
$db->query("UPDATE {$gtablepre}users SET u_templateid = '$original_templateid' WHERE username = '$cuser'");
echo "Restored u_templateid to: $original_templateid\n";

// 最终测试：模拟页面重新加载
echo "\n=== SIMULATING PAGE RELOAD ===\n";

// 重新获取用户数据
$final_udata = fetch_userdata_by_username($cuser);
$final_templateid = intval($final_udata['u_templateid']);

echo "Final u_templateid from database: $final_templateid\n";

// 模拟gamedata/system.php的逻辑
switch($final_templateid) {
    case 1:
        $final_TEMPLATEID = 1;
        $final_TPLDIR = './templates/luluxia';
        break;
    case 2:
        $final_TEMPLATEID = 2;
        $final_TPLDIR = './templates/nouveau';
        break;
    default:
        $final_TEMPLATEID = 1;
        $final_TPLDIR = './templates/default';
        break;
}

echo "Final TEMPLATEID would be: $final_TEMPLATEID\n";
echo "Final TPLDIR would be: $final_TPLDIR\n";

// 测试template函数调用
$final_template_path = template('header', $final_TEMPLATEID, $final_TPLDIR);
echo "Template function would return: $final_template_path\n";
echo "File exists: " . (file_exists($final_template_path) ? 'YES' : 'NO') . "\n";

echo "\n=== Test completed ===\n";
echo "</pre>";
?>
