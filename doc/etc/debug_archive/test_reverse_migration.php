<?php
/**
 * 反向迁移功能测试文件
 * Test file for reverse migration functionality
 */

define('CURSCRIPT', 'test_reverse_migration');

// 包含必要的文件
require './include/common.inc.php';
require './include/masterslave.func.php';

// 添加基本的访问控制 - 只允许管理员访问测试页面
if(!$cuser || !$udata || $udata['groupid'] < 9) {
    exit('Access Denied - 仅管理员可访问测试页面');
}

echo "<h2>反向迁移功能测试</h2>";
echo "<p style='color: #666; font-size: 12px;'>当前登录用户：{$cuser} (管理员权限)</p>";

// 检查当前配置
echo "<h3>当前配置状态：</h3>";
echo "Slave Level: $slave_level<br>";
echo "Master Server Name: $master_server_name<br>";
echo "Master DB Host: $master_dbhost<br>";
echo "Master DB Name: $master_dbname<br>";

// 检查是否为反向迁移模式
if(is_reverse_migration_mode()) {
    echo "<span style='color: green;'>✓ 当前处于反向迁移模式</span><br>";
    
    // 测试远端从数据库连接
    echo "<h3>远端从数据库连接测试：</h3>";
    $slave_db = connect_master_db(); // 复用连接函数，实际连接的是远端从数据库
    if($slave_db) {
        echo "<span style='color: green;'>✓ 远端从数据库连接成功</span><br>";

        // 查询远端从数据库用户数量
        $result = $slave_db->query("SELECT COUNT(*) as count FROM {$master_tablepre}users");
        if($result && $slave_db->num_rows($result)) {
            $data = $slave_db->fetch_array($result);
            echo "远端从数据库用户数量: {$data['count']}<br>";
        }

        // 查询远端从数据库玩家数量
        $result = $slave_db->query("SELECT COUNT(*) as count FROM {$master_tablepre}players WHERE type = 0");
        if($result && $slave_db->num_rows($result)) {
            $data = $slave_db->fetch_array($result);
            echo "远端从数据库玩家角色数量: {$data['count']}<br>";
        }
    } else {
        echo "<span style='color: red;'>✗ 远端从数据库连接失败</span><br>";
    }
    
    // 检查反向迁移表
    echo "<h3>反向迁移表状态：</h3>";
    create_reverse_migration_table_if_not_exists();
    $result = $db->query("SHOW TABLES LIKE '{$gtablepre}reverse_migration'");
    if($db->num_rows($result)) {
        echo "<span style='color: green;'>✓ 反向迁移表已存在</span><br>";
        
        // 查询已迁移的记录
        $result = $db->query("SELECT COUNT(*) as count FROM {$gtablepre}reverse_migration");
        if($result && $db->num_rows($result)) {
            $data = $db->fetch_array($result);
            echo "已记录的反向迁移数量: {$data['count']}<br>";
        }
    } else {
        echo "<span style='color: red;'>✗ 反向迁移表不存在</span><br>";
    }
    
    // 显示测试表单
    echo "<h3>反向迁移测试：</h3>";
    if(isset($_POST['test_reverse_migrate'])) {
        $test_local_username = $_POST['test_local_username'];
        $test_remote_username = $_POST['test_remote_username'];
        $test_remote_password = $_POST['test_remote_password'];
        $test_target_username = $_POST['test_target_username'] ?: $test_remote_username;

        if($test_local_username && $test_remote_username && $test_remote_password) {
            echo "<h4>反向迁移测试结果:</h4>";
            $migrate_result = reverse_migrate_user($test_local_username, $test_remote_username, $test_remote_password, $test_target_username);
            if($migrate_result['success']) {
                echo "<span style='color: green;'>✓ " . htmlspecialchars($migrate_result['message']) . "</span><br>";
            } else {
                echo "<span style='color: red;'>✗ " . htmlspecialchars($migrate_result['message']) . "</span><br>";
            }
        } else {
            echo "<span style='color: red;'>✗ 请输入完整的用户名和密码信息</span><br>";
        }
    }

    echo '<form method="post">';
    echo '<table border="1" style="margin: 10px 0;">';
    echo '<tr><td>本地用户名:</td><td><input type="text" name="test_local_username" placeholder="本地用户名" required></td></tr>';
    echo '<tr><td>远端用户名:</td><td><input type="text" name="test_remote_username" placeholder="远端从服务器用户名" required></td></tr>';
    echo '<tr><td>远端密码:</td><td><input type="password" name="test_remote_password" placeholder="远端从服务器密码" required></td></tr>';
    echo '<tr><td>目标用户名:</td><td><input type="text" name="test_target_username" placeholder="留空则使用远端用户名"></td></tr>';
    echo '<tr><td colspan="2"><input type="submit" name="test_reverse_migrate" value="测试反向迁移"></td></tr>';
    echo '</table>';
    echo '</form>';
    
} else {
    echo "<span style='color: orange;'>⚠ 当前不是反向迁移模式 (slave_level = $slave_level)</span><br>";
    echo "要启用反向迁移功能，请将 config.inc.php 中的 \$slave_level 设置为 -1<br>";
}

// 查询本地数据库状态
echo "<h3>本地数据库状态：</h3>";
$result = $db->query("SELECT COUNT(*) as count FROM {$gtablepre}users");
if($result && $db->num_rows($result)) {
    $data = $db->fetch_array($result);
    echo "本地用户数量: {$data['count']}<br>";
}

$result = $db->query("SELECT COUNT(*) as count FROM {$tablepre}players WHERE type = 0");
if($result && $db->num_rows($result)) {
    $data = $db->fetch_array($result);
    echo "本地玩家角色数量: {$data['count']}<br>";
}

echo "<br><a href='admin.php'>返回管理后台</a>";
?>
