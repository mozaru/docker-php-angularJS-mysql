<?php

require_once ('email.php');
require_once ('utils.php');
require_once ('bd.php');

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;


$app->get('/usuarios',function (Request $request, Response $response, array $args) 
{
    try{
        controlaAcesso($request);
        $bd = new banco();
        $bd->prepara('select id, apelido, nome, email, ativo, data from usuario order by nome');
        $data = $bd->executar();
        $bd->desconectar();
        if ($bd->temErro())
            throw new Exception($bd->getErro());
        else
            return $response->getBody()->write( json_encode($data));
    }catch(Exception $e){
        return $response->withJson( ['status'=>401, 'message'=>$e->getMessage()],401 );
  } 
});

$app->get('/usuarios/{id}',function (Request $request, Response $response, array $args) 
{
    try{
        controlaAcesso($request);
        $bd = new banco();
        $id = $args['id'];
        $bd->prepara('select id, apelido, nome, email, ativo, senha from usuario where id=:id');
        $bd->parametro("id", $id);        
        $data = $bd->executar();
        $bd->desconectar();
        if ($bd->temErro())
            throw new Exception($bd->getErro());
        else
            return $response->getBody()->write( json_encode($data));
    }catch(Exception $e){
        return $response->withJson( ['status'=>401, 'message'=>$e->getMessage()],401 );
    } 
});

$app->post('/usuarios/inserir', function (Request $request, Response $response, array $args) 
{
    try{
        controlaAcesso($request);
        $obj = json_decode($request->getBody());
        $bd->prepara("select apelido from usuario where email = :email and id <> :id");
        $bd->parametro("email",$obj->email);  
        $bd->parametro("id",$id);
        $bd->executar();
        if ($bd->count()!=0)
            throw new Exception("Email já cadastrado!");
        else if (strlen($obj->nome)==0)
            throw new Exception("O nome não pode estar em branco!");
        else if (strlen($obj['apelido'])==0)
            throw new Exception("O apelido não pode estar em branco!");
        else if (strlen($obj['email'])==0)
            throw new Exception("O email não pode estar em branco!");
        else{
            $dt =date('Y-m-d H:i:s');
            $senha = gerarSenha(); 
            $hash = md5($senha);
            $bd->prepara('INSERT INTO usuario (apelido, nome, email, senha, data) values (:apelido, :nome, :email, :senha, :data)');
            $bd->parametro("apelido", $obj->apelido);
            $bd->parametro("nome", $obj->nome);
            $bd->parametro("email", $obj->email);
            $bd->parametro("senha",$hash);
            $bd->parametro("data", $dt);
            $data = $bd->executar();
            bd.desconectar();
            if ($bd->temErro())
                throw new Exception($bd->getErro());
            else if ($bd->count() == 0)
                throw new Exception('Nao foi possivel inserir os dados do usuario '.$obj->email.'!');
            else{
                $ctr = new email();
                $corpo = sprintf("%s,\n\n sua conta foi criada pelo administrador\n%s\nlogin:%s\nsenha:%s\n\nAtt,\nSuporte Viagem",
                        $obj->apelido,
                        "/",
                        $obj->email,
                        $senha);
                if ($ctr->enviar($obj->email, "Bem Vindo", $corpo))
                    return $response->write( '{"status":200, "message":"Usuario registrado com sucesso!"}');
                else
                    throw new Exception('{"status":501, "message":"não foi possivel enviar email para '.$obj->email.'"}');
            }
        }
    }catch(Exception $e){
        return $response->withJson( ['status'=>401, 'message'=>$e->getMessage()],401 );
    } 
});

$app->delete('/usuarios/{id}', function (Request $request, Response $response, array $args) 
{
    try{
        controlaAcesso($request);
        $bd = new banco();
        $id = $args['id'];
        $bd->prepara("select email from usuario where id=:id");  
        $bd->parametro("id",$id);
        $resp = $bd->executar();
        if ($bd->temErro())
            throw new Exception($bd->getErro());
        else if ($bd->count() == 0)
            throw new Exception('Usuario nao encontrado!');
        else {
            $email = $resp[0]->email;
            $bd->prepara('DELETE from usuario where id=:id');
            $bd->parametro("id",$id);
            $bd->executar();
            $bd->desconectar();
            if ($bd->temErro())
                throw new Exception($bd->getErro());
            else if ($bd->count() == 0)
                throw new Exception('Nao foi possivel remover o usuario '.$email.'!');
            else
                return $response->write( '{"status":200, "message":"Usuario ' .$email.' removido com sucesso!"}'); 
        }   
    }catch(Exception $e){
        return $response->withJson( ['status'=>401, 'message'=>$e->getMessage()],401 );
    } 
});


$app->map(['PUT', 'POST'],'/usuarios/{id}', function (Request $request, Response $response, array $args) 
{
    try{
        controlaAcesso($request);
        $bd = new banco();
        $id = $args['id'];
        $bd->prepara("select email from usuario where id=:id");  
        $bd->parametro("id",$id);
        $resp = $bd->executar();
        if ($bd->temErro())
            throw new Exception($bd->getErro());
        else if ($bd->count() == 0)
            throw new Exception('Usuario nao encontrado!');
        else {
            $obj = json_decode($request->getBody());
            $bd->prepara("select apelido from usuario where email = :email and id <> :id");
            $bd->parametro("email",$obj->email);  
            $bd->parametro("id",$id);
            $bd->executar();
            if ($bd->count()!=0)
                throw new Exception("Email já cadastrado!");
            else if (strlen($obj->nome)==0)
                throw new Exception("O nome não pode estar em branco!");
            else if (strlen($obj['apelido'])==0)
                throw new Exception("O apelido não pode estar em branco!");
            else if (strlen($obj['email'])==0)
                throw new Exception("O email não pode estar em branco!");
            $hash = md5($obj->senha);
            $bd->prepara('UPDATE usuario set apelido=:apelido, nome=:nome, senha=:senha, email=:email where id=:id');
            $bd->parametro("apelido",$obj->apelido);
            $bd->parametro("nome",$obj->nome);
            $bd->parametro("senha", $hash);
            $bd->parametro("email",$obj->email);
            $bd->parametro("id", $id);
            $data = $bd->executar();
            $bd->desconectar();
            if ($bd->temErro())
                throw new Exception($bd->getErro());
            else if ($bd->count() == 0)
                throw new Exception('Nao foi possivel alterar os dados do usuario '.$obj->email.'!');
            else
                return $response->write( '{"status":200, "message":"Usuario '.$obj->email.' alterado com sucesso!"}'); 
        }
    }catch(Exception $e){
        return $response->withJson( ['status'=>401, 'message'=>$e->getMessage()],401 );
    } 
});
        

$app->post('/usuarios/ativar/{id}', function (Request $request, Response $response, array $args) 
{
    try{
        controlaAcesso($request);
        $bd = new banco();
        $id = $args['id'];
        $bd->prepara("select email from usuario where id=:id");  
        $bd->parametro("id",$id);
        $resp = $bd->executar();
        if ($bd->temErro())
            throw new Exception($bd->getErro());
        else if ($bd->count() == 0)
            throw new Exception('Usuario nao encontrado!');
        else {
            $email = $resp[0]->email;
            $bd->prepara('UPDATE usuario set ativo=1 where id=:id');
            $bd->parametro("id", $id);
            $data = $bd->executar();
            $bd->desconectar();
            if ($bd->temErro())
                throw new Exception($bd->getErro());
            else if ($bd->count() == 0)
                throw new Exception('Nao foi possivel ativar o usuario '.+$email.'!');
            else
                return $response->write( '{"status":200, "message":"Usuario '.$email.' ativado com sucesso!"}');
        }
    }catch(Exception $e){
        return $response->withJson( ['status'=>401, 'message'=>$e->getMessage()],401 );
    } 
});

$app->post('/usuarios/desativar/{id}', function (Request $request, Response $response, array $args) 
{
    try{
        controlaAcesso($request);
        $bd = new banco();
        $id = $args['id'];
        $bd->prepara("select email from usuario where id=:id");  
        $bd->parametro("id",$id);
        $resp = $bd->executar();
        if ($bd->temErro())
            throw new Exception($bd->getErro());
        else if ($bd->count() == 0)
            throw new Exception('Usuario nao encontrado!');
        else {
            $email = $resp[0]->email;
            $bd->prepara('UPDATE usuario set ativo=0 where id=:id');
            $bd->parametro("id", $id);
            $data = $bd->executar();
            $bd->desconectar();
            if ($bd->temErro())
                throw new Exception($bd->getErro());
            else if ($bd->count() == 0)
                throw new Exception('Nao foi possivel desativar o usuario '.+$email.'!');
            else
                return $response->write( '{"status":200, "message":"Usuario '.$email.' desativado com sucesso!"}');
        }
    }catch(Exception $e){
        return $response->withJson( ['status'=>401, 'message'=>$e->getMessage()],401 );
    } 
});


$app->post('/usuarios/resetar/{id}', function (Request $request, Response $response, array $args) 
{
    try{
        controlaAcesso($request);
        $bd = new banco();
        $id = $args['id'];
        $bd->prepara("select email from usuario where id=:id");  
        $bd->parametro("id",$id);
        $resp = $bd->executar();
        if ($bd->temErro())
            throw new Exception($bd->getErro());
        else if ($bd->count() == 0)
            throw new Exception('Usuario nao encontrado!');
        else {
            $email = $resp[0]->email;
            $hash = md5(gerarSenha());
            $bd->prepara('UPDATE usuario set senha=:senha where id=:id');
            $bd->parametro("id", $id);
            $bd->parametro("senha", $hash);
            $data = $bd->executar();
            $bd->desconectar();
            if ($bd->temErro())
                throw new Exception($bd->getErro());
            else if ($bd->count() == 0)
                throw new Exception('Nao foi possivel resetar a senha do usuario '.+$email.'!');
            else
                return $response->write( '{"status":200, "message":"Senha do usuario '.$email.' resetada com sucesso!"}');
        }
    }catch(Exception $e){
        return $response->withJson( ['status'=>401, 'message'=>$e->getMessage()],401 );
    } 
});

?>
