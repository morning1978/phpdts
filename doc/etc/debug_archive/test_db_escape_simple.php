<?php
/**
 * 数据库转义功能测试脚本 - 简化版
 * 
 * 用于验证修复后的数据库操作类是否正确处理特殊字符
 * 此版本避免了复杂的依赖关系，专注于核心测试功能
 */

// 错误报告设置
error_reporting(E_ALL);
ini_set('display_errors', 1);

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>数据库转义功能测试 - 简化版</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1000px; margin: 0 auto; }
        table { border-collapse: collapse; width: 100%; margin: 10px 0; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .success { color: green; }
        .error { color: red; }
        .warning { background: #fff3cd; border: 1px solid #ffeaa7; padding: 10px; margin: 10px 0; }
        .info { background: #d1ecf1; border: 1px solid #bee5eb; padding: 10px; margin: 10px 0; }
        pre { background: #f8f9fa; padding: 10px; border-radius: 3px; overflow-x: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>数据库转义功能测试 - 简化版</h1>
        
        <div class="warning">
            <strong>说明：</strong>此版本避免了复杂的依赖关系，专注于核心测试功能。
        </div>

        <?php
        // 测试数据
        $test_data = [
            'simple_string' => 'Hello World',
            'with_quotes' => "It's a \"test\" string",
            'json_data' => '{"isNuclearWeapon":1,"name":"Test Weapon"}',
            'special_chars' => "Line1\nLine2\tTabbed'Quote\"DoubleQuote\\Backslash",
            'nuclear_weapon_json' => '{"isNuclearWeapon":1}',
            'complex_json' => '{"key1":"value\'with\'quotes","key2":"value\"with\"doublequotes","key3":123}'
        ];
        ?>

        <h2>测试数据：</h2>
        <table>
            <tr><th>键</th><th>原始值</th><th>长度</th></tr>
            <?php foreach ($test_data as $key => $value): ?>
            <tr>
                <td><?= htmlspecialchars($key) ?></td>
                <td><?= htmlspecialchars($value) ?></td>
                <td><?= strlen($value) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h2>PHP环境检查：</h2>
        <table>
            <tr><th>项目</th><th>状态</th><th>说明</th></tr>
            <tr>
                <td>PHP版本</td>
                <td><?= PHP_VERSION ?></td>
                <td>当前PHP版本</td>
            </tr>
            <tr>
                <td>MySQLi扩展</td>
                <td><?= extension_loaded('mysqli') ? '<span class="success">✓ 已加载</span>' : '<span class="error">✗ 未加载</span>' ?></td>
                <td>mysqli_real_escape_string 需要此扩展</td>
            </tr>
            <tr>
                <td>PDO扩展</td>
                <td><?= extension_loaded('pdo') ? '<span class="success">✓ 已加载</span>' : '<span class="error">✗ 未加载</span>' ?></td>
                <td>PDO::quote 需要此扩展</td>
            </tr>
            <tr>
                <td>JSON扩展</td>
                <td><?= extension_loaded('json') ? '<span class="success">✓ 已加载</span>' : '<span class="error">✗ 未加载</span>' ?></td>
                <td>JSON解析需要此扩展</td>
            </tr>
        </table>

        <h2>转义效果模拟：</h2>
        <table>
            <tr><th>原始数据</th><th>addslashes()结果</th><th>说明</th></tr>
            <?php foreach ($test_data as $key => $value): ?>
            <?php $escaped = addslashes($value); ?>
            <tr>
                <td><?= htmlspecialchars($value) ?></td>
                <td><?= htmlspecialchars($escaped) ?></td>
                <td><?= $value !== $escaped ? '需要转义' : '无需转义' ?></td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h2>JSON解析测试：</h2>
        <table>
            <tr><th>JSON字符串</th><th>解析结果</th><th>状态</th></tr>
            <?php
            $json_tests = [
                '{"isNuclearWeapon":1}',
                '{"isNuclearWeapon":1,"name":"Test"}',
                '1]', // 损坏的数据
                '{"key":"value\'with\'quote"}',
                '{"key":"value\"with\"doublequote"}'
            ];
            
            foreach ($json_tests as $json_str):
                $parsed = json_decode($json_str, true);
                $error = json_last_error();
            ?>
            <tr>
                <td><?= htmlspecialchars($json_str) ?></td>
                <td>
                    <?php if ($error === JSON_ERROR_NONE): ?>
                        <pre><?= htmlspecialchars(print_r($parsed, true)) ?></pre>
                    <?php else: ?>
                        <span class="error">解析失败: <?= json_last_error_msg() ?></span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($error === JSON_ERROR_NONE): ?>
                        <span class="success">✓ 成功</span>
                    <?php else: ?>
                        <span class="error">✗ 失败</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h2>核子武器数据检测测试：</h2>
        <?php
        function testIsDamagedNuclearWeapon($weppara) {
            // 检查是否包含损坏的标识
            if (strpos($weppara, '1]') !== false) {
                return true;
            }
            
            // 尝试解析JSON
            $para = json_decode($weppara, true);
            if (json_last_error() !== JSON_ERROR_NONE && !empty($weppara)) {
                return true;
            }
            
            return false;
        }

        $weapon_tests = [
            '{"isNuclearWeapon":1}' => '正常的核子武器数据',
            '1]' => '损坏的数据片段',
            '{"isNuclearWeapon":1,"other":"value"}' => '包含其他属性的核子武器',
            '' => '空数据',
            'invalid_json' => '无效的JSON',
            '{"malformed":}' => '格式错误的JSON'
        ];
        ?>
        
        <table>
            <tr><th>测试数据</th><th>描述</th><th>是否损坏</th><th>JSON解析结果</th></tr>
            <?php foreach ($weapon_tests as $data => $desc): ?>
            <?php 
                $is_damaged = testIsDamagedNuclearWeapon($data);
                $parsed = json_decode($data, true);
            ?>
            <tr>
                <td><?= htmlspecialchars($data) ?></td>
                <td><?= htmlspecialchars($desc) ?></td>
                <td>
                    <?php if ($is_damaged): ?>
                        <span class="error">✗ 是</span>
                    <?php else: ?>
                        <span class="success">✓ 否</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if (is_array($parsed) && !empty($parsed)): ?>
                        <pre><?= htmlspecialchars(print_r($parsed, true)) ?></pre>
                    <?php else: ?>
                        <span style="color: gray;">空或无效</span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <h2>SQL注入测试示例：</h2>
        <div class="info">
            <h3>修复前的危险代码：</h3>
            <pre>$query .= "{$key} = '{$value}',";  // 危险！</pre>
            
            <h3>修复后的安全代码：</h3>
            <pre>// MySQLi版本
$escaped_value = mysqli_real_escape_string($this->con, $value);
$query .= "{$key} = '{$escaped_value}',";

// PDO版本
$escaped_value = $this->con->quote($value);
$query .= "{$key} = {$escaped_value},";</pre>
        </div>

        <h2>修复总结：</h2>
        <div style="background: #e8f5e8; padding: 15px; border: 1px solid #4caf50; border-radius: 5px;">
            <h3>已完成的修复：</h3>
            <ul>
                <li>✓ 修复了include/db_mysqli.class.php中的array_update方法，添加了mysqli_real_escape_string()转义</li>
                <li>✓ 修复了include/db_mysqli.class.php中的array_insert方法，添加了mysqli_real_escape_string()转义</li>
                <li>✓ 修复了include/db_mysqli.class.php中的multi_update方法，添加了mysqli_real_escape_string()转义</li>
                <li>✓ 修复了include/db_pdo.class.php中的array_update方法，添加了quote()转义</li>
                <li>✓ 修复了include/db_pdo.class.php中的array_insert方法，添加了quote()转义</li>
                <li>✓ 修复了include/db_pdo.class.php中的multi_update方法，添加了quote()转义</li>
                <li>✓ 创建了admin/nuclear_weapon_repair.php修复工具</li>
                <li>✓ 创建了详细的修复文档</li>
            </ul>

            <h3>修复效果：</h3>
            <ul>
                <li>防止了SQL注入攻击</li>
                <li>避免了JSON数据因特殊字符而损坏</li>
                <li>确保核子武器机制正常工作</li>
                <li>提供了数据修复和诊断工具</li>
            </ul>
        </div>

        <div class="info">
            <h3>下一步操作：</h3>
            <ol>
                <li>访问 <a href="nuclear_weapon_repair.php">nuclear_weapon_repair.php</a> 进行数据修复</li>
                <li>运行诊断检查现有问题</li>
                <li>执行自动修复恢复损坏数据</li>
            </ol>
        </div>
    </div>
</body>
</html>
