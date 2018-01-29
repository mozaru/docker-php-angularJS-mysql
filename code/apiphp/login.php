<?php

require_once ('email.php');
require_once ('utils.php');
require_once ('bd.php');

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

  
$app->post('/login/logar', function (Request $request, Response $response, array $args)
{
    $obj = json_decode($request->getBody());
    $senha = md5($obj->senha);
    $bd = new banco();
    $bd->prepara("select id, apelido, nome, senha, ativo from usuario where email=:email");  
    $bd->parametro("email",$obj->email);
    $resp = $bd->executar()[0];
    if ($bd->count() == 0 || $resp->senha != $senha || $resp->ativo == 0) //nao pode logar
    {
      $response = $response->withStatus(401);
      if ($bd->count() == 0 || $resp->senha != $senha)
            return $response->write( '{"status":401, "message":"Login ou Senha Invalido!"}');
      else 
            return $response->write( '{"status":401, "message":"Conta inativa!\nEntre em contato com os administradores para poder reativa-la."}');
    }
    else
    {
      session_start();
      $_SESSION['usuario'] = $resp;
      return  $response->getBody()->write( '{"status": 200, "message":"login efetuado com sucesso"}');
    }
});

$app->get('/login/lembrarsenha',function (Request $request, Response $response, array $args) 
{     
      $email = $request->getQueryParam('email');
      $bd = new banco();
      $bd->prepara("select apelido, ativo from usuario where email=:email");  
      $bd->parametro("email",$email);
      $obj = $bd->executar()[0];
      if ($bd->count() == 0 || $obj->ativo == 0) //nao pode logar
      {
        $response = $response->withStatus(401);
        if ($bd->count() == 0)
              return $response->write( '{"status":401, "message":"Email não cadastrado!"}');
        else 
              return $response->write( '{"status":401, "message":"Esta conta está inativa!\nEntre em contato com os administradores para poder reativa-la."}');
      }
      else
      {
            $ctr = new email();
            $corpo = sprintf("%s,\n\n para poder trocar a senha use o link abaixo\n\n%s%s\nAtt,\nSuporte Viagem",
                        $obj->apelido,
                        "http://localhost:8080/frontend/index.php?op=lembrarsenha&codigo=",
                        gerarChave($email,"lembrarsenha"));
            if ($ctr->enviar($email, "Lembrar Senha", $corpo))
                  return $response->write( '{"status":200, "message":"Email Enviado para '.$email.'!"}');
            else{
                  $response = $response->withStatus(501);
                  return $response->write( '{"status":501, "message":"não foi possivel enviar email para '.$email.'"}');
            }
      }
});

$app->get('/login/registrar',function (Request $request, Response $response, array $args) 
{
      $email = $request->getQueryParam('email');
      $bd = new banco();
      $bd->prepara("select apelido, ativo from usuario where email=:email");  
      $bd->parametro("email",$email);
      $obj = $bd->executar()[0];
      if ($bd->count() != 0) //nao pode logar
      {
        $response = $response->withStatus(401);
        if ($obj->ativo == 1)
              return $response->write( '{"status":401, "message":"Email já cadastrado!"}');
        else 
              return $response->write( '{"status":401, "message":"Conta está inativa!\nEntre em contato com os administradores para poder reativa-la."}');
      }
      else
      {
            $ctr = new email();
            $corpo = sprintf("%s,\n\n para poder registrar, confirme o seu email, usando o link abaixo\n\n%s%s\nAtt,\nSuporte Viagem",
                        "Caro Usuário",
                        "http://localhost:8080/frontend/index.php?op=registrar&codigo=",
                        gerarChave($email,"registrar"));
            if ($ctr->enviar($email, "Confirmação de email", $corpo))
                  return $response->write( '{"status":200, "message":"Email Enviado para '.$email.'!"}');
            else{
                  $response = $response->withStatus(501);
                  return $response->write( '{"status":501, "message":"não foi possivel enviar email para '.$email.'"}');
            }
      }
});

$app->get('/login/reativar',function (Request $request, Response $response, array $args) 
{
      $email = $request->getQueryParam('email');
      $bd = new banco();
      $bd->prepara("select apelido, ativo from usuario where email=:email");  
      $bd->parametro("email",$email);
      $obj = $bd->executar()[0];
      if ($bd->count() == 0 || $obj->ativo == 1) //nao pode logar
      {
        $response = $response->withStatus(401);
        if ($bd->count() == 0)
              return $response->write( '{"status":401, "message":"Email não cadastrado!"}');
        else 
              return $response->write( '{"status":401, "message":"Conta ja estava ativa!"}');
      }
      else
      {
            $ctr = new email();
            $corpo = sprintf("%s,\n\n para poder raativar sua conta clique no link abaixo\n\n%s%s\nAtt,\nSuporte Viagem",
                        $obj->apelido,
                        "http://localhost:8080/frontend/index.php?op=reativar&codigo=",
                        gerarChave($email,"reativar"));
            if ($ctr->enviar($email, "Reativar Conta", $corpo))
                  return $response->write( '{"status":200, "message":"Email Enviado para '.$email.'!"}');
            else{
                  $response = $response->withStatus(501);
                  return $response->write( '{"status":501, "message":"não foi possivel enviar email para '.$email.'"}');
            }
      }
});

$app->post('/login/lembrarsenha',function (Request $request, Response $response, array $args) 
{
      $obj = json_decode($request->getBody());
      $codigo = obterChave($obj->codigo);
      $bd = new banco();
      $bd->prepara("select apelido, ativo from usuario where email=:email");  
      $bd->parametro("email",$obj->email);
      $bd->executar()[0];
      if ($bd->count() != 1 || $codigo->expirado == 1 || $obj->email != $codigo->email || strlen($obj->senha)==0 || $obj->senha != $obj->confirmasenha || $codigo->motivo!="lembrarsenha") //nao pode logar
      {
        $response = $response->withStatus(401);
        if ($bd->count() != 1)
              return $response->write( '{"status":401, "message":"Email não cadastrado!"}');
        else if ($codigo->expirado == 1)
              return $response->write( '{"status":401, "message":"Codigo Expirado!"}');
        else if ($obj->email != $codigo->email)
              return $response->write( '{"status":401, "message":"O Codigo não é para este email!"}');
        else if (strlen($obj->senha)==0)
              return $response->write( '{"status":401, "message":"A senha não pode estar vazia!"}');
        else if ($obj->senha != $obj->confirmasenha)
              return $response->write( '{"status":401, "message":"A confirmaçao da senha não confere!"}');
        else if ($codigo->motivo!="lembrarsenha")
              return $response->write( '{"status":401, "message":"O Codigo não é para esta operação!"}');
        else 
              return $response->write( '{"status":401, "message":"Erro Inexperado!"}');
      }
      else
      {
            $bd->prepara('UPDATE usuario set senha=:senha where email=:email');
            $bd->parametro("senha", md5($obj->senha));
            $bd->parametro("email", $obj->email);            
            $l = $bd->executar();
            $response->getBody()->write( '{"id":' . $id . ', "LinhasAfetadas":'. $l .'}' );  
            return $response->write( '{"status":200, "message":"Senha alterada com sucesso!"}');
      }
});


$app->post('/login/registrar',function (Request $request, Response $response, array $args) 
{
      $obj = json_decode($request->getBody());
      $codigo = obterChave($obj->codigo);
      $bd = new banco();
      $bd->prepara("select apelido, ativo from usuario where email=:email");  
      $bd->parametro("email",$obj->email);
      $bd->executar()[0];
      if ($bd->count() != 0 || $codigo->expirado == 1 || $obj->email != $codigo->email || strlen($obj->nome)==0 || strlen($obj->apelido)==0 || strlen($obj->senha)==0 || $obj->senha != $obj->confirmasenha || $codigo->motivo!="registrar") //nao pode logar
      {
        $response = $response->withStatus(401);
        if ($bd->count() != 0)
              return $response->write( '{"status":401, "message":"Email já cadastrado!"}');
        else if ($codigo->expirado == 1)
              return $response->write( '{"status":401, "message":"Codigo Expirado!"}');
        else if ($obj->email != $codigo->email)
              return $response->write( '{"status":401, "message":"O Codigo não é para este email!"}');
        else if (strlen($obj->apelido)==0)
              return $response->write( '{"status":401, "message":"O apelido não pode estar em branco!"}');
        else if (strlen($obj->nome)==0)
              return $response->write( '{"status":401, "message":"O nome não pode estar em branco!"}');
        else if (strlen($obj->senha)==0)
              return $response->write( '{"status":401, "message":"A senha não pode estar em branco!"}');
        else if ($obj->senha != $obj->confirmasenha)
              return $response->write( '{"status":401, "message":"A confirmaçao da senha não confere!"}');
        else if ($codigo->motivo!="registrar")
              return $response->write( '{"status":401, "message":"O Codigo não é para esta operação!"}');
        else 
              return $response->write( '{"status":401, "message":"Erro Inexperado!"}');
      }
      else
      {
            $bd->prepara('INSERT INTO usuario (apelido, nome, email, senha, data) values (:apelido, :nome, :email, :senha, :data)');
            $bd->parametro("apelido", $obj->apelido);
            $bd->parametro("nome", $obj->nome);
            $bd->parametro("email", $obj->email);
            $bd->parametro("senha",md5($obj->senha));
            $bd->parametro("data", date('Y-m-d H:i:s'));
            $l = $bd->executar();
            $response->getBody()->write( '{"id":' . $id . ', "LinhasAfetadas":'. $l .'}' );  
            return $response->write( '{"status":200, "message":"Usuario registrado com sucesso!"}');
      }
});


$app->post('/login/reativar',function (Request $request, Response $response, array $args) 
{
      $obj = json_decode($request->getBody());
      $codigo = obterChave($obj->codigo);
      $bd = new banco();
      $bd->prepara("select apelido, ativo from usuario where email=:email");  
      $bd->parametro("email",$obj->email);
      $usuario = $bd->executar()[0];
      if ($bd->count() != 1 || $codigo->expirado == 1 || $obj->email != $codigo->email || strlen($obj->senha)==0 || $obj->senha != $obj->confirmasenha || $codigo->motivo!="reativar" || $usuario->ativo==1) //nao pode logar
      {
        $response = $response->withStatus(401);
        if ($bd->count() != 1)
              return $response->write( '{"status":401, "message":"Email não cadastrado!"}');
        else if ($codigo->expirado == 1)
              return $response->write( '{"status":401, "message":"Codigo Expirado!"}');
        else if ($obj->email != $codigo->email)
              return $response->write( '{"status":401, "message":"O Codigo não é para este email!"}');
        else if (strlen($obj->senha)==0)
              return $response->write( '{"status":401, "message":"A senha não pode estar vazia!"}');
        else if ($obj->senha != $obj->confirmasenha)
              return $response->write( '{"status":401, "message":"A confirmaçao da senha não confere!"}');
        else if ($codigo->motivo!="lembrarsenha")
              return $response->write( '{"status":401, "message":"O Codigo não é para esta operação!"}');
        else if ($usuario->ativo==1)
              return $response->write( '{"status":401, "message":"O Usuario já estava ativo!"}');
        else 
              return $response->write( '{"status":401, "message":"Erro Inexperado!"}');
      }
      else
      {
            $bd->prepara('UPDATE usuario set ativo=1 where id=:id');
            $bd->parametro("id", $id);
            $l = $bd->executar();
            $response->getBody()->write( '{"id":' . $id . ', "LinhasAfetadas":'. $l .'}' );  
            return $response->write( '{"status":200, "message":"Usuario registrado com sucesso!"}');
      }
});


?>
