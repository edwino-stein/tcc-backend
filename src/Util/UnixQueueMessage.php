<?php

namespace App\Util;

class UnixQueueMessage {

    protected $key = (-1);
    protected $msqId = NULL;

    function __construct($key, $forceCreate = false){

        $this->key = $key;

        if(UnixQueueMessage::existsQueue($key)){
            $this->msqId = UnixQueueMessage::getQueue($key);
            if($forceCreate){
                UnixQueueMessage::removeQueue($this->msqId);
                $this->msqId = UnixQueueMessage::createQueue($key, 0666);
            }
        }
    }

    public function push($data) : bool {
        return msg_send($this->msqId, 12, $data, false);
    }

    public function isOk() : bool {
        return $this->msqId != NULL;
    }

    protected static function getQueue($key){
        return msg_get_queue($key);
    }

    protected static function createQueue($key, $flags){
        return msg_get_queue($key, $flags);
    }

    protected static function removeQueue($msqId){
        msg_remove_queue($msqId);
    }

    protected static function pushQueue($msqId, $msg) : bool {
        return false;
    }

    public static function existsQueue($key) : bool {
        return msg_queue_exists($key);
    }

    public static function fileToKey($file) : int {
        return ftok($file, "G");
    }
}
