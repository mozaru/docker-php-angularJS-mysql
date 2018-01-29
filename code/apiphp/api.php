<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


require '../slim/vendor/autoload.php';

date_default_timezone_set('America/Sao_paulo');

$app = new \Slim\App;


require_once './usuarios.php';
require_once './login.php';


$app->run();

