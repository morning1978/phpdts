# PHPDTS Agent Guidelines

This document provides guidance for AI coding agents working in the PHPDTS (PHP Battle Royale) codebase.

## Project Overview

PHPDTS is a PHP-based web game emulating Battle Royale gameplay. It uses vanilla PHP with MySQL database, custom template system, and no framework dependencies.

**Tech Stack:** PHP 7.4/8.2, MySQL 5.7+, HTML/CSS/JavaScript (vanilla), PDO/MySQLi

---

## Build/Run/Test Commands

### Development Server

```bash
# Start PHP built-in server (recommended for development)
php -S localhost:8080 -t .

# Alternative using Yii (if composer dependencies installed)
composer install
./yii serve -t .
```

### Database Operations

```bash
# Database initialization - run via browser
# Navigate to: http://localhost:8080/install.php

# Import database structure
mysql -u root -p dbname < gamedata/sql/all_forInstall.sql
```

### Bot/Daemon Process

```bash
# Enable game bot daemon
bash ./bot/bot_enable.sh

# Run with nohup for persistence
nohup bash ./bot/bot_enable.sh &
```

### Testing

This project does not have a formal test framework. Manual testing is done by:
1. Running the game in browser
2. Using devtools.php for debugging
3. Checking `$log` variable output in-game

For ad-hoc PHP syntax checking:
```bash
# Check PHP syntax on a single file
php -l include/game/battle.func.php

# Check all PHP files
find . -name "*.php" -exec php -l {} \;
```

### Configuration Cache

```bash
# Regenerate configuration after modifying config files
composer dump-autoload
```

---

## Code Style Guidelines

### File Organization

```
phpdts/
├── include/              # Core PHP libraries
│   ├── common.inc.php    # Main initialization (load first)
│   ├── global.func.php   # Global utility functions
│   ├── db_*.class.php    # Database abstraction classes
│   ├── game/             # Game logic modules
│   └── admin/            # Admin panel functions
├── gamedata/             # Runtime data & configs
│   ├── cache/            # Configuration cache files
│   ├── sql/              # SQL schema files
│   └── templates/        # Compiled template cache
├── templates/            # Frontend templates (default/, nouveau/)
├── bot/                  # Bot daemon scripts
└── *.php                 # Entry point files (game.php, admin.php, etc.)
```

### File Naming Conventions

| Type | Pattern | Example |
|------|---------|---------|
| Function library | `name.func.php` | `battle.func.php`, `search.func.php` |
| Class definition | `name.class.php` | `db_mysqli.class.php` |
| Configuration | `name_version.php` | `gamecfg_1.php`, `resources_1.php` |
| Template | `name.htm` | `game.htm`, `battle.htm` |
| SQL schema | `table.sql` | `players.sql`, `all.sql` |

### PHP File Header

Every PHP file must start with:

```php
<?php

if (!defined('IN_GAME')) {
    exit('Access Denied');
}
```

### Variable Naming

```php
// Local variables: snake_case
$player_name = 'example';
$game_state = 0;

// Constants: UPPER_CASE with underscores
define('GAME_ROOT', './');
define('MAX_PLAYERS', 100);
define('IN_GAME', TRUE);

// Global variables: descriptive names, use global keyword
global $db, $tablepre, $gtablepre, $pdata, $log, $now;

// Table prefix pattern
$tablepre = 'acbra3_';  // Becomes: acbra3_players, acbra3_users
```

### Function Definitions

```php
// Use descriptive names, include global dependencies explicitly
function itemuse($itmn, &$data = NULL) {
    global $mode, $log, $db, $tablepre, $now;
    
    if (!isset($data)) {
        global $pdata;
        $data = &$pdata;
    }
    extract($data, EXTR_REFS);
    
    // Function logic...
    return $result;
}
```

### Database Operations

```php
// Standard query pattern
$result = $db->query("SELECT * FROM {$tablepre}players WHERE type = 0");

// Fetch results
while ($row = $db->fetch_array($result)) {
    // Process $row
}

// Use string replacement for table prefixes in SQL files
$sql = str_replace(' bra_', ' ' . $tablepre, $sql);

// Avoid direct string interpolation for user input
// BAD: $db->query("SELECT * FROM players WHERE name = '$name'");
// GOOD: Use validation and escaping
```

### Error Handling

```php
// Use gexit() for fatal errors (shows error template)
gexit('Error message here', __FILE__, __LINE__);

// Use $log for in-game messages
$log .= '操作失败，请重试。<br>';
$log .= 'Operation failed, please try again.<br>';

// Error handler is set in common.inc.php
set_error_handler('gameerrorhandler');
```

### Comments (Bilingual)

```php
// Comments should be in both Chinese and English
// 中文注释 / English comment

// 获取玩家数据 / Get player data
function get_player_data($pid) {
    // ...
}

/* 
 * 战斗系统核心函数
 * Battle system core function
 */
function combat_process(&$attacker, &$defender) {
    // ...
}
```

### Template System

Templates use custom syntax in `.htm` files:

```html
<!-- Variable output -->
<span>{player_name}</span>

<!-- Conditionals -->
<!--{if $gamestate == 20}-->
<p>游戏进行中 / Game in progress</p>
<!--{/if}-->

<!-- Loops -->
<!--{loop $items $item}-->
<li>{item['name']}</li>
<!--{/loop}-->

<!-- Include sub-template -->
<!--{template header}-->

<!-- PHP code in template -->
<!--{eval echo date('Y-m-d');}-->
```

Load templates using:

```php
include template('template_name');  // Loads templates/{templateid}/template_name.htm
```

### Import/Include Pattern

```php
// Use GAME_ROOT constant for paths
require GAME_ROOT . './include/global.func.php';
require GAME_ROOT . './config.inc.php';

// Use include_once for conditional/loading of feature modules
include_once GAME_ROOT . './include/game/item.weapon.php';

// Dynamic config loading
require config('gamecfg', $gamecfg);  // Loads gamedata/cache/gamecfg_1.php
```

### Array Syntax

```php
// Use traditional array() syntax for PHP 7.4 compatibility
$config = array(
    'database' => array(
        'host' => 'localhost',
        'name' => 'phpdts'
    ),
    'game' => array(
        'max_players' => 100
    )
);

// Access with quoted keys
$value = $config['database']['host'];
```

---

## Key Global Variables

| Variable | Description |
|----------|-------------|
| `$db` | Database connection object |
| `$tablepre` | Table prefix for current room |
| `$gtablepre` | Global table prefix |
| `$pdata` | Current player data array |
| `$log` | Game log output (accumulates HTML) |
| `$now` | Current timestamp (with timezone offset) |
| `$gamestate` | Game state (0=waiting, 10=ready, 20+=running) |
| `$groomid` | Room ID (0=main room, >0=private room) |

---

## Important Patterns

### Player Data Access

```php
// Player data is extracted from $pdata
extract($pdata, EXTR_REFS);

// Item variables are dynamically named
$itm1 = $pdata['itm1'];
$itmk1 = $pdata['itmk1'];
// Or use variable variables:
$itm = &${'itm' . $itmn};
```

### Configuration Loading

```php
// config() helper returns file path
require config('resources', $gamecfg);
require config('gamecfg', $gamecfg);
require config('combatcfg', $gamecfg);
```

### Game State Machine

```
0 → 10 (ready) → 20 (started) → 30 (stopped) → 40 (combo) → end
```

---

## Changelog Practice

When making code changes, record them in `/doc/` with timestamp:

```
File: doc/YYYYMMDD-HHMMSS-change-description.txt
Content: Bilingual explanation of changes and reasoning
```

---

## Security Considerations

1. Always check `IN_GAME` constant at file start
2. Use `gstrfilter()` for user input sanitization
3. Never expose `$dbpw`, `$authkey`, or `$salt` values
4. Validate all user inputs before database operations
5. Use `htmlspecialchars()` for output escaping
