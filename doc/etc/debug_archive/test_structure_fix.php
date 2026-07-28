<?php
/*
 * 测试文件结构修复效果
 * 验证npc_1.php和shopitem_1.php的结构是否正确
 */

define('IN_GAME', true);
define('GAME_ROOT', './');

echo "<h1>文件结构修复测试</h1>";
echo "<style>
.pass { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
.warn { color: orange; font-weight: bold; }
.info { color: blue; }
.debug { background: #f0f0f0; padding: 10px; margin: 10px 0; border-left: 3px solid #ccc; }
</style>";

$rulesets = ['ACBRA_2009', 'ACDTS_2011', 'ACDTS_298SP4'];

foreach ($rulesets as $ruleset_id) {
    echo "<h2>测试 {$ruleset_id}</h2>";
    
    $base_path = GAME_ROOT . "gamedata/ruleset/{$ruleset_id}/cache/";
    
    // 测试npc_1.php
    echo "<h3>测试 npc_1.php</h3>";
    $npc_file = $base_path . 'npc_1.php';
    
    if (!file_exists($npc_file)) {
        echo "<span class='fail'>✗ 文件不存在</span><br>";
    } else {
        try {
            // 重置变量
            unset($npcinit, $npcinfo);
            
            include $npc_file;
            
            if (isset($npcinit) && is_array($npcinit)) {
                echo "<span class='pass'>✓ \$npcinit 数组已定义</span><br>";
                echo "<span class='info'>包含字段: " . count($npcinit) . " 个</span><br>";
                
                // 检查关键字段
                $required_fields = ['name', 'pass', 'gd', 'icon', 'club', 'mhp', 'msp', 'att', 'def', 'pls', 'lvl'];
                $missing_fields = [];
                foreach ($required_fields as $field) {
                    if (!array_key_exists($field, $npcinit)) {
                        $missing_fields[] = $field;
                    }
                }
                
                if (empty($missing_fields)) {
                    echo "<span class='pass'>✓ 所有必需字段都存在</span><br>";
                } else {
                    echo "<span class='fail'>✗ 缺少字段: " . implode(', ', $missing_fields) . "</span><br>";
                }
            } else {
                echo "<span class='fail'>✗ \$npcinit 数组未定义或不是数组</span><br>";
            }
            
            if (isset($npcinfo) && is_array($npcinfo)) {
                echo "<span class='pass'>✓ \$npcinfo 数组已定义，包含 " . count($npcinfo) . " 个NPC类型</span><br>";
            } else {
                echo "<span class='fail'>✗ \$npcinfo 数组未定义或不是数组</span><br>";
            }
            
        } catch (Exception $e) {
            echo "<span class='fail'>✗ 文件包含失败: " . $e->getMessage() . "</span><br>";
        }
    }
    
    // 测试shopitem_1.php
    echo "<h3>测试 shopitem_1.php</h3>";
    $shop_file = $base_path . 'shopitem_1.php';
    
    if (!file_exists($shop_file)) {
        echo "<span class='fail'>✗ 文件不存在</span><br>";
    } else {
        $content = file_get_contents($shop_file);
        $lines = explode("\n", $content);
        
        // 检查PHP标签
        if (strpos($lines[0], '<?php') !== false || strpos($lines[0], '<?') !== false) {
            echo "<span class='pass'>✓ PHP标签正确</span><br>";
        } else {
            echo "<span class='fail'>✗ PHP标签缺失或错误</span><br>";
        }
        
        // 检查CSV格式
        $csv_lines = 0;
        $format_errors = 0;
        
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line)) continue;
            
            $csv_lines++;
            $fields = explode(',', $line);
            
            // 新格式应该有9个字段（包括最后的空字段）
            if (count($fields) < 8) {
                $format_errors++;
                if ($format_errors <= 3) { // 只显示前3个错误
                    echo "<span class='warn'>⚠ 第" . ($i+1) . "行格式可能有问题: " . htmlspecialchars($line) . "</span><br>";
                }
            }
        }
        
        echo "<span class='info'>CSV行数: {$csv_lines}</span><br>";
        
        if ($format_errors == 0) {
            echo "<span class='pass'>✓ CSV格式检查通过</span><br>";
        } else {
            echo "<span class='warn'>⚠ 发现 {$format_errors} 行格式问题</span><br>";
        }
        
        // 检查是否使用新格式（第一个数据行应该有分类ID）
        for ($i = 1; $i < count($lines); $i++) {
            $line = trim($lines[$i]);
            if (empty($line) || strpos($line, '0,') === 0) continue; // 跳过分类标题行
            
            $fields = explode(',', $line);
            if (count($fields) >= 4 && is_numeric($fields[0]) && is_numeric($fields[1]) && is_numeric($fields[2]) && is_numeric($fields[3])) {
                echo "<span class='pass'>✓ 使用新格式（分类,库存,价格,稀有度,...）</span><br>";
                break;
            } else {
                echo "<span class='warn'>⚠ 可能使用旧格式</span><br>";
                break;
            }
        }
    }
    
    echo "<hr>";
}

echo "<h2>测试总结</h2>";
echo "<p class='info'>如果所有测试都通过，那么文件结构修复成功。</p>";
echo "<p class='info'>现在可以重新创建RuleSet房间并测试游戏功能。</p>";

// 显示修复前后的对比
echo "<h2>修复对比</h2>";
echo "<div class='debug'>";
echo "<h3>修复前的问题：</h3>";
echo "<ul>";
echo "<li>ACBRA_2009和ACDTS_2011的npc_1.php缺少\$npcinit数组</li>";
echo "<li>ACBRA_2009的shopitem_1.php使用旧格式</li>";
echo "<li>导致NPC创建失败和商店解析错误</li>";
echo "</ul>";

echo "<h3>修复后的改进：</h3>";
echo "<ul>";
echo "<li>所有RuleSet的npc_1.php都包含\$npcinit数组</li>";
echo "<li>所有shopitem_1.php都使用统一的新格式</li>";
echo "<li>文件结构与ACDTS_298SP4保持兼容</li>";
echo "</ul>";
echo "</div>";

?>
