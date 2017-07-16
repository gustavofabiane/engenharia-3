<?php

namespace Control;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use RedBeanPHP\R as R;

/**
 * Description of Login
 *
 * @author gusta
 */
class Login extends AbstractController {

    /**
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function __invoke(Request $request, Response $response): Response {

        if ($request->isPost()) {

            $loginData = $request->getParsedBody();

            $username = $loginData["user"];
            $password = $loginData["pass"];

            try {
                
                $user = \Model\Usuario::validate($username, $password);

                $this->container['session']
                        ->set('logged', true)
                        ->set('user', $user);

                return $response->withRedirect('/dashboard/', 302);
                
            } catch (\Exception $ex) {
                \SlimSession\Helper::destroy();
                $this->responseData['username'] = $username;
                $this->responseData['password'] = $password;
                $this->responseData['error'] = $ex->getMessage();
            }
        }

        return $this->container["view"]->render($response, 'login/login.twig', $this->responseData);
    }

    /**
     * Verifica se o usuário está logado
     * @middleware
     * @param Request $request
     * @param Response $response
     * @param callable $next
     * @return Response
     */
    public function checkLogged(Request $request, Response $response, callable $next): Response {

        $session = $this->container['session'];

        if (!$session->exists('logged') || $session->get('logged') != true) {
            return $response->withRedirect('/login/', 302);
        }

        return $next($request, $response);
    }
    
    /**
     * Destroi a sessão e desloga o usuário
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function logout(Request $request, Response $response): Response {

        $session = $this->container['session'];
        
        $session->delete('user')
                ->delete('company')
                ->delete('logged');
        
        \SlimSession\Helper::destroy();
        
        return $response->withRedirect('/login/', 302);
    }

}
