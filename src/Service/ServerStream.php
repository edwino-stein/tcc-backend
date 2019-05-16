<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use App\Util\UnixQueueMessage;

class ServerStream {

    protected $referenceFile = "";
    protected $config = [];

    public function __construct(ParameterBagInterface $params){
        $params = $params->get("server_stream");
        $this->referenceFile = $params['reference_file'];
        $this->config = self::parseConfig($params['config']);
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

        $result = $this->send('start', $this->config);

        if(!isset($result['message'])){
            $result['message'] = (
                $result['result'] ?
                'Transmissão iniciado com sucesso.' :
                'Falha ao inicializar a transmissão.'
            );
        }

        return $result;
    }

    public function stop(){

        $result = $this->send('stop');

        if(!isset($result['message'])){
            $result['message'] = (
                $result['result'] ?
                'Transmissão encerrada com sucesso.' :
                'Falha ao encerrar a transmissão.'
            );
        }

        return $result;
    }
}
