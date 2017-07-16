<?php

use Psr\Container\ContainerInterface as ContainerInterface;

$container = $app->getContainer();

// session helper
$container['session'] = function(ContainerInterface $container) {
    return new SlimSession\Helper();
};

// flash messages for redirect messages
$container['flash'] = function () {
    return new \Slim\Flash\Messages();
};

// view renderer
$container['view'] = function (ContainerInterface $container) {

    $settings = $container->get('settings')["renderer"];

    $view = new \Slim\Views\Twig($settings["template_path"], $settings);

    $basePath = rtrim(str_ireplace('index.php', '', $container['request']->getUri()->getBasePath()), '/');
    
    $view->addExtension(new Slim\Views\TwigExtension($container['router'], $basePath));
    
    $view->addExtension(new \Integration\Twig\RedBeanRelationshipTwigAdapter);

    return $view;
};

// monolog
$container['logger'] = function (ContainerInterface $c) {

    $settings = $c->get('settings')['logger'];

    $logger = new Monolog\Logger($settings['name']);

    $logger->pushProcessor(new Monolog\Processor\UidProcessor());
    $logger->pushHandler(new Monolog\Handler\StreamHandler($settings['path'], $settings['level']));

    return $logger;
};

// orm
$container['setupRedBean'] = function (ContainerInterface $c) {
    $settings = $c->get('settings')['db'];
    if (!\RedBeanPHP\R::testConnection()) {
        
        \RedBeanPHP\R::setup(
            'mysql:host=' . $settings['host'] . 
            ';dbname=' . $settings['name'] . 
            ';charset=' . $settings['char'], 
            $settings['user'], 
            $settings['pass']
        );
        
        \RedBeanPHP\R::setAutoResolve(true);
        
        if (!\RedBeanPHP\R::testConnection()) {
            throw new \RedBeanPHP\RedException('Não foi possível conectar ao banco de dados');
        }
        
//        \RedBeanPHP\R::setAutoResolve(true);
        
        define( 'REDBEAN_MODEL_PREFIX', '\\Model\\' );
    }
    return;
};
