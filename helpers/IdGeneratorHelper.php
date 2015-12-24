<?php
/**
 * FileName: IdGeneratorHelper.php
 * Author: liupeng
 * Date: 10/22/15
 */

namespace yii\liuxy\helpers;

use Yii;
use yii\liuxy\gearman\Client;

/**
 * 基于cache和gearman的ID生成器,需要在外部启动一个标识名为“common/id/index”的worker函数
 * 支持异步持久化
 * Class IdGeneratorHelper
 * @package yii\liuxy\helpers
 */
class IdGeneratorHelper {
    const PREFIX = 'id.generator';
    /**
     * @param $category
     * @param string $cacheName
     */
    public static function get($category) {
        $key = self::PREFIX.$category;
        if (!Yii::$app->cache->exists($key)) {
            $item = Yii::$app->db->createCommand('select category,value from tids where category=:category',
                [':category'=>$category])->queryOne();
            if ($item) {
                Yii::$app->cache->set($key, $item['value']);
            } else {
                $ret = Yii::$app->db->createCommand()->insert('tids',[
                    'category'=>$category,'value'=>1
                ]);
                if ($ret) {
                    Yii::$app->cache->set($key, 0);
                }
            }
        }

        $ret = Yii::$app->cache->increment($key);
        if ($ret) {
            //通知异步消息队列累加
            $idClient = new Client('id');
            $idClient->doBackground('common/id/execute', json_encode([
                'tids',
                ['value'=>1],
                ['category'=>$category]
            ]));
        }
        return $ret;
    }
}