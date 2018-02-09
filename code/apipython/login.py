import json
import utils
from bottle import HTTPResponse, route, request, response, get, post, put, delete
from bd import banco 
import hashlib
from datetime import datetime
import email_
import hashlib

@post('/login/logar')
def logar():
    try:
        obj = json.load(request.body) #request.json #
        if obj['grant_type'] != 'password':
            raise Exception('grant_type diferente de password')
        elif obj['client_id'] != 'viagem':
            raise Exception('client_id nao permitido')
        elif obj['client_secret'] != '123':
            raise Exception('cliente_secret nao validado')
        elif obj['scope'] != 'admin':
            raise Exception('scope nao permitido')
        bd = banco()
        bd.conectar()
        bd.prepara("select id, apelido, nome, email, senha, ativo from usuario where email=%s",(obj['username'],))
        resp = bd.executar()
        bd.desconectar()
        if bd.temErro():
            raise Exception(bd.getErro())
        else:
            resp = resp[0]
            senha = hashlib.md5(obj['password'].encode('utf-8')).hexdigest()
            if bd.count() == 0 or resp['senha'] != senha:
                raise Exception('Login ou Senha Invalido!')
            elif resp['ativo'] == 0:
                raise Exception('Conta inativa!\nEntre em contato com os administradores para poder reativa-la.')
            else:
              access_token = utils.GerarPayloadJWT(resp,request.remote_addr, True)
              refresh_token = utils.GerarPayloadJWT(resp,request.remote_addr, False)
              jwt = { 
                    "status": 200,
                    "message": "Login realizado com sucesso",
                    "token_type": "Bearer",
                    "expires_in": access_token["duracao"],
                    "expires_on": access_token["exp"],
                    "access_token": utils.JWTEncoder(access_token),
                    "refresh_token": utils.JWTEncoder(refresh_token)
                }
              return utils.mySQLtoJSON(jwt) 
    except Exception as e:
       return HTTPResponse({'status':401, 'message': str(e)}, 400)
    except :
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)

@post('/login/refreshtoken')
def refreshtoken():
    try:
        obj = json.load(request.body) #request.json #
        if obj['grant_type'] != 'refresh_token':
            raise Exception('grant_type diferente de password')
        elif obj['client_id'] != 'viagem':
            raise Exception('client_id nao permitido')
        elif obj['client_secret'] != '123':
            raise Exception('cliente_secret nao validado')
        elif obj['scope'] != 'admin':
            raise Exception('scope nao permitido')
        elif obj['refresh_token'] == None:
            raise Exception('refresh_token é obrigatório')
        refresh_token = obj['refresh_token']
        obj = utils.checarToken(refresh_token, request.remote_addr, "Refresh")
        bd = banco()
        bd.conectar()
        bd.prepara("select id, apelido, nome, email, senha, ativo from usuario where email=%s",(obj['email'],))
        resp = bd.executar()
        bd.desconectar()
        if bd.temErro():
            raise Exception(bd.getErro())
        else:
            resp = resp[0]
            if bd.count() == 0:
                raise Exception('Usuario nao encontrado!')
            elif resp['ativo'] == 0:
                raise Exception('Conta inativa!\nEntre em contato com os administradores para poder reativa-la.')
            else:
              access_token = utils.GerarPayloadJWT(resp,request.remote_addr, True)
              refresh_token = utils.GerarPayloadJWT(resp,request.remote_addr, False)
              jwt = { 
                    "status": 200,
                    "message": "Refresh Token realizado com sucesso",
                    "token_type": "Bearer",
                    "expires_in": access_token["duracao"],
                    "expires_on": access_token["exp"],
                    "access_token": utils.JWTEncoder(access_token),
                    "refresh_token": utils.JWTEncoder(refresh_token)
                }
              return utils.mySQLtoJSON(jwt) 
    except Exception as e:
       return HTTPResponse({'status':401, 'message': str(e)}, 400)
    except:
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)

@post('/login/hs256')
def testahash():
    try:
        obj = json.load(request.body)
        if (obj['texto']) == None:
            raise "faltou o campo texto no corpo"
        texto = obj['texto']
        msg = utils.hs256(texto)
        return '{"status":200, "message":"'+msg+'"}'
    except Exception as e:
       return HTTPResponse({'status':401, 'message': str(e)}, 400)
    except:
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)

@post('/login/jwt')
def testajwt():
    try:
        obj = json.load(request.body)
        msg = utils.JWTEncoder(obj)
        return '{"status":200, "token":"'+msg+'"}'
    except Exception as e:
       return HTTPResponse({'status':401, 'message': str(e)}, 400)
    except:
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)

@post('/login/jwt/valida')
def validajwt():
    obj = None
    jwt = None
    try:
        req = json.load(request.body)
        vet = req['token'].split('.')
        jwt = {'header': json.loads(utils.fromBase64(vet[0])), 
               'payload': json.loads(utils.fromBase64(vet[1]))
        }
        obj = utils.checarToken(req['token'],request.remote_addr, req['tipo'])       
        return '{"status":200,"jwt":'+utils.mySQLtoJSON(jwt)+' "obj":'+utils.mySQLtoJSON(obj)+'}'
    except Exception as e:
       return HTTPResponse({'status':401, 'message': str(e), 'jwt':jwt, 'payload':obj}, 400)
    except:
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)