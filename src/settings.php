<?php

return [
    'settings' => [
        
        'displayErrorDetails' => true, // set to false in production
        'addContentLengthHeader' => false, // Allow the web server to send the content-length header
        
        // Renderer settings
        'renderer' => [
            'template_path' => __DIR__ . '/../templates/',
            'cache_path' => __DIR__ . '/../templates/cache/',
        ],
        
        // Monolog settings
        'logger' => [
            'name' => 'engenharia',
            'path' => isset($_ENV['docker']) ? 'php://stdout' : __DIR__ . '/../logs/engenharia.log',
            'level' => \Monolog\Logger::DEBUG,
        ],
        
        //Database settings
        'db' => [
            'host' => 'localhost',
            'name' => 'engenharia_redbean',
            'user' => 'eng_app',
            'pass' => 'app123',
            'char' => 'utf8',
            'frozen' => false
        ]
    ],
];
