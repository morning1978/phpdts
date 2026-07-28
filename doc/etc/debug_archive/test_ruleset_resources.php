<?php
/*
 * RuleSet资源文件完整性测试
 * 验证修复后的资源文件是否正常
 */

define('IN_GAME', true);
define('GAME_ROOT', './');

echo "<h1>RuleSet资源文件完整性测试</h1>";
echo "<style>
.pass { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
.warn { color: orange; font-weight: bold; }
.info { color: blue; }
</style>";

$rulesets = ['ACBRA_2009', 'ACDTS_2011', 'ACDTS_298SP4'];
$required_files = [
    'npc_1.php' => 'NPC配置',
    'addnpc_1.php' => 'NPC初始化配置',
    'evonpc_1.php' => '进化NPC配置',
    'present_1.php' => '礼品配置',
    'shopitem_1.php' => '商店配置',
    'resources_1.php' => '资源配置',
    'mapitem_1.php' => '地图道具配置',
    'gamecfg_1.php' => '游戏配置',
    'combatcfg_1.php' => '战斗配置'
];

foreach ($rulesets as $ruleset_id) {
    echo "<h2>测试 {$ruleset_id}</h2>";
    
    $base_path = GAME_ROOT . "gamedata/ruleset/{$ruleset_id}/cache/";
    
    foreach ($required_files as $file => $description) {
        $file_path = $base_path . $file;
        echo "<h3>{$description} ({$file})</h3>";
        
        if (!file_exists($file_path)) {
            echo "<span class='fail'>✗ 文件不存在</span><br>";
            continue;
        }
        
        $size = filesize($file_path);
        echo "<span class='pass'>✓ 文件存在 ({$size} 字节)</span><br>";
        
        // 测试文件是否可以正常包含
        try {
            ob_start();
            include $file_path;
            $output = ob_get_clean();
            
            if ($output) {
                echo "<span class='warn'>⚠ 文件包含时有输出（可能有语法错误）</span><br>";
                echo "<pre>" . htmlspecialchars($output) . "</pre>";
            } else {
                echo "<span class='pass'>✓ 文件语法正确</span><br>";
            }
            
            // 检查特定变量是否定义
            switch ($file) {
                case 'npc_1.php':
                    if (isset($npcinfo) && is_array($npcinfo)) {
                        echo "<span class='pass'>✓ \$npcinfo 变量已定义，包含 " . count($npcinfo) . " 个NPC类型</span><br>";
                    } else {
                        echo "<span class='fail'>✗ \$npcinfo 变量未定义或不是数组</span><br>";
                    }
                    break;
                    
                case 'addnpc_1.php':
                    if (isset($npcinit) && is_array($npcinit)) {
                        echo "<span class='pass'>✓ \$npcinit 变量已定义</span><br>";
                    } else {
                        echo "<span class='fail'>✗ \$npcinit 变量未定义或不是数组</span><br>";
                    }
                    
                    if (isset($anpcinfo) && is_array($anpcinfo)) {
                        echo "<span class='pass'>✓ \$anpcinfo 变量已定义，包含 " . count($anpcinfo) . " 个额外NPC类型</span><br>";
                    } else {
                        echo "<span class='fail'>✗ \$anpcinfo 变量未定义或不是数组</span><br>";
                    }
                    break;
                    
                case 'evonpc_1.php':
                    if (isset($enpcinfo) && is_array($enpcinfo)) {
                        echo "<span class='pass'>✓ \$enpcinfo 变量已定义，包含 " . count($enpcinfo) . " 个进化NPC类型</span><br>";
                    } else {
                        echo "<span class='fail'>✗ \$enpcinfo 变量未定义或不是数组</span><br>";
                    }
                    break;
            }
            
        } catch (Exception $e) {
            echo "<span class='fail'>✗ 文件包含失败: " . $e->getMessage() . "</span><br>";
        } catch (ParseError $e) {
            echo "<span class='fail'>✗ 语法错误: " . $e->getMessage() . "</span><br>";
        }
        
        // 重置变量
        unset($npcinfo, $npcinit, $anpcinfo, $enpcinfo);
        
        echo "<br>";
    }
    
    echo "<hr>";
}

echo "<h2>测试总结</h2>";
echo "<p class='info'>如果所有文件都显示为正常，那么RuleSet资源文件修复成功。</p>";
echo "<p class='info'>现在可以尝试重新创建RuleSet房间并测试游戏功能。</p>";

?>
