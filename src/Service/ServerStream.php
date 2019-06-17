<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

class ServerStream {

    public const SERVER_STATUS_STOPPED  = 'STOPPED';
    public const SERVER_STATUS_WAITTING  = 'WAITTING';
    public const SERVER_STATUS_RUNNING  = 'RUNNING';
    public const SERVER_STATUS_ERROR    = 'ERROR';

    protected $referenceFile = "";
    protected $sudoBin = '';
    protected $serviceBin = '';
    protected $ffmpegCfg = [];
    protected $serverStreamCfg = [];

    public function __construct(ParameterBagInterface $params){
        $serverParams = $params->get("server_stream");
        $linuxParams = $params->get("linux");
        $this->ffmpegCfg = $serverParams['ffmpeg'];
        $this->serverStreamCfg = $serverParams['server_stream'];
        $this->sudoBin = $linuxParams['sudo_bin'];
        $this->serviceBin = $linuxParams['service_bin'];
    }

    protected function callService($service, $action){

        $cmd = implode(
            ' ',
            [
                $this->sudoBin,
                $this->serviceBin,
                $service,
                $action
            ]
        );

        $output = [];
        exec($cmd, $output);
        return $output;
    }

    protected function readFileStatus(){

        if(!file_exists($this->serverStreamCfg['status_file'])) return ['watching' => 0];

        $data = file_get_contents($this->serverStreamCfg['status_file']);
        if(empty($data)) return ['watching' => 0];

        $data = json_decode($data, true);
        if($data == NULL) return ['watching' => 0];

        return $data;
    }

    protected function getFfmpegPid(){
        if(!file_exists($this->ffmpegCfg['pid_file'])) return (-1);
        $pid = (int) file_get_contents($this->ffmpegCfg['pid_file']);
        if(!posix_getpgid($pid)) return (-1);
        return $pid;
    }

    protected function getServerStreamPid(){
        if(!file_exists($this->serverStreamCfg['pid_file'])) return (-1);
        $pid = (int) file_get_contents($this->serverStreamCfg['pid_file']);
        if(!posix_getpgid($pid)) return (-1);
        return $pid;
    }

    protected function readDataStatus(){

        $ffmpegPid = $this->getFfmpegPid();
        $serverStreamPid = $this->getServerStreamPid();
        $serverStreamStatus = $this->readFileStatus();

        $data = [
            'ffmpeg' => $ffmpegPid,
            'serverStream' => $serverStreamPid,
            'status' => $ffmpegPid == (-1) || $serverStreamPid == (-1) ? self::SERVER_STATUS_STOPPED : self::SERVER_STATUS_RUNNING
        ];

        foreach ($serverStreamStatus as $key => $value) $data[$key] = $value;
        return $data;
    }

    protected function getStatus($ignoreCache = false){
        if($ignoreCache){
            return $this->readDataStatus();
        }
        else {
            $cache = new FilesystemAdapter();
            return $cache->get('server_stream_status', function (ItemInterface $item) {
                $data = $this->readDataStatus();
                $item->expiresAfter(10);
                return $data;
            });
        }
    }

    public function start(){

        $status = $this->getStatus(true);

        if($status['ffmpeg'] == (-1)){
            $this->callService($this->ffmpegCfg['service'], 'start');
        }

        if($status['serverStream'] == (-1)){
            $this->callService($this->serverStreamCfg['service'], 'start');
        }

        sleep(2);
        $status = $this->getStatus(true);

        return [
            'result' => $status['status'] == self::SERVER_STATUS_RUNNING,
            'data' => [
                'status' => $status['status'],
                'watching' => $status['watching']
            ]
        ];
    }

    public function stop(){

        $status = $this->getStatus(true);

        if($status['ffmpeg'] != (-1)){
            $this->callService($this->ffmpegCfg['service'], 'stop');
        }

        if($status['serverStream'] != (-1)){
            $this->callService($this->serverStreamCfg['service'], 'stop');
        }

        sleep(2);
        $status = $this->getStatus(true);

        return [
            'result' => $status['status'] == self::SERVER_STATUS_STOPPED,
            'data' => [
                'status' => $status['status'],
                'watching' => $status['watching']
            ]
        ];
    }

    public function status(){
        $status = $this->getStatus();
        return [
            'result' => true,
            'data' => [
                'status' => $status['status'],
                'watching' => $status['watching']
            ]
        ];
    }
}
