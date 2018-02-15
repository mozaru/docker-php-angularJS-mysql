
function define(name, value) {
    Object.defineProperty(exports, name, {
        value:      value,
        enumerable: true
    });
}

//servidor bd
define('_BD_HOST_'                      , 'bd');
define('_BD_DATABASE_'                  , 'viagem');
define('_BD_LOGIN_'                     , 'operador');
define('_BD_PASSWORD_'                  , '123456');

//servidor de email
define('_EMAIL_HOST_'                   , 'smtp.gmail.com');
define('_EMAIL_PORT_'                   , 587);
define('_EMAIL_PROTOCOL_'               , 'tls');
define('_EMAIL_USER_'                   , 'emaillixo21@gmail.com');
define('_EMAIL_PASSWORD_'               , 'qwe123!@#');
define('_SERVER_HOST_'                  , 'http://localhost:8080');

//OAUTH 2.0
define('_OAUTH_CHAVE_'                  , 'TESTE de CHAVE DE ACESSO 2017012'); //tem que ter comprimento de  8, 16, 32, 64  
define('_OAUTH_CHAVES_VALIDADE_'        , 2); //em horas
define('_OAUTH_ACCESS_TOKEN_VALIDADE_'  , 5); //em minutos
define('_OAUTH_REFRESH_TOKEN_VALIDADE_' , 30);//em minutos
define('_OAUTH_TIPO_TOKEN_ACESSO_'      , 'Acesso');
define('_OAUTH_TIPO_TOKEN_REFRESH_'     , 'Refresh');
define('_OAUTH_PERFIL_PADRAO_'          , 'Admin');
define('_OAUTH_TIPO_TOKEN_DEFAULT_'     , this._OAUTH_TIPO_TOKEN_ACESSO_);

