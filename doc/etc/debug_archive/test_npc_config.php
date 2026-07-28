<?php
/*
 * NPC配置完整性测试脚本
 * 用于检查RuleSet中的NPC配置是否完整
 */

define('IN_GAME', true);
define('GAME_ROOT', './');

echo "<h1>NPC配置完整性测试</h1>";
echo "<style>
.pass { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
.warn { color: orange; font-weight: bold; }
.info { color: blue; }
</style>";

// 测试函数
function test_npc_config($config_file, $ruleset_name) {
    echo "<h2>测试 {$ruleset_name}</h2>";
    
    if (!file_exists($config_file)) {
        echo "<span class='fail'>✗ 配置文件不存在: {$config_file}</span><br>";
        return false;
    }
    
    // 包含配置文件
    include $config_file;
    
    if (!isset($npcinfo) || !is_array($npcinfo)) {
        echo "<span class='fail'>✗ \$npcinfo 变量未定义或不是数组</span><br>";
        return false;
    }
    
    echo "<span class='pass'>✓ 配置文件加载成功</span><br>";
    echo "<span class='info'>NPC类型数量: " . count($npcinfo) . "</span><br>";
    
    $issues = 0;
    foreach ($npcinfo as $type => $npc_data) {
        echo "<h3>NPC类型 {$type}</h3>";
        
        // 检查基本结构
        if (!is_array($npc_data)) {
            echo "<span class='fail'>✗ NPC数据不是数组</span><br>";
            $issues++;
            continue;
        }
        
        // 检查sub数组
        if (!isset($npc_data['sub'])) {
            echo "<span class='fail'>✗ 缺少 'sub' 数组</span><br>";
            $issues++;
        } elseif (!is_array($npc_data['sub'])) {
            echo "<span class='fail'>✗ 'sub' 不是数组</span><br>";
            $issues++;
        } elseif (empty($npc_data['sub'])) {
            echo "<span class='warn'>⚠ 'sub' 数组为空</span><br>";
            $issues++;
        } else {
            echo "<span class='pass'>✓ 'sub' 数组正常，包含 " . count($npc_data['sub']) . " 个子配置</span><br>";
            
            // 检查sub数组中的每个元素
            foreach ($npc_data['sub'] as $sub_index => $sub_data) {
                if (!is_array($sub_data)) {
                    echo "<span class='fail'>✗ sub[{$sub_index}] 不是数组</span><br>";
                    $issues++;
                } elseif (empty($sub_data['name'])) {
                    echo "<span class='warn'>⚠ sub[{$sub_index}] 缺少名称</span><br>";
                } else {
                    echo "<span class='info'>  - sub[{$sub_index}]: {$sub_data['name']}</span><br>";
                }
            }
        }
        
        // 检查其他必要字段
        $required_fields = ['mode', 'num', 'mhp', 'msp', 'att', 'def', 'lvl'];
        foreach ($required_fields as $field) {
            if (!isset($npc_data[$field])) {
                echo "<span class='warn'>⚠ 缺少字段: {$field}</span><br>";
            }
        }
    }
    
    if ($issues == 0) {
        echo "<span class='pass'>✓ 所有NPC配置检查通过</span><br>";
        return true;
    } else {
        echo "<span class='fail'>✗ 发现 {$issues} 个问题</span><br>";
        return false;
    }
}

// 测试默认配置
echo "<h2>测试默认NPC配置</h2>";
test_npc_config(GAME_ROOT . 'gamedata/cache/npc_1.php', '默认配置');

// 测试RuleSet配置
$rulesets = [
    'ACBRA_2009' => GAME_ROOT . 'gamedata/ruleset/ACBRA_2009/cache/npc_1.php',
    'ACDTS_2011' => GAME_ROOT . 'gamedata/ruleset/ACDTS_2011/cache/npc_1.php',
    'ACDTS_298SP4' => GAME_ROOT . 'gamedata/ruleset/ACDTS_298SP4/cache/npc_1.php'
];

foreach ($rulesets as $ruleset_name => $config_file) {
    test_npc_config($config_file, $ruleset_name);
}

echo "<h2>测试总结</h2>";
echo "<p class='info'>如果发现问题，请检查对应的NPC配置文件并修复缺失的'sub'数组。</p>";
echo "<p class='info'>修复后，除零错误应该不会再出现。</p>";

?>
