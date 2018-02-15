import json
from datetime import datetime, timedelta, date
from random import randint
import urllib.request as req
from Crypto.Cipher import AES, blockalgo
from Crypto import Random
import base64
import hashlib
import hmac
import base64
import constantes

def json_handler(show_time=True):
    def decorated(obj):
        if isinstance(obj, datetime):
            return obj.strftime('%F %T' if show_time else '%F')
        if isinstance(obj, date):
            return obj.strftime('%F')
        return repr(obj) # catch-all
    return decorated

def fnEncrypt(text):
    text = text + '\0' * (AES.block_size - len(text) % AES.block_size)
    #AES.MODE_EBC é igual a 1  por isso o 1 nas chamada
    aes = AES.new(constantes._OAUTH_CHAVE_, 1  , Random.new().read(AES.block_size))
    return base64.b64encode(aes.encrypt(text))

def fnDecrypt(text):
    #AES.MODE_EBC é igual a 1  por isso o 1 nas chamada
    aes = AES.new(constantes._OAUTH_CHAVE_, 1 , Random.new().read(AES.block_size))
    return aes.decrypt(base64.b64decode(text)).decode("utf-8").strip('\0')

def mySQLtoJSON(data):
    return json.dumps(data, default=json_handler(True))

def gerarSenha(qtd = 8):
    LETRAS = 'abcdefghijklmnopqrstuvwxyz1234567890'
    tam = len(LETRAS)
    senha = ''
    for i in range(0,qtd):
        senha = senha + LETRAS[randint(1, tam)-1]
    return senha.encode('utf-8')

def gerarChave(email, motivo):
    data = datetime.utcnow() + timedelta(hours=constantes._OAUTH_CHAVES_VALIDADE_)
    texto = '{"email":"%s", "data":"%s", "motivo":"%s"}' % (email, data.strftime('%F %T'), motivo)
    return req.pathname2url(fnEncrypt(texto))

def obterChave(texto):
    tempo = datetime.utcnow()
    obj = json.loads(fnDecrypt(texto))
    delta = (tempo - datetime.strptime(obj['data'], '%Y-%m-%d %H:%M:%S')).total_seconds()
    obj["expirado"] = delta >0#1 if delta >0 else 0  #python operador ternario ex: (time()-$obj->data)>0?1:0;
    obj["tempo"] = delta
    return obj

def toBase64(valor):
    if isinstance(valor,str):
        valor = valor.encode("utf-8")
    elif isinstance(valor, dict):
        valor = mySQLtoJSON(valor).encode("utf-8")
    return base64.b64encode(valor).decode("utf-8").strip('==')

def fromBase64(valor):
    valor += "=" * ((4 - len(valor) % 4) % 4)   
    valor = base64.b64decode(valor)
    valor = valor.decode("utf-8")
    return valor     

def JWTEncoder(dados):
    header = {
        "typ":"JWT",
        "alg":"HS256"        
    }
    texto = toBase64(header) + "." + toBase64(dados)
    texto = texto + "." + hs256(texto)
    return texto
    #return jwt.encode(dados, chave, algorithm='HS256').decode("utf-8")

def JWTDecoder(token):
    try:
        vet = token.split('.')
        header = json.loads(fromBase64(vet[0]))
        payload = json.loads(fromBase64(vet[1]))
        assinatura = hs256(vet[0]+"."+vet[1])
        if assinatura != vet[2]:
            raise Exception("token invalido!")
        return payload
    except Exception as e:
        raise Exception("token em formato invalido")
    #return jwt.decode(token, chave, algorithms=['HS256'])

def GerarPayloadJWT(usuario, iporigem, AccessToken = True):
    delta = timedelta(minutes=constantes._OAUTH_ACCESS_TOKEN_VALIDADE_ if AccessToken else constantes._OAUTH_REFRESH_TOKEN_VALIDADE_)
    payload = {
        'iss':iporigem,
        'iat':int(datetime.utcnow().timestamp()),
        'exp':int((datetime.utcnow() + delta).timestamp()),
        'sub':usuario['id'],
        'tipo':constantes._OAUTH_TIPO_TOKEN_ACESSO_ if AccessToken else constantes._OAUTH_TIPO_TOKEN_REFRESH_,
        'duracao':delta.total_seconds(),
        'email':usuario['email'],
        'perfil':constantes._OAUTH_PERFIL_PADRAO_, #todo: pegar o perfil real no banco
        'apelido':usuario['apelido']
    }
    return payload

def checarToken(token, iporigem, tipo = constantes._OAUTH_TIPO_TOKEN_DEFAULT_, perfil = constantes._OAUTH_PERFIL_PADRAO_):
    try:
        delta = timedelta(minutes=constantes._OAUTH_ACCESS_TOKEN_VALIDADE_ if tipo == constantes._OAUTH_TIPO_TOKEN_ACESSO_ else constantes._OAUTH_REFRESH_TOKEN_VALIDADE_)
        obj = JWTDecoder(token)
        #if iporigem != obj['iss']:
        #   raise Exception("Origem invalida!")
        if 'tipo' in obj and obj['tipo'] != tipo:
            raise Exception("Tipo de token incompativel")
        elif 'perfil' in obj and obj['perfil'] != perfil and tipo==constantes._OAUTH_TIPO_TOKEN_ACESSO_:
            raise Exception("Este usuario nao possui autorizacao para usar este recurso")
        elif obj['exp'] < int((datetime.utcnow()).timestamp()):
            raise Exception("Token Expirado")
        return obj
    except Exception as e:
            raise e

def controlaAcesso(request, perfil = constantes._OAUTH_PERFIL_PADRAO_):
    x = request.get_header('Authorization')
    if x == None:
        raise Exception("acesso nao permitido\nAuthorization nao encontrado")
    x = x.strip().split()
    if x[0].lower()!='bearer':
        raise Exception("Autorization deve ser do tipo bearer")
    else:
        checarToken(x[1], request.remote_addr,constantes._OAUTH_TIPO_TOKEN_DEFAULT_, perfil)
    
def hs256(text):
    hash_obj = hmac.new(constantes._OAUTH_CHAVE_.encode('utf-8'), text.encode('utf-8'), hashlib.sha256)
    block = hash_obj.hexdigest()
    return toBase64(block)


