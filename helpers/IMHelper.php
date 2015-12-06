<?php
/**
 * Created by PhpStorm.
 * User: 80124191
 * Date: 2015-11-18
 * Time: 18:55
 */

namespace yii\liuxy\helpers;


class IMHelper
{

    public static function getIMPassword($userId){
        return strtolower(sha1($userId));
    }
    /**
     * 根据用户UID转换得IM账密
     * @param $userId
     * @return array [IMUID, IMPASSWD]
     */
    public static function UserIdToIM($userId){
        if(!is_numeric($userId)){
            return $userId;
        }
        if(defined('YII_ENV') && YII_ENV === 'prod'){
            return $userId;
        }else{
            return 't'.$userId;
        }
    }

    /**
     * 根据IM账号还原用户UID
     * @param $imId
     * @return string userId
     */
    public static function IMToUserID($imId){
        if(preg_match('/^t\d+$/', $imId)){
            return trim($imId, 't');
        }else{
            return $imId;
        }
    }
}