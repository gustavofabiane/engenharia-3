<?php

namespace Control;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Description of Home
 *
 * @author gusta
 */
class Home extends Controller {
    
    public function recursos(Request $request, Response $response) {
        return $response->getBody()->write('Recursos');
    }
}
