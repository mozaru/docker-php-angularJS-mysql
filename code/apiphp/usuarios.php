<?php

require_once ('email.php');
require_once ('utils.php');
require_once ('bd.php');

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


$app->get('/usuarios',function (Request $request, Response $response, array $args) 
    {
        $bd = new banco();
        $bd->prepara('select id, apelido, nome, email, ativo, data from usuario order by nome');
        $response->getBody()->write( json_encode($bd->executar()));
        return $response;
    });
$app->get('/usuarios/{id}',function (Request $request, Response $response, array $args) 
    {
        $bd = new banco();
        $id = $args['id'];
        $bd->prepara('select id, apelido, nome, email, ativo, senha from usuario where id=:id');
        $bd->parametro("id", $id);        
        $response->getBody()->write( json_encode($bd->executar()));
        return $response;
    });
$app->post('/usuarios/inserir', function (Request $request, Response $response, array $args) 
    {
        $obj = json_decode($request->getBody());
        $bd = new banco();
        $bd->prepara("select apelido from usuario where email=:email");  
        $bd->parametro("email",$obj->email);
        $bd->executar()[0];
        if ($bd->count() != 0 || strlen($obj->nome)==0 || strlen($obj->apelido)==0 || strlen($obj->email)==0)
        {
            $response = $response->withStatus(401);
            if ($bd->count() != 0)
                return $response->write( '{"status":401, "message":"Email já cadastrado!"}');
            else if (strlen($obj->apelido)==0)
                return $response->write( '{"status":401, "message":"O apelido não pode estar em branco!"}');
            else if (strlen($obj->nome)==0)
                return $response->write( '{"status":401, "message":"O nome não pode estar em branco!"}');
            else if (strlen($obj->email)==0)
                return $response->write( '{"status":401, "message":"O email não pode estar em branco!"}');
           else 
                return $response->write( '{"status":401, "message":"Erro Inexperado!"}');
        }
        else
        {
            $bd->prepara('INSERT INTO usuario (apelido, nome, email, senha, data) values (:apelido, :nome, :email, :senha, :data)');
            $bd->parametro("apelido", $obj->apelido);
            $bd->parametro("nome", $obj->nome);
            $bd->parametro("email", $obj->email);
            $senha = gerarSenha();
            $bd->parametro("senha",md5($senha));
            $bd->parametro("data", date('Y-m-d H:i:s'));
            $l = $bd->executar();
            $ctr = new email();
            $corpo = sprintf("%s,\n\n sua conta foi criada pelo administrador\n%s\nlogin:%s\nsenha:%s\n\nAtt,\nSuporte Viagem",
                        $obj->apelido,
                        "/",
                        $obj->email,
                        $senha);
            if ($ctr->enviar($obj->email, "Bem Vindo", $corpo))
                return $response->write( '{"status":200, "message":"Usuario registrado com sucesso!"}');
            else{
                $response = $response->withStatus(501);
                return $response->write( '{"status":501, "message":"não foi possivel enviar email para '.$obj->email.'"}');
            }
        }
    });
$app->delete('/usuarios/{id}', function (Request $request, Response $response, array $args) 
    {
        $bd = new banco();
        $id = $args['id'];
        $bd->prepara("select email from usuario where id=:id");  
        $bd->parametro("id",$id);
        $email = $bd->executar()[0]->email;
        $bd->prepara('DELETE from usuario where id=:id');
        $bd->parametro("id",$id);
        $l = $bd->executar();
        if ($l == 1)
            return $response->write( '{"status":200, "message":"Usuario ' .$email.' removido com sucesso!"}'); 
        else{
            $response = $response->withStatus(500);
            return $response->write( '{"status":501, "message":"usuário ' .$email.' não removido!"}');
        }
    });
$app->put('/usuarios/{id}', function (Request $request, Response $response, array $args) 
    {
        $obj = json_decode($request->getBody());
        $bd = new banco();
        $id = $args['id'];
        $bd->prepara("select email from usuario where id=:id");  
        $bd->parametro("id",$id);
        $email = $bd->executar()[0]->email;
        $bd->prepara('UPDATE usuario set apelido=:apelido, nome=:nome, email=:email, senha=:senha where id=:id');
        $bd->parametro("apelido",$obj->apelido);
        $bd->parametro("nome",$obj->nome);
        $bd->parametro("email",$obj->email);
        $bd->parametro("senha",md5($obj->senha));
        $bd->parametro("id", $id);
        $l = $bd->executar();
        if ($l == 1)
            return $response->write( '{"status":200, "message":"Usuario ' .$email.' alterado com sucesso!"}'); 
        else{
            $response = $response->withStatus(500);
            return $response->write( '{"status":501, "message":"usuário ' .$email.' não alterado!"}');
        }
    });
$app->post('/usuarios/{id}', function (Request $request, Response $response, array $args) 
    {
        $obj = json_decode($request->getBody());
        $bd = new banco();
        $id = $args['id'];
        $bd->prepara("select email from usuario where id=:id");  
        $bd->parametro("id",$id);
        $email = $bd->executar()[0]->email;
        $bd->prepara('UPDATE usuario set apelido=:apelido, nome=:nome, email=:email where id=:id');
        $bd->parametro("apelido",$obj->apelido);
        $bd->parametro("nome",$obj->nome);
        $bd->parametro("email",$obj->email);
        $bd->parametro("id", $id);
        $l = $bd->executar();
        if ($l == 1)
            return $response->write( '{"status":200, "message":"Usuario '.$email.' alterado com sucesso!"}'); 
        else{
            $response = $response->withStatus(500);
            return $response->write( '{"status":501, "message":"usuário '.$email. ' não alterado!"}');
        }
    });

    $app->post('/usuarios/ativar/{id}', function (Request $request, Response $response, array $args) 
    {
        $bd = new banco();
        $id = $args['id'];
        $bd->prepara("select email from usuario where id=:id");  
        $bd->parametro("id",$id);
        $email = $bd->executar()[0]->email;
        $bd->prepara('UPDATE usuario set ativo=1 where id=:id');
        $bd->parametro("id", $id);
        $l = $bd->executar();
        if ($l == 1)
            return $response->write( '{"status":200, "message":"Usuario ' .$email.' ativado com sucesso!"}'); 
        else{
            $response = $response->withStatus(500);
            return $response->write( '{"status":501, "message":"usuário ' .$email.' não ativado!"}');
        }
    });

    $app->post('/usuarios/desativar/{id}', function (Request $request, Response $response, array $args) 
    {
        $bd = new banco();
        $id = $args['id'];
        $bd->prepara("select email from usuario where id=:id");  
        $bd->parametro("id",$id);
        $email = $bd->executar()[0]->email;
        $bd->prepara('UPDATE usuario set ativo=0 where id=:id');
        $bd->parametro("id", $id);
        $l = $bd->executar();
        if ($l == 1)
            return $response->write( '{"status":200, "message":"Usuario ' .$email.' desativado com sucesso!"}'); 
        else{
            $response = $response->withStatus(500);
            return $response->write( '{"status":501, "message":"usuário ' .$email.' não desativado!"}');
        }
    });

    $app->post('/usuarios/resetar/{id}', function (Request $request, Response $response, array $args) 
    {
        $bd = new banco();
        $id = $args['id'];

        $bd->prepara("select apelido, email from usuario where id=:id");  
        $bd->parametro("id",$id);
        $obj = $bd->executar()[0];

        $bd->prepara('UPDATE usuario set senha=:senha where id=:id');
        $senha = gerarSenha();
        $bd->parametro("senha",md5($senha));
        $bd->parametro("id", $id);
        $l = $bd->executar();
   
        if ($l == 1)
        {
            $ctr = new email();
            $corpo = sprintf("%s,\n\n sua senha foi resetada pelo adminsitrador para %s\n\nAtt,\nSuporte Viagem",
                        $obj->apelido,
                        $senha);
            if ($ctr->enviar($obj->email, "Senha Resetada", $corpo))
                return $response->write( '{"status":200, "message":"A senha do usuario ' .$email.' foi resetada com sucesso!"}'); 
            else{
                $response = $response->withStatus(501);
                return $response->write( '{"status":501, "message":"não foi possivel enviar email para '.$obj->email.'"}');
            }
        }else{
            $response = $response->withStatus(500);
            return $response->write( '{"status":501, "message":"A senha do usuário ' .$email.' não foi resetada!"}');
        }
    });

?>
