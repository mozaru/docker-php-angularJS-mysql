import json
import utils
from bottle import HTTPResponse, route, request, response, get, post, put, delete
from bd import banco 
import hashlib
from datetime import datetime
import email_
import hashlib
import constantes

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
            senha = hashlib.md5(obj['password'].encode('utf-8')).hexdigest()
            if bd.count() == 0 or resp[0]['senha'] != senha:
                raise Exception('Login ou Senha Invalido!')
            elif resp[0]['ativo'] == 0:
                raise Exception('Conta inativa!\nEntre em contato com os administradores para poder reativa-la.')
            else:
                resp = resp[0] 
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
        elif bd.count() == 0:
            raise Exception('Usuario nao encontrado!')
        elif resp[0]['ativo'] == 0:
            raise Exception('Conta inativa!\nEntre em contato com os administradores para poder reativa-la.')
        else:
            resp = resp[0] 
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

@get('/login/lembrarsenha')
def solicitar_lembrarsenha():
    try:
        email = request.query['email']
        if email == None:
            raise Exception('email é obrigatorio!')
        bd = banco()
        bd.conectar()
        bd.prepara("select apelido, ativo from usuario where email=%s",(email,))
        obj = bd.executar()
        bd.desconectar()
        if bd.temErro():
            raise Exception(bd.getErro())
        elif bd.count()==0:
            raise Exception('Email não cadastrado!')
        else:
            obj=obj[0]
            if obj['ativo'] == 0:
                raise Exception('Esta conta está inativa!\nEntre em contato com os administradores para poder reativa-la!')
            else:
                ctr = email_.email()
                corpo = '{}\n\n para poder trocar a senha use o link abaixo\n\n{}/login.html?op=lembrarsenha&codigo={}\nAtt,\nSuporte Viagem'.format(
                    obj['apelido'],
                    constantes._SERVER_HOST_,
                    utils.gerarChave(email,'lembrarsenha'))
                if ctr.enviar(email, 'Lembrar Senha', corpo):
                    return{"status":200, "message":"Email Enviado para {}!".format(email)}
                else:
                    raise Exception('{"status":501, "message":"não foi possivel enviar email para {}"}'.format(email))
    except Exception as e:
        return HTTPResponse({'status':401, 'message': str(e)}, 400)
    except:
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)



@get('/login/registrar')
def solicitar_registro():
    try:
        email = request.query['email']
        if email == None:
            raise Exception('Email é obrigatorio!')
        bd = banco()
        bd.conectar()
        bd.prepara("select apelido, ativo from usuario where email=%s",(email,))
        obj = bd.executar()
        bd.desconectar()
        if bd.temErro():
            raise Exception(bd.getErro())
        elif bd.count()!=0:
            raise Exception('Email já cadastrado!')
        else:
            ctr = email_.email()
            corpo = '{},\n\n Para poder registrar, confirme o seu email, usando o link abaixo\n\n\n\n{}/login.html?op=registrar&codigo={}\nAtt,\nSuporte Viagem'.format(
                'Caro Usuario',
                constantes._SERVER_HOST_,
                utils.gerarChave(email,'registrar'))
            if ctr.enviar(email, 'Confirmação de Email', corpo):
                return{"status":200, "message":"Email Enviado para {}!".format(email)}
            else:
                raise Exception('{"status":501, "message":"não foi possivel enviar email para {}"}'.format(email))
    except Exception as e:
        return HTTPResponse({'status':401, 'message': str(e)}, 400)
    except:
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)

@get('/login/reativar')
def solicitar_reativar():
    try:
        email = request.query['email']
        if email == None:
            raise Exception('Email é obrigatorio!')
        bd = banco()
        bd.conectar()
        bd.prepara("select apelido, ativo from usuario where email=%s",(email,))
        obj = bd.executar()
        bd.desconectar()
        if bd.temErro():
            raise Exception(bd.getErro())
        elif bd.count()==0:
            raise Exception('Email não cadastrado!')
        else:
            obj=obj[0]
            if obj['ativo'] == 1:
                raise Exception('Conta ja estava ativa!')
            else:
                ctr = email_.email()
                corpo = '{},\n\n Para poder raativar sua conta clique no link abaixo\n\n\n\n{}/login.html?op=reativar&codigo={}\nAtt,\nSuporte Viagem'.format(
                    obj['apelido'],
                    constantes._SERVER_HOST_,
                    utils.gerarChave(email,'reativar'))
                if ctr.enviar(email, 'Reativar Conta', corpo):
                    return{"status":200, "message":"Email Enviado para {}!".format(email)}
                else:
                    raise Exception('{"status":501, "message":"não foi possivel enviar email para {}"}'.format(email))
    except Exception as e:
        return HTTPResponse({'status':401, 'message': str(e)}, 400)
    except:
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)


@post('/login/lembrarsenha')
def lembrarsenha():
    try:
        obj = json.load(request.body)
        codigo = utils.obterChave(obj['codigo'])
        if codigo['expirado']:
            raise Exception('Codigo Expirado!')
        elif codigo['email'] != obj['email']:
            raise Exception('O Codigo não é para este email!')
        elif obj['senha'] != obj['confirmasenha']:         
            raise Exception('A confirmaçao da senha não confere!')
        elif len(obj['senha'])==0:
            raise Exception('A senha não pode estar vazia!')
        elif codigo['motivo']!='lembrarsenha':
            raise Exception('O Codigo não é para esta operação!')
        bd = banco()
        bd.conectar()
        bd.prepara("select apelido, ativo from usuario where email=%s",(obj['email'],))
        bd.executar()
        bd.desconectar()
        if bd.temErro():
            raise Exception(bd.getErro())
        elif bd.count()==0:
            raise Exception('Email não cadastrado!')
        else:
            senha = hashlib.md5(obj['senha'].encode('utf-8')).hexdigest()
            bd.prepara("UPDATE usuario set senha=%s where email=%s",(senha,obj['email']))
            bd.executar()
            bd.desconectar()
            if bd.temErro():
                raise Exception(bd.getErro())
            elif bd.count()==0:
                raise Exception('Senha não atualizada!')
            return{"status":200, "message":"Senha do usuario {}, alterada com sucesso!!".format(obj['email'])}
    except Exception as e:
        return HTTPResponse({'status':401, 'message': str(e)}, 400)
    except:
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)


@post('/login/registrar')
def registrar():
    try:
        obj = json.load(request.body)
        codigo = utils.obterChave(obj['codigo'])
        if codigo['expirado']:
            raise Exception('Codigo Expirado!')
        elif codigo['motivo']!='registrar':
            raise Exception('O Codigo não é para esta operação!')
        elif codigo['email'] != obj['email']:
            raise Exception('O Codigo não é para este email!')
        elif len(obj['nome'])==0:
            raise Exception("O nome não pode estar em branco!")
        elif len(obj['apelido'])==0:
            raise Exception("O apelido não pode estar em branco!")
        elif len(obj['email'])==0:
            raise Exception("O email não pode estar em branco!")
        elif len(obj['senha'])==0:
            raise Exception('A senha não pode estar vazia!')
        elif obj['senha'] != obj['confirmasenha']:         
            raise Exception('A confirmaçao da senha não confere!')
        bd = banco()
        bd.conectar()
        bd.prepara("select apelido, ativo from usuario where email=%s",(obj['email'],))
        bd.executar()
        bd.desconectar()
        if bd.temErro():
            raise Exception(bd.getErro())
        elif bd.count()!=0:
            raise Exception('Email já cadastrado!')
        else:
            hash = hashlib.md5(obj['senha'].encode('utf-8')).hexdigest()
            dt = datetime.now() 
            bd.prepara("INSERT INTO usuario (apelido, nome, email, senha, data) values (%s, %s, %s, %s, %s)", (obj['apelido'],obj['nome'],obj['email'],hash, dt.strftime("%Y-%m-%d %H:%M:%S")))
            bd.executar()
            bd.desconectar()
            if bd.temErro():
                raise Exception(bd.getErro())
            elif bd.count()==0:
                raise Exception('Usuário não cadastrado!')
            return {"status":200, "message":"Usuario {}, registrado com sucesso!".format(obj['email'])}
    except Exception as e:
        return HTTPResponse({'status':401, 'message': str(e)}, 400)
    except:
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)

@post('/login/reativar')
def reativar():
    try:
        obj = json.load(request.body)
        codigo = utils.obterChave(obj['codigo'])
        if codigo['expirado']:
            raise Exception('Codigo Expirado!')
        elif codigo['email'] != obj['email']:
            raise Exception('O Codigo não é para este email!')
        elif len(obj['senha'])==0:
            raise Exception('A senha não pode estar vazia!')
        elif codigo['motivo']!='reativar':
            raise Exception('O Codigo não é para esta operação!')
        bd = banco()
        bd.conectar()
        bd.prepara("select apelido, ativo, senha from usuario where email=%s",(obj['email'],))
        usuario = bd.executar()
        bd.desconectar()
        if bd.temErro():
            raise Exception(bd.getErro())
        elif bd.count()==0:
            raise Exception('Email não cadastrado!')
        else:
            usuario = usuario[0]
            senha = hashlib.md5(obj['senha'].encode('utf-8')).hexdigest()
            if usuario['ativo']==1:
                raise Exception('O Usuário já esta ativo!')
            elif senha!=usuario['senha']:
                raise Exception('A senha não confere!')
            bd.prepara("UPDATE usuario set ativo=1 where email=%s",(obj['email'],))
            bd.executar()
            bd.desconectar()
            if bd.temErro():
                raise Exception(bd.getErro())
            elif bd.count()==0:
                raise Exception('usuario {} nao ativado!'.format(obj['email']))
            return{"status":200, "message":"Usuario {}, ativado com sucesso!!".format(obj['email'])}
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