<?php

namespace Middleware;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Description of FlashMessageCatcher
 *
 * @author gusta
 */
class FlashMessageCatcher extends AbstractMiddleware {

    public function __invoke(Request $request, Response $response, callable $next): Response {

        $messages = $this->container['flash']->getMessages();
        
        if (!empty($messages)) {
            $request = $request->withAttribute('messages', $messages);
        }
        
        return $next($request, $response);
    }

}
