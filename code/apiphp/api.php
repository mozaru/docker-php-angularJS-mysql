<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


require '../slim/vendor/autoload.php';
require './usuarios.php';

date_default_timezone_set('America/Sao_paulo');

$app = new \Slim\App;


//usuarios
$app->get('/usuarios',function (Request $request, Response $response, array $args) 
    {
        $usuario = new usuario();
        $vet = $usuario->listar();
        $response->getBody()->write( json_encode($vet));
    });
$app->get('/usuarios/{id}',function (Request $request, Response $response, array $args) 
    {
        $usuario = new usuario();
        $id = $args['id'];
        $obj = $usuario->obter($id);
        echo json_encode($obj);       
    });
$app->post('/usuarios/inserir', function (Request $request, Response $response, array $args) 
    {
        $usuario = new usuario();
        $obj = json_decode($request->getBody());
        $obj = $usuario->inserir($obj);
        echo json_encode($obj);
    });
$app->delete('/usuarios/{id}', function (Request $request, Response $response, array $args) 
    {
        $usuario = new usuario();
        $id = $args['id'];
        $obj = $usuario->remover($id);
        echo json_encode($obj);       
    });
$app->put('/usuarios/{id}', function (Request $request, Response $response, array $args) 
    {
        $usuario = new usuario();
        $id = $args['id'];
        $obj = json_decode($request->getBody());
        $obj = $usuario->alterar($id, $obj);
        echo json_encode($obj);       
    });
$app->post('/usuarios/{id}', function (Request $request, Response $response, array $args) 
    {
        $usuario = new usuario();
        $id = $args['id'];
        $obj = json_decode($request->getBody());
        $obj = $usuario->alterar($id, $obj);
        echo json_encode($obj);       
    });


$app->run();

