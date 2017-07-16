<?php

namespace Middleware;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;

/**
 * Description of TraillingSlash
 *
 * @author gusta
 */
class TraillingSlash extends AbstractMiddleware {

    /**
     * 
     * @param Request $request
     * @param Response $response
     * @param \Middleware\callable $next
     * @return Response
     */
    public function __invoke(Request $request, Response $response, callable $next): Response {
        
        $uri = $request->getUri();
        $path = $uri->getPath();
        
        if (substr($path, -1) != '/') {
            
            $uri = $uri->withPath($path . '/');

            if ($request->getMethod() == 'GET') {
                return $response->withRedirect((string) $uri, 301);
            } else {
                return $next($request->withUri($uri), $response);
            }
        }

        return $next($request, $response);
    }
}
