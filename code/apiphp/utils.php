<?php


require_once('constantes.php');


function get_client_ip() {
    $ipaddress = '';
    if ($_SERVER['HTTP_CLIENT_IP'])
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if($_SERVER['HTTP_X_FORWARDED_FOR'])
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if($_SERVER['HTTP_X_FORWARDED'])
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if($_SERVER['HTTP_FORWARDED_FOR'])
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if($_SERVER['HTTP_FORWARDED'])
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if($_SERVER['REMOTE_ADDR'])
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
 
    return $ipaddress;
}

function fnEncrypt($texto)
{
    return  rtrim( base64_encode(
            mcrypt_encrypt(
                MCRYPT_RIJNDAEL_256,
                _OAUTH_CHAVE_, trim($texto), 
                MCRYPT_MODE_ECB, 
                mcrypt_create_iv(
                    mcrypt_get_iv_size(
                        MCRYPT_RIJNDAEL_256, 
                        MCRYPT_MODE_ECB
                    ), 
                    MCRYPT_RAND)
                )
            ), "\0" );
}

function fnDecrypt($texto)
{
    return rtrim(
        mcrypt_decrypt(
            MCRYPT_RIJNDAEL_256, 
            _OAUTH_CHAVE_, 
            base64_decode($texto), 
            MCRYPT_MODE_ECB,
            mcrypt_create_iv(
                mcrypt_get_iv_size(
                    MCRYPT_RIJNDAEL_256,
                    MCRYPT_MODE_ECB
                ), 
                MCRYPT_RAND
            )
        ), "\0"
    );
}

function gerarChave($email, $motivo)
{
    $texto = sprintf('{"email":"%s", "data":%d, "motivo":"%s"}',$email, time() + (_OAUTH_CHAVES_VALIDADE_ * 60 * 60), $motivo);
    return urlencode(fnEncrypt($texto));
}
function obterChave($texto)
{
    $obj = json_decode(fnDecrypt($texto));  
    $obj->{"expirado"} = (time()-$obj->data)>0?1:0;
    $obj->{"tempo"} = (time()-$obj->data);
    return $obj;
}

function gerarSenha($qtd = 8)
{
    $LETRAS = 'abcdefghijklmnopqrstuvwxyz1234567890';
    $len = strlen($LETRAS);
    $senha = '';
    for ($i = 0; $i < $qtd; $i++) {
        $senha .= $LETRAS[mt_rand(1, $len)-1];
    }
    return str_shuffle($senha); //embaralha mais ainda :)
}

function toBase64($valor)
{
    if (is_object($valor))
        return rtrim(base64_encode(json_encode($valor)),'= ');
    else
        return rtrim(base64_encode($valor),'= ');
}

function fromBase64($valor)
{
    //valor += "=" * ((4 - len(valor) % 4) % 4)   
    return rtrim(base64_decode($valor));     
}

function JWTEncoder($dados)
{
    $header = json_decode('{
        "typ":"JWT",
        "alg":"HS256"        
    }');
    $texto = toBase64($header) . "." . toBase64($dados);
    $texto = $texto . "." . hs256($texto);
    return $texto;
}

function JWTDecoder($token)
{
    try{
        $vet = explode('.',$token);
        $header = json_decode(fromBase64($vet[0]));
        $payload = json_decode(fromBase64($vet[1]));
        $assinatura = hs256($vet[0].".".$vet[1]); 
        if ($assinatura != $vet[2])
            throw new Exception("token invalido!");
        return $payload;
    }catch( Exception $e){
        throw new Exception("token em formato invalido");
    }
}


function GerarPayloadJWT($usuario, $iporigem, $AccessToken = True)
{
    $delta = ($AccessToken?_OAUTH_ACCESS_TOKEN_VALIDADE_:_OAUTH_REFRESH_TOKEN_VALIDADE_)*60;//converter de minutos para segundos
    $payload = sprintf('{"iss":"%s","iat": %s,"exp": %s,"sub": "%s","tipo":"%s", "duracao":%s, "email":"%s", "perfil":"%s","apelido":"%s"}', 
        $iporigem, 
        time(),
        time()+$delta,
        $usuario->id,
        $AccessToken?_OAUTH_TIPO_TOKEN_ACESSO_ : _OAUTH_TIPO_TOKEN_REFRESH_,
        $delta,
        $usuario->email,
        _OAUTH_PERFIL_PADRAO_, #todo: pegar o perfil real no banco
        $usuario->apelido);
    return json_decode($payload);
}

function checarToken($token, $iporigem, $tipo = _OAUTH_TIPO_TOKEN_DEFAULT_, $perfil = _OAUTH_PERFIL_PADRAO_)
{
    try{
        $delta = ($AccessToken?_OAUTH_ACCESS_TOKEN_VALIDADE_:_OAUTH_REFRESH_TOKEN_VALIDADE_)*60;//converter de minutos para segundos
        $obj = JWTDecoder($token);
        /*if ($iporigem != $obj->iss)
            throw new Exception("Origem invalida!");
        else */if (!property_exists($obj,'tipo') || $obj->tipo != $tipo)
            throw new Exception("Tipo de token incompativel");
        else if ((!property_exists($obj, 'perfil') || $obj->perfil != $perfil) && $tipo==_OAUTH_TIPO_TOKEN_ACESSO_)
            throw new Exception("Este usuario nao possui autorizacao para usar este recurso");
        else if (!property_exists($obj, 'exp'))
            throw new Exception("Token nao possui o tempo de expiracao 'exp'");
        else if ($obj->exp < time())
            throw new Exception("Token Expirado");
        return $obj;
    }catch (Exception $e){
            throw new Exception($e->getMessage());
    }
}

function controlaAcesso($request, $perfil = _OAUTH_PERFIL_PADRAO_)
{
    if (!$request->hasHeader('Authorization'))
        throw new Exception("acesso nao permitido\nAuthorization nao encontrado");
    $x = $request->getHeader('Authorization')[0];
    $x = explode(' ',trim($x));
    if (strtolower($x[0])!='bearer')
        throw new Exception("Autorization deve ser do tipo bearer");
    else
        checarToken($x[1], get_client_ip(),_OAUTH_TIPO_TOKEN_DEFAULT_, $perfil);
}

function hs256($texto)
{   
    $block = hash_hmac('SHA256', $texto, _OAUTH_CHAVE_, false);
    return toBase64($block);
}


?>