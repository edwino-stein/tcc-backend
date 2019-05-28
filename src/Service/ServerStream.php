<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;

use App\Util\UnixQueueMessage;

class ServerStream {

    public const SERVER_STATUS_STOPPED  = 'STOPPED';
    public const SERVER_STATUS_WAITTING  = 'WAITTING';
    public const SERVER_STATUS_RUNNING  = 'RUNNING';
    public const SERVER_STATUS_ERROR    = 'ERROR';

    protected $referenceFile = "";
    protected $config = [];

    public function __construct(ParameterBagInterface $params){

        $params = $params->get("server_stream");
        $this->config = $params['config'];

        $this->referenceFile = $params['reference_file'];
        if(!file_exists($this->referenceFile)){
            file_put_contents(
                $this->referenceFile,
                json_encode(['status' => self::SERVER_STATUS_STOPPED])
            );
        }
    }

    protected function readFileStatus(){

        $data = file_get_contents($this->referenceFile);
        if(empty($data)) return ['status' => self::SERVER_STATUS_ERROR];

        $data = json_decode($data, true);
        if($data == NULL) return ['status' => self::SERVER_STATUS_ERROR];


        return $data;
    }

    protected function getStatus($ignoreCache = false){

        if($ignoreCache){
            return $this->readFileStatus();
        }
        else {

            $cache = new FilesystemAdapter();
            $meta = $cache->getItem('server_stream_status')->getMetaData();

            if(!empty($meta)){
                if(filemtime($this->referenceFile) < $meta['expiry']){
                    $cache->delete('server_stream_status');
                }
            }
            return $cache->get('server_stream_status', function (ItemInterface $item) {
                $data = $this->readFileStatus();
                if($data['status'] != self::SERVER_STATUS_ERROR) $item->expiresAfter(60);
                else $item->expiresAfter(0);
                return $data;
            });
        }
    }

    protected function getQueueMessage(&$result){

        if(!file_exists($this->referenceFile)){

            $result = [
                'result' => false,
                'message' => 'Arquivo de referência inexistente.'
            ];

            return NULL;
        }

        $key = UnixQueueMessage::fileToKey($this->referenceFile);
        if(!UnixQueueMessage::existsQueue($key)){
            $result = [
                'result' => false,
                'message' => 'Fila de mensgens inexistente.'
            ];

            return NULL;
        }

        $qm = new UnixQueueMessage($key);

        if(!$qm->isOk()){
            $result = [
                'result' => false,
                'message' => 'Falha ao inicializar fila de mensgens.'
            ];

            return NULL;
        }

        return $qm;
    }

    protected function send($cmd, $extra = []){

        $result = [];
        $qm = $this->getQueueMessage($result);

        if($qm == NULL) return $result;

        $json = json_encode(
            ['cmd' => $cmd, 'extra' => $extra],
            JSON_UNESCAPED_SLASHES | JSON_FORCE_OBJECT
        );

        return ['result' => $qm->push($json."\0")];
    }

    public function start(){

        $status = $this->getStatus(true);

        if($status['status'] == self::SERVER_STATUS_WAITTING){

            $result = $this->send('start', self::parseConfig($this->config));

            if(!isset($result['message'])){
                $result['message'] = (
                    $result['result'] ?
                    'Transmissão iniciado com sucesso.' :
                    'Falha ao inicializar a transmissão.'
                );
            }

            sleep(2);
            $status = $this->getStatus(true);
        }
        else{
            $result = ['result' => false, 'message' => 'Server status: '. $status['status']];
        }

        $result['data'] = $status;
        return $result;
    }

    public function stop(){

        $status = $this->getStatus(true);

        if($status['status'] == self::SERVER_STATUS_RUNNING){

            $result = $this->send('stop');

            if(!isset($result['message'])){
                $result['message'] = (
                    $result['result'] ?
                    'Transmissão encerrada com sucesso.' :
                    'Falha ao encerrar a transmissão.'
                );
            }

            sleep(2);
            $status = $this->getStatus(true);
        }
        else{
            $result = ['result' => false, 'message' => 'Server status: '. $status['status']];
        }

        $result['data'] = $status;
        return $result;
    }

    public function status(){
        return [
            'result' => true,
            'data' => $this->getStatus()
        ];
    }

    static function toCamelCase($str, $sep, $startLower = true){

        $words = explode($sep, $str);
        $str = '';

        foreach ($words as $k => $w) $str .= ucfirst($w);
        return $startLower ? lcfirst($str) : $str;
    }

    static function parseConfig($c){

        $config = [];
        foreach ($c as $key => $value){
            $config[self::toCamelCase($key, '_')] = is_array($value) ? self::parseConfig($value) : $value;
        }

        return $config;
    }
}
