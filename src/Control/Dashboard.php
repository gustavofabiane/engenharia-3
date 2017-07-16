<?php

namespace Control;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
//use RedBeanPHP\R as R;

/**
 * Description of Dashboard
 *
 * @author gusta
 */
class Dashboard extends AbstractController {

    /**
     * 
     * @param Request $request
     * @param Response $response
     * @return Response
     */
    public function __invoke(Request $request, Response $response): Response {

        $this->responseData['title'] .= ' - Dashboard';

        switch ($this->user->cargo->id) {
            case 4:
                $response = $this->manager($request, $response);
                break;
            case 1:
                $response = $this->manager($request, $response);
                break;
            case 2:
                $response = $this->supervisor($request, $response);
                break;
            case 3:
                $response = $this->salesman($request, $response);
                break;
        }

        return $response;
    }

    /**
     * Gera dashboard do gerente
     * @param Request $request
     * @param Response $response
     */
    protected function manager(Request $request, Response $response): Response {


        return $this->container['view']->render(
            $response, 
            'dashboard/manager.twig', 
            $this->responseData
        );
    }

    /**
     * Gera dashboard do supervisor
     * @param Request $request
     * @param Response $response
     */
    protected function supervisor(Request $request, Response $response): Response {


        return $this->container['view']->render(
            $response, 
            'dashboard/supervisor.twig', 
            $this->responseData
        );
    }

    /**
     * Gera dashboard do vendedor
     * @param Request $request
     * @param Response $response
     */
    protected function salesman(Request $request, Response $response): Response {


        return $this->container['view']->render(
            $response, 
            'dashboard/salesman.twig', 
            $this->responseData
        );
    }

}
