<?php

require_once ('email.php');
require_once ('utils.php');
require_once ('bd.php');

use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

$app->post('/login/logar',function (Request $request, Response $response, array $args) 
{ 
      try{
            $obj = json_decode($request->getBody());
            $senha = md5($obj->password);
            if (!property_exists($obj,'grant_type') || $obj->grant_type != 'password')
                  throw new Exception('grant_type diferente de password '.$obj->grant_type."sem");
            else if (!property_exists($obj,'client_id') || $obj->client_id != 'viagem')
                  throw new Exception('client_id nao permitido');
            else if (!property_exists($obj,'client_secret') || $obj->client_secret != '123')
                  throw new Exception('cliente_secret nao validado');
            else if (!property_exists($obj, 'scope') || $obj->scope != 'admin')
                  throw new Exception('scope nao permitido');
            $bd = new banco();
            $bd->conectar();
            $bd->prepara('select id, apelido, nome, email, senha, ativo from usuario where email=:email');
            $bd->parametro('email',$obj->username);
            $resp = $bd->executar();
            $bd->desconectar();
            if ($bd->temErro())
                  throw new Exception($bd->getErro());
            else{
                  $resp = $resp[0];
                  if ($bd->count() == 0 || $resp->senha == $senha)
                        throw new Exception('Login ou Senha Invalido!');
                  else if ($resp->ativo == 0)
                        throw new Exception('Conta inativa!\nEntre em contato com os administradores para poder reativa-la.');
                  else{
                        $access_token = GerarPayloadJWT($resp,get_client_ip(), True);
                        $refresh_token = GerarPayloadJWT($resp,get_client_ip(), False);
                        $jwt = json_decode(sprintf('{ 
                              "status": 200,
                              "message": "Login realizado com sucesso",
                              "token_type": "Bearer",
                              "expires_in": %s,
                              "expires_on": %s,
                              "access_token": "%s",
                              "refresh_token": "%s"
                        }',$access_token->duracao,$access_token->exp,JWTEncoder($access_token),JWTEncoder($refresh_token)));
                        return $response->write(json_encode($jwt));
                  }
            }
      }catch(Exception $e){
            return $response->withJson( ['status'=>401, 'message'=>$e->getMessage()],401 );
      } 
});

$app->post('/login/refreshtoken',function (Request $request, Response $response, array $args) 
{ 
      try{
            $obj = json_decode($request->getBody());
            if (!property_exists($obj,'grant_type') || $obj->grant_type != 'refresh_token')
                  throw new Exception('grant_type diferente de refresh_token');
            else if (!property_exists($obj,'client_id') || $obj->client_id != 'viagem')
                  throw new Exception('client_id nao permitido');
            else if (!property_exists($obj,'client_secret') || $obj->client_secret != '123')
                  throw new Exception('cliente_secret nao validado');
            else if (!property_exists($obj, 'scope') || $obj->scope != 'admin')
                  throw new Exception('scope nao permitido');
            else if (!property_exists($obj,'refresh_token'))
                  throw new Exception('refresh_token é obrigatório');
            $refresh_token = $obj->refresh_token;
            $obj = checarToken($refresh_token, get_client_ip(), "Refresh");
            $bd = new banco();
            $bd->conectar();
            $bd->prepara('select id, apelido, nome, email, senha, ativo from usuario where email=:email');
            $bd->parametro('email',$obj->email);
            $resp = $bd->executar();
            $bd->desconectar();
            if ($bd->temErro())
                  throw new Exception($bd->getErro());
            else{
                  $resp = $resp[0];
                  if ($bd->count() == 0)
                        throw new Exception('Usuario nao encontrado!');
                  else if ($resp->ativo == 0)
                        throw new Exception('Conta inativa!\nEntre em contato com os administradores para poder reativa-la.');
                  else{
                        $access_token = GerarPayloadJWT($resp,get_client_ip(), True);
                        $refresh_token = GerarPayloadJWT($resp,get_client_ip(), False);
                        $jwt = json_decode(sprintf('{ 
                              "status": 200,
                              "message": "Refresh Token realizado com sucesso",
                              "token_type": "Bearer",
                              "expires_in": %s,
                              "expires_on": %s,
                              "access_token": "%s",
                              "refresh_token": "%s"
                        }',$access_token->duracao,$access_token->exp,JWTEncoder($access_token),JWTEncoder($refresh_token)));
                        return $response->write(json_encode($jwt));
                  }
            }
      }catch(Exception $e){
            return $response->withJson( ['status'=>401, 'message'=>$e->getMessage()],401 );
      } 
});
    

$app->get('/login/lembrarsenha',function (Request $request, Response $response, array $args) 
{     
      $email = $request->getQueryParam('email');
      $bd = new banco();
      $bd->prepara("select apelido, ativo from usuario where email=:email");  
      $bd->parametro("email",$email);
      $obj = $bd->executar();
      if ($bd->count()>0)
        $obj = $obj[0];
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
                        "/frontend/index.php?op=lembrarsenha&codigo=",
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
                        "/frontend/index.php?op=registrar&codigo=",
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
                        "/frontend/index.php?op=reativar&codigo=",
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

$app->post('/login/hs256',function (Request $request, Response $response, array $args) 
{
      try{
            $obj = json_decode($request->getBody());
            if (!property_exists($obj,'texto'))
                  throw new Exception("faltou o campo texto no corpo");
            $texto = $obj->texto;
            $msg = hs256($texto);
            return $response->write('{"status":200, "message":"'.$msg.'"}');
      }catch(Exception $e){
            return $response->withJson( ['status'=>401, 'message'=>$e->getMessage()],401 );
      } 
});

$app->post('/login/jwt',function (Request $request, Response $response, array $args) 
{
      try{
            $obj = json_decode($request->getBody());
            $msg = JWTEncoder($obj);  
            return $response->write('{"status":200, "message":"'.json_encode($msg).'"}');     
      }catch(Exception $e){
            return $response->withJson( ['status'=>401, 'message'=>$e->getMessage()],401 );
      } 
});
 
$app->post('/login/jwt/valida',function (Request $request, Response $response, array $args) 
{
      $obj = null;
      $jwt = null;
      try{
            $req = json_decode($request->getBody());
            $vet = explode('.',$req->token);
            $jwt = json_decode(sprintf('{"header": %s, "payload": %s}',
                  fromBase64($vet[0]),fromBase64($vet[1]) ));
            $obj = checarToken($req->token,get_client_ip(), $req->tipo);
            return $response->getBody()->write( sprintf('{"status":200, "jwt":%s, "payload":%s}'
                  ,json_encode($jwt),json_encode($obj) ));  
      }catch(Exception $e){
            $response = $response->withStatus(401);
            return $response->getBody()->write(sprintf('{"status":401, "message":"%s", "jwt":%s, "payload":%s}',
                   $e->getmessage(),json_encode($jwt),json_encode($obj)));
      }
});

?>
