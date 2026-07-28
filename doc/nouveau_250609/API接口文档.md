# PHPDTS API接口文档

## API概述

PHPDTS 提供了完整的 RESTful API 接口，支持游戏数据的获取和操作。API 主要通过 `api.php` 文件提供服务，返回 JSON 格式数据。

## 认证机制

### Cookie认证
```http
Cookie: {gtablepre}user=username; {gtablepre}pass=md5_password
```

### 会话验证
- 所有API请求都需要有效的用户会话
- 系统通过Cookie中的用户名和MD5密码进行验证
- 管理员操作需要额外的权限检查（groupid >= 要求等级）

## 响应格式

### 成功响应
```json
{
  "status": "success",
  "data": {
    // 具体数据内容
  }
}
```

### 错误响应
```json
{
  "status": "error",
  "message": "错误描述",
  "code": "错误代码"
}
```

## 核心API接口

### 1. 游戏状态API

#### 获取游戏数据
```http
GET /api.php?action=gamedata
```

**响应数据结构：**
```json
{
  "player": {
    "name": "玩家名称",
    "hp": 400,
    "mhp": 400,
    "sp": 350,
    "msp": 400,
    "att": 100,
    "def": 80,
    "pls": 12,
    "club": 1,
    "money": 1500
  },
  "area": {
    "nowArea": 12,
    "aliveNum": 45,
    "weather": 0,
    "areaList": [1, 5, 8],
    "areaNum": 3,
    "isHack": false
  },
  "itemBag": {
    "item": [
      {
        "name": "物品名称",
        "type": "物品类型",
        "num": 1
      }
    ],
    "num": 5,
    "limit": 20
  }
}
```

### 2. 战斗系统API

#### 发起攻击
```http
POST /api.php
Content-Type: application/x-www-form-urlencoded

action=attack&target_id=123&weapon_type=sword
```

#### 获取战斗结果
```http
GET /api.php?action=battle_result&battle_id=456
```

### 3. 物品系统API

#### 使用物品
```http
POST /api.php
Content-Type: application/x-www-form-urlencoded

action=use_item&item_slot=1&target_id=123
```

#### 获取背包信息
```http
GET /api.php?action=inventory
```

**响应示例：**
```json
{
  "items": [
    {
      "slot": 1,
      "name": "治疗药水",
      "type": "HH",
      "effect": "恢复100HP",
      "durability": "∞",
      "attribute": "--"
    }
  ],
  "capacity": {
    "current": 8,
    "max": 20
  }
}
```

### 4. 地图系统API

#### 移动到指定位置
```http
POST /api.php
Content-Type: application/x-www-form-urlencoded

action=move&target_location=15
```

#### 探索当前位置
```http
POST /api.php
Content-Type: application/x-www-form-urlencoded

action=search
```

#### 获取地图信息
```http
GET /api.php?action=map_info&location=12
```

### 5. 社交系统API

#### 发送聊天消息
```http
POST /api.php
Content-Type: application/x-www-form-urlencoded

action=chat&message=Hello&type=0
```

**聊天类型：**
- `0` - 全员频道
- `1` - 队伍频道
- `2` - 剧情频道
- `3` - 遗言频道

#### 获取聊天记录
```http
GET /api.php?action=chat_history&type=0&limit=50
```

### 6. 队伍系统API

#### 创建队伍
```http
POST /api.php
Content-Type: application/x-www-form-urlencoded

action=create_team&team_name=MyTeam&password=123456
```

#### 加入队伍
```http
POST /api.php
Content-Type: application/x-www-form-urlencoded

action=join_team&team_id=TEAM001&password=123456
```

## 特殊功能API

### 1. 尸体处理API

#### 获取尸体操作选项
```http
GET /api.php?action=corpse_actions&corpse_id=789
```

**响应示例：**
```json
{
  "actions": [
    {
      "key": "destory",
      "title": "销毁尸体"
    },
    {
      "key": "pickpocket",
      "title": "置入物品"
    }
  ]
}
```

### 2. 商店系统API

#### 获取商店物品列表
```http
GET /api.php?action=shop_items&shop_id=1
```

#### 购买物品
```http
POST /api.php
Content-Type: application/x-www-form-urlencoded

action=buy_item&shop_id=1&item_id=101&quantity=1
```

### 3. 成就系统API

#### 获取成就列表
```http
GET /api.php?action=achievements
```

#### 领取成就奖励
```http
POST /api.php
Content-Type: application/x-www-form-urlencoded

action=claim_achievement&achievement_id=25
```

## 错误代码

| 错误代码 | 描述 | 解决方案 |
|---------|------|----------|
| `AUTH_FAILED` | 认证失败 | 检查用户名密码 |
| `PERMISSION_DENIED` | 权限不足 | 检查用户权限 |
| `INVALID_PARAMETER` | 参数错误 | 检查请求参数 |
| `GAME_NOT_STARTED` | 游戏未开始 | 等待游戏开始 |
| `PLAYER_DEAD` | 角色已死亡 | 角色复活后重试 |
| `INSUFFICIENT_SP` | 体力不足 | 等待体力恢复 |
| `ITEM_NOT_FOUND` | 物品不存在 | 检查物品ID |
| `LOCATION_INVALID` | 位置无效 | 检查目标位置 |

## 限流机制

### 请求频率限制
- 普通API：每秒最多10次请求
- 战斗API：每秒最多5次请求
- 聊天API：每分钟最多30次请求

### 冷却时间
- 移动操作：821微秒
- 探索操作：873微秒
- 使用物品：555微秒

## 开发示例

### JavaScript示例
```javascript
// 获取游戏数据
async function getGameData() {
    try {
        const response = await fetch('/api.php?action=gamedata');
        const data = await response.json();
        
        if (data.status === 'success') {
            console.log('玩家数据:', data.data.player);
            console.log('区域信息:', data.data.area);
        } else {
            console.error('API错误:', data.message);
        }
    } catch (error) {
        console.error('请求失败:', error);
    }
}

// 使用物品
async function useItem(slot) {
    const formData = new FormData();
    formData.append('action', 'use_item');
    formData.append('item_slot', slot);
    
    try {
        const response = await fetch('/api.php', {
            method: 'POST',
            body: formData
        });
        const result = await response.json();
        
        if (result.status === 'success') {
            console.log('物品使用成功');
        } else {
            console.error('使用失败:', result.message);
        }
    } catch (error) {
        console.error('请求失败:', error);
    }
}
```

### PHP示例
```php
// API客户端示例
class PHPDTSClient {
    private $baseUrl;
    private $cookies;
    
    public function __construct($baseUrl) {
        $this->baseUrl = $baseUrl;
    }
    
    public function login($username, $password) {
        $this->cookies = "user={$username}; pass=" . md5($password);
    }
    
    public function getGameData() {
        $url = $this->baseUrl . '/api.php?action=gamedata';
        $context = stream_context_create([
            'http' => [
                'header' => "Cookie: {$this->cookies}\r\n"
            ]
        ]);
        
        $response = file_get_contents($url, false, $context);
        return json_decode($response, true);
    }
}
```

## 版本兼容性

### API版本控制
- 当前版本：v1.0
- 向后兼容：支持旧版本客户端
- 版本标识：通过HTTP头部 `API-Version` 指定

### 废弃通知
即将废弃的API会在响应头中包含 `Deprecated` 标识，建议及时更新。

---

*本文档提供了PHPDTS API的完整使用指南，帮助开发者快速集成游戏功能。*
