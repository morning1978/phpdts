<?php
// 测试RuleSet资源文件加载是否正常工作
define('GAME_ROOT', dirname(dirname(__DIR__)) . '/');
define('CURSCRIPT', 'test');

// 包含必要的文件
require GAME_ROOT.'./config.inc.php';
require GAME_ROOT.'./include/db_mysql.class.php';
require GAME_ROOT.'./include/global.func.php';

// 模拟数据库连接
$db = new dbstuff;
$db->connect($dbhost, $dbuser, $dbpw, $dbname, $pconnect);

echo "<h1>RuleSet资源文件加载测试</h1>";
echo "<style>
.pass { color: green; font-weight: bold; }
.fail { color: red; font-weight: bold; }
.warn { color: orange; font-weight: bold; }
.info { color: blue; }
</style>";

// 测试不同的RuleSet
$rulesets = array('ACBRA_2009', 'ACDTS_2011', 'ACDTS_298SP4');

foreach ($rulesets as $ruleset_id) {
    echo "<h2>测试 {$ruleset_id}</h2>";
    
    // 模拟设置groomid
    global $groomid, $gtablepre;
    $groomid = 1; // 假设房间ID为1
    $gtablepre = $tablepre;
    
    // 创建测试房间记录
    $db->query("DELETE FROM {$gtablepre}game WHERE groomid = 1");
    $db->query("INSERT INTO {$gtablepre}game (groomid, gruleset, gamestate) VALUES (1, '{$ruleset_id}', 0)");
    
    // 测试config()函数是否正确返回RuleSet文件路径
    $resources_file = config('resources', 1);
    echo "<h3>配置文件路径测试</h3>";
    echo "<p class='info'>期望路径: gamedata/ruleset/{$ruleset_id}/cache/resources_1.php</p>";
    echo "<p class='info'>实际路径: " . str_replace(GAME_ROOT.'./', '', $resources_file) . "</p>";
    
    if (strpos($resources_file, "ruleset/{$ruleset_id}/cache/resources_1.php") !== false) {
        echo "<span class='pass'>✓ config()函数正确返回RuleSet路径</span><br>";
    } else {
        echo "<span class='fail'>✗ config()函数未返回RuleSet路径</span><br>";
    }
    
    // 测试文件是否存在
    if (file_exists($resources_file)) {
        echo "<span class='pass'>✓ RuleSet资源文件存在</span><br>";
    } else {
        echo "<span class='fail'>✗ RuleSet资源文件不存在</span><br>";
        continue;
    }
    
    // 测试文件是否可以正常加载
    echo "<h3>资源文件内容测试</h3>";
    try {
        // 保存当前变量状态
        $old_plsinfo = isset($plsinfo) ? $plsinfo : null;
        $old_xyinfo = isset($xyinfo) ? $xyinfo : null;
        $old_areainfo = isset($areainfo) ? $areainfo : null;
        $old_clubinfo = isset($clubinfo) ? $clubinfo : null;
        
        // 加载RuleSet资源文件
        include $resources_file;
        
        // 检查关键变量是否被正确加载
        if (isset($plsinfo) && is_array($plsinfo) && count($plsinfo) > 0) {
            echo "<span class='pass'>✓ \$plsinfo 加载成功 (" . count($plsinfo) . " 个地点)</span><br>";
            echo "<p class='info'>示例地点: " . $plsinfo[3] . " (索引3)</p>";
        } else {
            echo "<span class='fail'>✗ \$plsinfo 加载失败</span><br>";
        }
        
        if (isset($xyinfo) && is_array($xyinfo) && count($xyinfo) > 0) {
            echo "<span class='pass'>✓ \$xyinfo 加载成功 (" . count($xyinfo) . " 个坐标)</span><br>";
        } else {
            echo "<span class='fail'>✗ \$xyinfo 加载失败</span><br>";
        }
        
        if (isset($areainfo) && is_array($areainfo) && count($areainfo) > 0) {
            echo "<span class='pass'>✓ \$areainfo 加载成功 (" . count($areainfo) . " 个区域描述)</span><br>";
        } else {
            echo "<span class='fail'>✗ \$areainfo 加载失败</span><br>";
        }
        
        if (isset($clubinfo) && is_array($clubinfo) && count($clubinfo) > 0) {
            echo "<span class='pass'>✓ \$clubinfo 加载成功 (" . count($clubinfo) . " 个社团)</span><br>";
        } else {
            echo "<span class='fail'>✗ \$clubinfo 加载失败</span><br>";
        }
        
    } catch (Exception $e) {
        echo "<span class='fail'>✗ 资源文件加载出错: " . $e->getMessage() . "</span><br>";
    }
    
    echo "<hr>";
}

// 测试默认资源文件加载（无RuleSet）
echo "<h2>测试默认资源文件加载</h2>";
$groomid = 0; // 重置为默认房间
$resources_file = config('resources', 1);
echo "<p class='info'>默认路径: " . str_replace(GAME_ROOT.'./', '', $resources_file) . "</p>";

if (strpos($resources_file, "gamedata/cache/resources_1.php") !== false) {
    echo "<span class='pass'>✓ 默认房间正确使用默认资源文件</span><br>";
} else {
    echo "<span class='fail'>✗ 默认房间未使用默认资源文件</span><br>";
}

// 清理测试数据
$db->query("DELETE FROM {$gtablepre}game WHERE groomid = 1");

echo "<h2>测试总结</h2>";
echo "<p class='info'>如果所有测试都通过，说明RuleSet资源文件加载修复成功。</p>";
echo "<p class='info'>现在可以创建RuleSet房间并验证游戏功能。</p>";

?>
