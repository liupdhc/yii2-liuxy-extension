<?php
/**
 * Copyright 2008-2015 OPPO Mobile Comm Corp., Ltd, All rights reserved.
 *
 * FileName: FileHelper.php
 * Author: liupeng
 * Date: 12/25/15
 */

namespace yii\liuxy\helpers;

/**
 * 扩展文件帮助类
 * Class FileHelper
 * @package yii\liuxy\helpers
 */
class FileHelper extends \yii\helpers\FileHelper {

    /**
     * 获取文件名后缀（小写形式）,不包含.符号，且支持最大10个字符串长度的后缀
     * @param string $fileName 文件名
     * @return string
     */
    public static function getExtensions($fileName) {
        return addslashes(strtolower(substr(strrchr($fileName, '.'), 1, 10)));
    }

    /**
     * 获取文件路径的文件名
     * @param $filePath
     * @return string
     */
    public static function getFileName($filePath) {
        $lastIndex = strripos($filePath, DIRECTORY_SEPARATOR);
        $fileName = substr($filePath, $lastIndex + 1);
        return substr($fileName, 0, strripos($fileName, "."));
    }
}