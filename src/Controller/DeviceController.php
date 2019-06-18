<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;

use App\Service\DeviceService;

class DeviceController extends AbstractController {

    /**
     * @Route("/device", name="device")
     */
    public function index(){
        return $this->render('frontend/index.html.twig');
    }

    /**
     * @Route("/device/poweroff", name="device_poweroff")
     */
    public function poweroff(DeviceService $device){
        return $device->poweroff();
    }

    /**
     * @Route("/device/reboot", name="device_reboot")
     */
    public function reboot(DeviceService $device){
        return $device->reboot();
    }

    /**
     * @Route("/device/info", name="device_info")
     */
    public function info(DeviceService $device){
        return $this->json([
            'result' => true,
            'data' => $device->getInfo()
        ]);
    }
}
