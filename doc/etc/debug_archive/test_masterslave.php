<?php
/**
 * 主从数据库同步功能测试脚本
 * Master-Slave Database Sync Test Script
 */

define('CURSCRIPT', 'test_masterslave');
require './include/common.inc.php';
require './include/masterslave.func.php';

echo "<h2>主从数据库同步功能测试</h2>";

// 显示当前配置
echo "<h3>当前配置</h3>";
echo "从服务器级别 (slave_level): " . $slave_level . "<br>";
echo "主服务器名称 (master_server_name): " . $master_server_name . "<br>";
echo "主数据库服务器 (master_dbhost): " . $master_dbhost . "<br>";
echo "主数据库名 (master_dbname): " . $master_dbname . "<br>";
echo "主数据库表前缀 (master_tablepre): " . $master_tablepre . "<br>";

echo "<hr>";

// 测试连接主数据库
echo "<h3>测试主数据库连接</h3>";
if($slave_level > 0) {
    $master_db = connect_master_db();
    if($master_db) {
        echo "<span style='color: green;'>✓ 主数据库连接成功</span><br>";
        
        // 测试查询主数据库用户表
        $result = $master_db->query("SELECT COUNT(*) as count FROM {$master_tablepre}users", 'SILENT');
        if(!$master_db->error() && $master_db->num_rows($result)) {
            $count_data = $master_db->fetch_array($result);
            echo "主数据库用户数量: " . $count_data['count'] . "<br>";
        } else {
            echo "<span style='color: orange;'>⚠ 无法查询主数据库用户表</span><br>";
        }
    } else {
        echo "<span style='color: red;'>✗ 主数据库连接失败</span><br>";
    }
} else {
    echo "<span style='color: blue;'>ℹ 当前为主服务器，无需连接主数据库</span><br>";
}

echo "<hr>";

// 测试同步表创建
echo "<h3>测试同步表</h3>";
create_sync_table_if_not_exists();
$result = $db->query("SHOW TABLES LIKE '{$gtablepre}user_sync'", 'SILENT');
if($db->num_rows($result)) {
    echo "<span style='color: green;'>✓ 同步表存在</span><br>";
    
    // 显示同步记录
    $sync_result = $db->query("SELECT * FROM {$gtablepre}user_sync ORDER BY sync_time DESC LIMIT 5");
    if($db->num_rows($sync_result)) {
        echo "<h4>最近的同步记录:</h4>";
        echo "<table border='1' cellpadding='5'>";
        echo "<tr><th>目标用户名</th><th>主服务器用户名</th><th>同步时间</th></tr>";
        while($sync_data = $db->fetch_array($sync_result)) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($sync_data['target_username']) . "</td>";
            echo "<td>" . htmlspecialchars($sync_data['master_username']) . "</td>";
            echo "<td>" . date('Y-m-d H:i:s', $sync_data['sync_time']) . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "暂无同步记录<br>";
    }
} else {
    echo "<span style='color: red;'>✗ 同步表不存在</span><br>";
}

echo "<hr>";

// 功能状态检查
echo "<h3>功能状态检查</h3>";
echo "是否需要自动同步: " . (should_auto_sync() ? "<span style='color: green;'>是</span>" : "<span style='color: gray;'>否</span>") . "<br>";
echo "是否直接使用主数据库: " . (should_use_master_db() ? "<span style='color: green;'>是</span>" : "<span style='color: gray;'>否</span>") . "<br>";

if($slave_level >= 1 && !empty($master_server_name)) {
    echo "主从同步功能: <span style='color: green;'>已启用</span><br>";
} else {
    echo "主从同步功能: <span style='color: gray;'>未启用</span><br>";
}

// 显示当前使用的数据库表前缀
echo "当前游戏表前缀 (gtablepre): " . $gtablepre . "<br>";
if($slave_level == 3) {
    echo "<span style='color: blue;'>ℹ 当前直接使用主数据库</span><br>";
}

echo "<hr>";

// 测试表单（仅在从服务器模式下显示）
if($slave_level >= 1 && !empty($master_server_name)) {
    echo "<h3>测试同步功能</h3>";
    echo "<form method='post' action='test_masterslave.php'>";
    echo "<table>";
    echo "<tr><td>主服务器用户名:</td><td><input type='text' name='test_master_username' size='20'></td></tr>";
    echo "<tr><td>主服务器密码:</td><td><input type='password' name='test_master_password' size='20'></td></tr>";
    echo "<tr><td>目标用户名:</td><td><input type='text' name='test_target_username' size='20' placeholder='留空使用主服务器用户名'></td></tr>";
    echo "<tr><td colspan='2'><input type='submit' name='test_sync' value='测试同步'></td></tr>";
    echo "</table>";
    echo "</form>";
    
    // 处理测试同步
    if(isset($_POST['test_sync'])) {
        $test_master_username = $_POST['test_master_username'];
        $test_master_password = $_POST['test_master_password'];
        $test_target_username = $_POST['test_target_username'] ?: $test_master_username;
        
        if($test_master_username && $test_master_password) {
            echo "<h4>同步测试结果:</h4>";
            $sync_result = sync_user_from_master($test_master_username, $test_master_password, $test_target_username);
            if($sync_result['success']) {
                echo "<span style='color: green;'>✓ " . htmlspecialchars($sync_result['message']) . "</span><br>";
            } else {
                echo "<span style='color: red;'>✗ " . htmlspecialchars($sync_result['message']) . "</span><br>";
            }
        } else {
            echo "<span style='color: red;'>✗ 请输入用户名和密码</span><br>";
        }
    }
}

echo "<hr>";
echo "<p><a href='admin.php'>返回管理界面</a> | <a href='user.php'>用户资料</a> | <a href='index.php'>游戏首页</a></p>";

?>
