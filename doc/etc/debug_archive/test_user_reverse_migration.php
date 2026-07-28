<?php
/**
 * 用户端反向迁移功能测试文件
 * Test file for user-side reverse migration functionality
 */

define('CURSCRIPT', 'test_user_reverse_migration');

// 包含必要的文件
require './include/common.inc.php';
require './include/masterslave.func.php';

// 添加基本的访问控制 - 只允许管理员访问测试页面
if(!$cuser || !$udata || $udata['groupid'] < 9) {
    exit('Access Denied - 仅管理员可访问测试页面');
}

echo "<h2>用户端反向迁移功能测试</h2>";
echo "<p style='color: #666; font-size: 12px;'>当前登录用户：{$cuser} (管理员权限)</p>";

// 检查当前配置
echo "<h3>当前配置状态：</h3>";
echo "Slave Level: $slave_level<br>";
echo "Master Server Name: $master_server_name<br>";

// 检查是否为反向迁移模式
if(is_reverse_migration_mode()) {
    echo "<span style='color: green;'>✓ 当前处于反向迁移模式</span><br>";
    echo "<span style='color: blue;'>ℹ 用户可以在个人资料页面看到反向迁移功能</span><br>";
    
    // 模拟用户界面变量设置
    $show_sync_button = ($slave_level >= 1 && !empty($master_server_name));
    $show_reverse_migrate_button = (is_reverse_migration_mode() && !empty($master_server_name));
    
    echo "<h3>界面显示状态：</h3>";
    echo "显示正向同步按钮: " . ($show_sync_button && !$show_reverse_migrate_button ? "<span style='color: green;'>是</span>" : "<span style='color: red;'>否</span>") . "<br>";
    echo "显示反向迁移按钮: " . ($show_reverse_migrate_button ? "<span style='color: green;'>是</span>" : "<span style='color: red;'>否</span>") . "<br>";
    
    // 测试主数据库连接
    echo "<h3>主数据库连接测试：</h3>";
    $master_db = connect_master_db();
    if($master_db) {
        echo "<span style='color: green;'>✓ 主数据库连接成功</span><br>";
        
        // 查询主数据库用户数量
        $result = $master_db->query("SELECT COUNT(*) as count FROM {$master_tablepre}users");
        if($result && $master_db->num_rows($result)) {
            $data = $master_db->fetch_array($result);
            echo "主数据库用户数量: {$data['count']}<br>";
        }
    } else {
        echo "<span style='color: red;'>✗ 主数据库连接失败</span><br>";
    }
    
    // 检查反向迁移表
    echo "<h3>反向迁移表状态：</h3>";
    create_reverse_migration_table_if_not_exists();
    $result = $db->query("SHOW TABLES LIKE '{$gtablepre}reverse_migration'");
    if($db->num_rows($result)) {
        echo "<span style='color: green;'>✓ 反向迁移表已存在</span><br>";
        
        // 查询已迁移的记录
        $result = $db->query("SELECT * FROM {$gtablepre}reverse_migration ORDER BY sync_time DESC LIMIT 5");
        if($result && $db->num_rows($result)) {
            echo "<h4>最近的反向迁移记录：</h4>";
            echo "<table border='1' style='border-collapse: collapse;'>";
            echo "<tr><th>目标用户名</th><th>主服务器用户名</th><th>迁移时间</th></tr>";
            while($row = $db->fetch_array($result)) {
                $sync_time_formatted = date('Y-m-d H:i:s', $row['sync_time']);
                echo "<tr><td>{$row['target_username']}</td><td>{$row['master_username']}</td><td>$sync_time_formatted</td></tr>";
            }
            echo "</table>";
        } else {
            echo "暂无反向迁移记录<br>";
        }
    } else {
        echo "<span style='color: red;'>✗ 反向迁移表不存在</span><br>";
    }
    
    // 显示用户端测试表单
    echo "<h3>用户端反向迁移测试：</h3>";
    if(isset($_POST['test_user_reverse_migrate'])) {
        $test_local_username = $_POST['test_local_username'];
        $test_remote_username = $_POST['test_remote_username'];
        $test_remote_password = $_POST['test_remote_password'];
        $test_target_username = $_POST['test_target_username'] ?: $test_remote_username;

        if($test_local_username && $test_remote_username && $test_remote_password) {
            echo "<h4>用户端反向迁移测试结果:</h4>";

            // 模拟用户端调用
            $migrate_result = reverse_migrate_user($test_local_username, $test_remote_username, md5($test_remote_password), $test_target_username);
            if($migrate_result['success']) {
                echo "<span style='color: green;'>✓ " . htmlspecialchars($migrate_result['message']) . "</span><br>";

                // 检查迁移状态
                $migrate_status = get_reverse_migration_status($test_target_username);
                if($migrate_status) {
                    echo "<span style='color: blue;'>ℹ 迁移记录已创建，时间：" . date('Y-m-d H:i:s', $migrate_status['sync_time']) . "</span><br>";
                }
            } else {
                echo "<span style='color: red;'>✗ " . htmlspecialchars($migrate_result['message']) . "</span><br>";
            }
        } else {
            echo "<span style='color: red;'>✗ 请输入完整的用户名和密码信息</span><br>";
        }
    }

    echo '<form method="post">';
    echo '<table border="1" style="margin: 10px 0; border-collapse: collapse;">';
    echo '<tr><td style="padding: 5px;">本地用户名:</td><td style="padding: 5px;"><input type="text" name="test_local_username" placeholder="本地用户名" required></td></tr>';
    echo '<tr><td style="padding: 5px;">远端用户名:</td><td style="padding: 5px;"><input type="text" name="test_remote_username" placeholder="远端从服务器用户名" required></td></tr>';
    echo '<tr><td style="padding: 5px;">远端密码:</td><td style="padding: 5px;"><input type="password" name="test_remote_password" placeholder="远端从服务器密码" required></td></tr>';
    echo '<tr><td style="padding: 5px;">目标用户名:</td><td style="padding: 5px;"><input type="text" name="test_target_username" placeholder="留空则使用远端用户名"></td></tr>';
    echo '<tr><td colspan="2" style="padding: 5px; text-align: center;"><input type="submit" name="test_user_reverse_migrate" value="测试用户端反向迁移" style="padding: 5px 10px; background-color: #FF6347; color: white; border: none; border-radius: 3px;"></td></tr>';
    echo '</table>';
    echo '</form>';
    
    echo "<h3>用户界面预览：</h3>";
    echo "<div style='border: 1px solid #ccc; padding: 10px; background-color: #f9f9f9;'>";
    echo "<strong>在用户个人资料页面会显示：</strong><br>";
    echo "<div style='margin-top: 10px; padding: 15px; border: 2px solid #FF6347; background-color: #FFF5EE; border-radius: 5px; max-width: 500px;'>";
    echo "<div style='text-align: center; font-weight: bold; color: #CD5C5C; margin-bottom: 10px;'>反向迁移功能 (推送到远端从服务器)</div>";
    echo "<div style='text-align: center; font-size: 12px; color: #8B4513; margin-bottom: 10px;'>当前模式：反向迁移模式 | 目标服务器：$master_server_name</div>";
    echo "<div style='text-align: center;'>";
    echo "<input type='text' placeholder='在{$master_server_name}的用户名' style='margin: 2px; padding: 3px;'><br>";
    echo "<input type='password' placeholder='在{$master_server_name}的密码' style='margin: 2px; padding: 3px;'><br>";
    echo "<button style='margin: 5px; padding: 8px 15px; background-color: #FF6347; color: white; border: 1px solid #CD5C5C; border-radius: 3px;'>推送到{$master_server_name}</button>";
    echo "</div>";
    echo "<div style='margin-top: 10px; text-align: center; font-size: 11px; color: #888;'>注意：反向迁移将把您的本地账户数据推送到远端从服务器 {$master_server_name}，需要在远端服务器有有效账户进行身份验证。本地数据不会受影响。</div>";
    echo "</div>";
    echo "</div>";
    
} else {
    echo "<span style='color: orange;'>⚠ 当前不是反向迁移模式 (slave_level = $slave_level)</span><br>";
    echo "要启用用户端反向迁移功能，请将 config.inc.php 中的 \$slave_level 设置为 -1<br>";
    
    if($slave_level >= 1) {
        echo "<span style='color: blue;'>ℹ 当前是正向同步模式，用户可以看到正向同步功能</span><br>";
    }
}

echo "<br><a href='user.php'>查看用户个人资料页面</a> | <a href='admin.php'>返回管理后台</a>";
?>
