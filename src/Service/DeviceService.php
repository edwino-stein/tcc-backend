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
    protected $catBin = '';
    protected $hostapdConf = '';

    public function __construct(ParameterBagInterface $params){
        $params = $params->get("linux");
        $this->sudoBin = $params['sudo_bin'];
        $this->poweroffBin = $params['poweroff_bin'];
        $this->rebootBin = $params['reboot_bin'];

        $this->wifiIf = $params['wifi_if'];
        $this->iwconfigBin = $params['iwconfig_bin'];
        $this->ifconfigBin = $params['ifconfig_bin'];
        $this->catBin = $params['cat_bin'];
        $this->hostapdConf = $params['hostapd_conf'];
    }

    protected function readInfo(){

        $wifiData = $this->exec([$this->iwconfigBin, $this->wifiIf])['output'];
        $wifiName = 'undefined';
        $wifibt = 'undefined';
        $wifiIsClient = true;
        $wifiSecurity = 'undefined';

        if(!empty($wifiData)){

            $matches = [];
            preg_match('/Mode:Master/', $wifiData[0], $matches);

            if(empty($matches[0])){

                $matches = [];
                preg_match('/ESSID:\"[a-zA-Z\d]*\"/', $wifiData[0], $matches);
                $wifiName = explode(':', $matches[0])[1];
                $wifiName = str_replace('"', '', $wifiName);

                $matches = [];
                preg_match('/Bit\s?Rate=\d*(.\d*)?\s?(M|m|K|k)b\/s/', $wifiData[2], $matches);
                $wifibt = explode('=', $matches[0])[1];

            }
            else {
                $wifiIsClient = false;
            }
        }

        if(!$wifiIsClient){

            $wifiData = implode("\n", $this->exec([$this->catBin, $this->hostapdConf])['output']);

            $matches = [];
            preg_match('/ssid=[a-zA-Z\d]*/m', $wifiData, $matches);
            $wifiName = explode('=', $matches[0])[1];

            $matches = [];
            preg_match('/passphrase=[a-zA-Z\d]*/m', $wifiData, $matches);
            $wifiSecurity = explode('=', $matches[0])[1];
        }

        $ifconfig = $this->exec([$this->ifconfigBin, $this->wifiIf])['output'];
        $ip = 'undefined';

        if(!empty($ifconfig)){
            $matches = [];
            preg_match('/inet\s\d*.\d*.\d*.\d*/', $ifconfig[1], $matches);
            $ip = str_replace('inet ', '', $matches[0]);
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
            'security' => $wifiSecurity,
            'bitrate' => $wifibt,
            'isWifiClient' => $wifiIsClient,
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
        $this->sendPreResponse(json_encode(['result' => true, 'data' => null], JSON_FORCE_OBJECT));
        sleep($this->waitFor);
        $this->exec([$this->poweroffBin], true);
        return new Response();
    }

    public function reboot(){
        $this->sendPreResponse(json_encode(['result' => true, 'data' => null], JSON_FORCE_OBJECT));
        sleep($this->waitFor);
        $this->exec([$this->rebootBin], true);
        return new Response();
    }

    public function getInfo(){
        $cache = new FilesystemAdapter();
        return $cache->get('device_info', function (ItemInterface $item) {
            return $this->readInfo();
        });
    }
}
