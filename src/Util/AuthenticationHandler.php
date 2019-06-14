<?php
namespace App\Util;

use Symfony\Component\Security\Http\Authentication\AuthenticationSuccessHandlerInterface;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\Logout\LogoutSuccessHandlerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Exception\AuthenticationException;

class AuthenticationHandler implements AuthenticationSuccessHandlerInterface, AuthenticationFailureHandlerInterface, LogoutSuccessHandlerInterface {

    public function onAuthenticationSuccess(Request $request, TokenInterface $token){
        $result = ['success' => true, 'message' => 'Logado com sucesso'];
        return new Response(json_encode($result));
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception){

        $message = $exception->getMessage();
        switch ($exception->getMessage()) {
            case 'Bad credentials.':
                $message = "Senha inválida.";
            break;
        }

        $result = ['success' => false, 'message' => $message];
        return new Response(json_encode($result));
    }

    public function onLogoutSuccess(Request $request){
        $result = array('success' => false, 'message' => 'Deslogado com sucesso');
        return new Response(json_encode($result));
    }
}
