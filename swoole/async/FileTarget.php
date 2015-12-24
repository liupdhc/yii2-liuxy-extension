<?php
 /**
 * FileName: FileTarget.php
 * Author: liupeng
 * Date: 10/29/15
 */

namespace yii\liuxy\swoole\async;

/**
 * 异步写文件
 * Class FileTarget
 * @package yii\liuxy\swoole\async
 */
class FileTarget extends \yii\log\FileTarget {
    public function export() {
        $text = implode("\n", array_map([$this, 'formatMessage'], $this->messages)) . "\n";


        $self = $this;
        if ($this->enableRotation) {
            // clear stalt cache to ensure getting the real current file size and not a cached one
            // this may result in rotating twice when cached file size is used on subsequent calls
            clearstatcache();
        }
        if ($this->enableRotation && @filesize($this->logFile) > $this->maxFileSize * 1024) {
            $this->rotateFiles();
        }
        swoole_async_write($this->logFile,$text, -1, function($filename) use ($self){
            if ($self->fileMode !== null) {
                @chmod($self->logFile, $self->fileMode);
            }
        });

    }

}