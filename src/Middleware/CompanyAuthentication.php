<?php

namespace Middleware;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Description of CompanyAuthentication
 *
 * @author gusta
 */
class CompanyAuthentication extends AbstractMiddleware {

    /**
     * Autentica o usuário como funcionário da empresa ou dono
     * @param Request $request
     * @param Response $response
     * @param callable $next
     */
    public function __invoke(Request $request, Response $response, callable $next): Response {

        $this->container['setupRedBean'];

        $session = $this->container['session'];

        if (!$session->exists('company')) {

            $user = $session->get('user');

            if ($user->cargo->id == 4) {
                return $response->withRedirect('/empresa/selecionar/', 302);
            } else {
                $session->set('company', $user->empresa);
            }
        }

        return $next($request, $response);
    }

}
