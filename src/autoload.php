<?php

spl_autoload_register(function($class) {
    $path = '../src/' . str_replace('\\', '/', $class) . '.php';
    require $path;
});
