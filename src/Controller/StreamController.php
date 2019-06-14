<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ServerStream;

class StreamController extends AbstractController {

    /**
     * @Route("/stream/start", name="streamstart")
     */
    public function start(Request $request, ServerStream $server){
        return $this->json($server->start());
    }

    /**
     * @Route("/stream/stop", name="streamstop")
     */
    public function stop(Request $request, ServerStream $server){
        return $this->json($server->stop());
    }

    /**
     * @Route("/stream/status", name="streamstat")
     */
    public function status(Request $request, ServerStream $server){
        return $this->json($server->status());
    }

}
