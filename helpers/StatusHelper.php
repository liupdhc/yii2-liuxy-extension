<?php
/**
 * FileName: StatusHelper.php
 * Author: liupeng
 * Date: 10/22/15
 */

namespace yii\liuxy\helpers;

/**
 * 处理可兼容多状态值)(最多16个标志位)存储和判断的工具类
 * Class StatusHelper
 * @package oppo\sns\components\helpers
 */
class StatusHelper {

    /**
     * 设置状态值
     * @param $position 二进制标志位
     * @param $value
     * @param null $baseOn
     * @return int
     */
    public static function set($position, $value, $baseOn = null) {
        $t = pow(2, $position - 1);
        if($value) {
            $t = $baseOn | $t;
        } elseif ($baseOn !== null) {
            $t = $baseOn & ~$t;
        } else {
            $t = ~$t;
        }
        return $t & 0xFFFF;
    }

    /**
     * 验证状态值
     * @param $status   状态值
     * @param $position 需要验证的二进制标志位
     */
    public static function check($status, $position) {
        $t = $status & pow(2, $position - 1) ? 1 : 0;
        return $t;
    }
}