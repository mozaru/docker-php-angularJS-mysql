const constantes = require('./constantes.js');
const crypto = require('crypto');
const util = require('util');

function formataData(data)
{  
    return data.toJSON();//.replace(/T/, ' ').replace(/\..+/, ''); 
}

function obterIpCliente(req)
{
    return req.headers['x-forwarded-for'] || req.connection.remoteAddress;
}

function fnEncrypt(text)
{
    //text = text + '\0' * (AES.block_size - len(text) % AES.block_size)
    //AES.MODE_EBC é igual a 1  por isso o 1 nas chamada
    var cipher = crypto.createCipheriv('aes-256-ecb',constantes._OAUTH_CHAVE_,"");
    var crypted = cipher.update(text,'utf8','hex');
    crypted += cipher.final('hex');
    return toBase64(crypted);
}

function fnDecrypt(text){
    //AES.MODE_EBC é igual a 1  por isso o 1 nas chamada
    var decipher = crypto.createDecipheriv('aes-256-ecb',constantes._OAUTH_CHAVE_,"");
    text = fromBase64(text);
    var dec = decipher.update(text,'hex','utf8')
    dec += decipher.final('utf8');
    return dec;
}

function gerarSenha(qtd = 8){
    LETRAS = 'abcdefghijklmnopqrstuvwxyz1234567890';
    tam = LETRAS.length;
    senha = '';
    for (i=0;i<qtd;i++)
        senha = senha + LETRAS[Math.floor(Math.random() * tam)];
    return senha;
}

function gerarChave(email, motivo){
    data = new Date();
    data.setHours(data.getHours()+constantes._OAUTH_CHAVES_VALIDADE_);
    texto = util.format('{"email":"%s", "data":"%s", "motivo":"%s"}', email, formataData(data), motivo);
    return encodeURIComponent(fnEncrypt(texto));
}

function obterChave(texto){
    tempo = Date.now();
    obj = JSON.parse(fnDecrypt(texto));
    delta = Math.floor( (tempo - Date.parse(obj['data']))/1000 );
    obj["expirado"] = delta >0;  //1 if delta >0 else 0  #python operador ternario ex: (time()-$obj->data)>0?1:0;
    obj["tempo"] = delta;
    return obj;
}

function toBase64(valor){
    if (typeof valor == 'object')
        valor = JSON.stringify(valor);
    return (new Buffer(valor)).toString('base64').replace(/^\=+|\=+$/g, '');
}

function fromBase64(valor){
    var tam = (4 - valor.length % 4)%4;
    for (i=0;i<tam;i++)
        valor = valor + '=';
    return (new Buffer(valor, 'base64').toString('utf8'));
}

function JWTEncoder(dados){
    header = {
        "typ":"JWT",
        "alg":"HS256"        
    };
    texto = toBase64(header) + "." + toBase64(dados);
    texto = texto + "." + hs256(texto);
    return texto;
}

function JWTDecoder(token){
    try{
        vet = token.split('.');
        header = JSON.parse(fromBase64(vet[0]));
        payload = JSON.parse(fromBase64(vet[1]));
        assinatura = hs256(vet[0]+"."+vet[1]);
        if (assinatura != vet[2])
            throw new Error("token invalido!");
        return payload;
    }catch(e){
        throw Error("token em formato invalido");
    }
}

function GerarPayloadJWT(usuario, iporigem, AccessToken = true)
{
    delta = 1000*60* (AccessToken?constantes._OAUTH_ACCESS_TOKEN_VALIDADE_ : constantes._OAUTH_REFRESH_TOKEN_VALIDADE_); //min para millisecs
    payload = {
        'iss':iporigem,
        'iat':Math.floor(Date.now()),
        'exp':Math.floor(Date.now()+delta),
        'sub':usuario['id'],
        'tipo':AccessToken? constantes._OAUTH_TIPO_TOKEN_ACESSO_ : constantes._OAUTH_TIPO_TOKEN_REFRESH_,
        'duracao':Math.floor(delta/1000), //millisecs to sec
        'email':usuario['email'],
        'perfil':constantes._OAUTH_PERFIL_PADRAO_, //todo: pegar o perfil real no banco
        'apelido':usuario['apelido']
    };
    return payload;
}

function checarToken(token, iporigem, tipo = constantes._OAUTH_TIPO_TOKEN_DEFAULT_, perfil = constantes._OAUTH_PERFIL_PADRAO_)
{
    try{
        delta = 1000*60* (tipo == constantes._OAUTH_TIPO_TOKEN_ACESSO_?constantes._OAUTH_ACCESS_TOKEN_VALIDADE_ : constantes._OAUTH_REFRESH_TOKEN_VALIDADE_); //min para millisecs
        obj = JWTDecoder(token);
        //if iporigem != obj['iss']:
        //   throw new Error("Origem invalida!")
        if ('tipo' in obj && obj['tipo'] != tipo)
            throw new Error("Tipo de token incompativel");
        else if ('perfil' in obj && obj['perfil'] != perfil && tipo==constantes._OAUTH_TIPO_TOKEN_ACESSO_)
            throw new Error("Este usuario nao possui autorizacao para usar este recurso");
        else if (obj['exp'] < Date.now())
            throw new Error("Token Expirado");
        return obj;
    }catch(e){
            throw e;
    }
}


function controlaAcesso(request, perfil = constantes._OAUTH_PERFIL_PADRAO_){
    x = request.headers['authorization'];
    if (x === undefined || x == null || x.length <= 0)
        throw new Error("acesso nao permitido\nAuthorization nao encontrado");
    x = x.replace(/^\s*|\s*$/g, '').split(' ');
    if (x[0].toLowerCase()!='bearer')
        throw new Error("Autorization deve ser do tipo bearer");
    else
        checarToken(x[1], obterIpCliente(request) ,constantes._OAUTH_TIPO_TOKEN_DEFAULT_, perfil);
}

function hs256(text){

    const hash = crypto.createHmac('sha256', constantes._OAUTH_CHAVE_)
                   .update(text)
                   .digest('hex');
    return toBase64(hash);
}

module.exports={
    formataData,
    gerarChave,
    obterChave,
    gerarSenha,
    JWTEncoder,
    JWTDecoder,
    GerarPayloadJWT,
    checarToken,
    controlaAcesso,
    obterIpCliente,
    fromBase64,
    hs256
};