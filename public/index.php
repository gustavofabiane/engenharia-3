<?php

require '../vendor/autoload.php';

require '../src/autoload.php';

$app = new \Slim\App(require '../src/settings.php');

require '../src/dependencies.php';

require '../src/routes.php';

require '../src/middleware.php';

$app->run();