<?php

define('chave', 'TESTE de CHAVE DE ACESSO 2017012'); //tem que ter comprimento de  8, 16, 32, 64  
define('validade', 2); //em horas


function fnEncrypt($texto)
{
    return  rtrim( base64_encode(
            mcrypt_encrypt(
                MCRYPT_RIJNDAEL_256,
                chave, trim($texto), 
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

function fnDecrypt($chave)
{
    return rtrim(
        mcrypt_decrypt(
            MCRYPT_RIJNDAEL_256, 
            chave, 
            base64_decode($chave), 
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
    $texto = sprintf('{"email":"%s", "data":%d, "motivo":"%s"}',$email, time() + (validade * 60 * 60), $motivo);
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

?>