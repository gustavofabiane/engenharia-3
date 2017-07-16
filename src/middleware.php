<?php

$app->add(Middleware\FormValidation::class);

$app->add(new \Slim\Middleware\Session([
    'name' => 'user-session',
    'lifetime' => '120 minutes'
]));

$app->add(Middleware\TraillingSlash::class);

//$app->add(new \Slim\Csrf\Guard());