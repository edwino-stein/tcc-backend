<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;

class AuthController extends AbstractController {

    /**
     * @Route("/auth/session", name="auth_session")
     */
    public function session(){

        $hasSession = $this->isGranted('IS_AUTHENTICATED_FULLY');
        $user = $hasSession ? $this->getUser() : null;

        return new Response(
            $this->renderView('auth/session.js.twig', [
                'hasSession' => $hasSession,
                'username' =>  $user != null ? $this->getUser()->getUsername() : '',
                'enabled' =>  $user != null ? $this->getUser()->isEnabled() : false
            ]),
            Response::HTTP_OK,
            [
                'content-type' => 'text/javascript',
                'r' => uniqid()
            ]
        );
    }
}
