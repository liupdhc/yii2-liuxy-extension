<?php
/**
 * FileName: ArrayHelper.php
 * Author: liupeng
 * Date: 12/24/15
 */

namespace yii\liuxy\helpers;


class ArrayHelper extends \yii\helpers\ArrayHelper {

    /**
     * 将数组转换成对象属性
     * @param $obj 重写了__set方法，支持动态设置属性的对象
     * @param $arr
     */
    public static function toObject(&$obj, $arr) {
        if (is_object($obj) && is_array($arr)) {
            foreach($arr as $key=>$val) {
                $obj->$key = $val;
            }
        }
    }
}