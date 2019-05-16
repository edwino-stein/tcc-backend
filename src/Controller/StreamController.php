<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;

use App\Service\ServerStream;

class StreamController extends AbstractController{

    /**
     * @Route("/stream", name="stream")
     */
    public function index(Request $request){
        return $this->render('frontend/index.html.twig');
    }

    /**
     * @Route("/stream/start", name="streamstart")
     */
    public function start(Request $request, ServerStream $server){
        return $this->json($server->start());
    }

}
