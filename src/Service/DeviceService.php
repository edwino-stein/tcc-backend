<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Component\HttpFoundation\Response;

class DeviceService {

    protected $waitFor = 3;
    protected $sudoBin = '';
    protected $poweroffBin = '';
    protected $rebootBin = '';

    protected $wifiIf = '';
    protected $iwconfigBin = '';
    protected $ifconfigBin = '';
    protected $hostnameBin = '';

    public function __construct(ParameterBagInterface $params){
        $params = $params->get("linux");
        $this->sudoBin = $params['sudo_bin'];
        $this->poweroffBin = $params['poweroff_bin'];
        $this->rebootBin = $params['reboot_bin'];

        $this->wifiIf = $params['wifi_if'];
        $this->iwconfigBin = $params['iwconfig_bin'];
        $this->ifconfigBin = $params['ifconfig_bin'];
        $this->hostnameBin = $params['hostname_bin'];
    }

    protected function readInfo(){

        $wifiName = $this->exec([$this->iwconfigBin, $this->wifiIf])['output'];

        if(!empty($wifiName)){
            $wifiName = explode('ESSID:', $wifiName[0])[1];
            $wifiName = str_replace('"', '', $wifiName);
        }
        else {
            $wifiName = 'undefined';
        }

        $ip = $this->exec([$this->ifconfigBin, $this->wifiIf])['output'];
        if(!empty($ip)){
            $ip = trim(explode('netmask', $ip[1])[0]);
            $ip = str_replace('inet ', '', $ip);
        }
        else{
            $ip = 'undefined';
        }

        $hostname = $this->exec([$this->hostnameBin])['output'];
        if(!empty($hostname)){
            $hostname = $hostname[0];
        }
        else{
            $hostname = 'undefined';
        }

        return [
            'wifi' => $wifiName,
            'security' => '',
            'ip' => $ip,
            'hostname' => $hostname,
            'so' => 'Raspbian/Linux',
            'ram' => '1GB',
            'devicename' => 'Raspberry Pi 3B',
            'software' => '0.5-alpha'
        ];
    }

    protected function exec($command, $sudo = false){
        if($sudo) array_unshift($command, $this->sudoBin);
        $output = [];
        $return = 0;
        exec(implode(' ', $command), $output, $return);
        return ['return' => $return, 'output' => $output];
    }

    protected function sendPreResponse($response){
        ob_start();

        echo $response;
        $size = ob_get_length();

        header("Content-Encoding: none");
        header("Content-Length: " . $size);
        header("Connection: close");

        ob_end_flush();
        ob_flush();
        flush();
    }

    public function poweroff(){
        $this->sendPreResponse(json_encode(['result' => true], JSON_FORCE_OBJECT));
        sleep($this->waitFor);
        $this->exec([$this->poweroffBin], true);
        return new Response();
    }

    public function reboot(){
        $this->sendPreResponse(json_encode(['result' => true], JSON_FORCE_OBJECT));
        sleep($this->waitFor);
        $this->exec([$this->rebootBin], true);
        return new Response();
    }

    public function getInfo(){
        $cache = new FilesystemAdapter();
        return $cache->get('device_info', function (ItemInterface $item) {
            return $this->readInfo();;
        });
    }
}
