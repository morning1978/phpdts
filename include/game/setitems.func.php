<?php

if(!defined('IN_GAME')) exit('Access Denied');

# 套装效果处理函数

# 处理套装效果
function process_set_item_effects(&$pa)
{
    global $log;

    # 检查玩家是否有套装效果
    if(empty($pa['clbpara']['setitems'])) return;

    # 获取套装信息
    $set_items_info = get_set_items_info();

    # 处理每个套装效果
    foreach($pa['clbpara']['setitems'] as $sid => $snums)
    {
        # 检查套装件数是否达到激活条件
        if($snums >= $set_items_info[$sid]['active'][1])
        {
            # 根据套装ID应用不同效果
            switch($sid)
            {
                case 'fs2': # 种火I套装
                    if(empty($pa['clbpara']['skill']) || !in_array('fireseed1', $pa['clbpara']['skill']))
                    {
                        getclubskill('fireseed1', $pa['clbpara']);
                        $log .= "<span class='yellow'>套装「{$set_items_info[$sid]['name']}」效果：获得技能「种火I」！</span><br>";
                    }
                    break;

                case 'fs3': # 种火II套装
                    if(empty($pa['clbpara']['skill']) || !in_array('fireseed2', $pa['clbpara']['skill']))
                    {
                        getclubskill('fireseed2', $pa['clbpara']);
                        $log .= "<span class='yellow'>套装「{$set_items_info[$sid]['name']}」效果：获得技能「种火II」！</span><br>";
                    }
                    break;

                case 'fs4': # 种火III套装
                    if(empty($pa['clbpara']['skill']) || !in_array('fireseed3', $pa['clbpara']['skill']))
                    {
                        getclubskill('fireseed3', $pa['clbpara']);
                        $log .= "<span class='yellow'>套装「{$set_items_info[$sid]['name']}」效果：获得技能「种火III」！</span><br>";
                    }
                    break;

                case 'fs5': # 种火IV套装
                    if(empty($pa['clbpara']['skill']) || !in_array('fireseed4', $pa['clbpara']['skill']))
                    {
                        getclubskill('fireseed4', $pa['clbpara']);
                        $log .= "<span class='yellow'>套装「{$set_items_info[$sid]['name']}」效果：获得技能「种火IV」！</span><br>";
                    }
                    break;

                # 其他套装效果可以在这里添加
                default:
                    # 默认不做任何处理
                    break;
            }
        }
        else
        {
            # 套装件数不足，移除对应效果
            switch($sid)
            {
                case 'fs2': # 种火I套装
                    # 检查玩家是否有该技能，且该技能不是通过学习获得的
                    if(!empty($pa['clbpara']['skill']) && in_array('fireseed1', $pa['clbpara']['skill']))
                    {
                        # 移除技能
                        lostclubskill('fireseed1', $pa['clbpara']);
                        $log .= "<span class='yellow'>失去套装「{$set_items_info[$sid]['name']}」效果：技能「种火I」消失了！</span><br>";
                    }
                    break;

                case 'fs3': # 种火II套装
                    if(!empty($pa['clbpara']['skill']) && in_array('fireseed2', $pa['clbpara']['skill']))
                    {
                        lostclubskill('fireseed2', $pa['clbpara']);
                        $log .= "<span class='yellow'>失去套装「{$set_items_info[$sid]['name']}」效果：技能「种火II」消失了！</span><br>";
                    }
                    break;

                case 'fs4': # 种火III套装
                    if(!empty($pa['clbpara']['skill']) && in_array('fireseed3', $pa['clbpara']['skill']))
                    {
                        lostclubskill('fireseed3', $pa['clbpara']);
                        $log .= "<span class='yellow'>失去套装「{$set_items_info[$sid]['name']}」效果：技能「种火III」消失了！</span><br>";
                    }
                    break;

                case 'fs5': # 种火IV套装
                    if(!empty($pa['clbpara']['skill']) && in_array('fireseed4', $pa['clbpara']['skill']))
                    {
                        lostclubskill('fireseed4', $pa['clbpara']);
                        $log .= "<span class='yellow'>失去套装「{$set_items_info[$sid]['name']}」效果：技能「种火IV」消失了！</span><br>";
                    }
                    break;

                # 其他套装效果可以在这里添加
                default:
                    # 默认不做任何处理
                    break;
            }
        }
    }
}

?>
