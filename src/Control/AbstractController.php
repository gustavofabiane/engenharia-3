<?php

namespace Control;

use Psr\Http\Message\RequestInterface as Request;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Container\ContainerInterface as ContainerInterface;

/**
 * Description of Controller
 *
 * @author gusta
 */
class AbstractController {

    /**
     * $container
     * 
     * Dependency Container
     * 
     * @var ContainerInterface 
     */
    protected $container;
    
    /**
     * $responseData
     * 
     * Data to be passed for Response
     * 
     * @var array
     */
    protected $responseData = [
        'title' => 'EasyManagement'
    ];
    
    /**
     * Logged user
     * @var RedBeanPHP\OODBBean
     */
    protected $user;
    
    /**
     * Selected Company
     * @var RedBeanPHP\OODBBean
     */
    protected $company;

    /**
     * Constrói o controlador
     * @param ContainerInterface $container
     */
    public function __construct(ContainerInterface $container) {
        
        $this->container = $container;
        
        $this->container['setupRedBean'];
        
        $session = $this->container['session'];
        
        if($session->exists('user')) {
            $this->user = $session->get('user');
            $this->responseData['user'] = $this->user;
        }
        
        if($session->exists('company')) {
            $this->company = $session->get('company');
            $this->responseData['selCompany'] = $this->company;
        }
        
    }

}
