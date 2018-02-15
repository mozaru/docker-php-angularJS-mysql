import json
import utils
from bottle import HTTPResponse, route, request, response, get, post, put, delete
from bd import banco 
import hashlib
from datetime import datetime
import email_

@get('/usuarios')
def listar():
    try:
        utils.controlaAcesso(request)
        bd = banco()
        bd.conectar()
        bd.prepara("select id, apelido, nome, email, ativo, data from usuario order by nome")
        data = bd.executar()
        bd.desconectar()
        if bd.temErro():
            raise Exception(bd.getErro())
        else:
            return utils.mySQLtoJSON(data)
    except Exception as e:
       return HTTPResponse({'status':401, 'message': str(e)}, 400)
    except :
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)

@get('/usuarios/<id>')
def obter(id):
    try:
        utils.controlaAcesso(request)
        bd = banco()
        bd.conectar()
        bd.prepara("select id, apelido, nome, email, ativo, senha from usuario where id=%s",(id,))
        data = bd.executar()
        bd.desconectar()
        if bd.temErro():
            raise Exception(bd.getErro())
        else:
            return utils.mySQLtoJSON(data)
    except Exception as e:
       return HTTPResponse({'status':401, 'message': str(e)}, 400)
    except :
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)

@post('/usuarios/inserir')
def inserir():
    try:
        utils.controlaAcesso(request)
        obj = json.load(request.body) #request.json #
        bd = banco()
        bd.conectar()
        bd.prepara('select apelido from usuario where email = %s ',(obj['email'],))
        bd.executar()
        if bd.count()!=0:
            raise Exception("Email já cadastrado!")
        elif len(obj['nome'])==0:
            raise Exception("O nome não pode estar em branco!")
        elif len(obj['apelido'])==0:
            raise Exception("O apelido não pode estar em branco!")
        elif len(obj['email'])==0:
            raise Exception("O email não pode estar em branco!")
        else:
            dt = datetime.now()
            senha = utils.gerarSenha()
            hash = hashlib.md5(senha).hexdigest()
            bd.prepara("INSERT INTO usuario (apelido, nome, email, senha, data) values (%s, %s, %s, %s, %s)", (obj['apelido'],obj['nome'],obj['email'],hash, dt.strftime("%Y-%m-%d %H:%M:%S")))
            data = bd.executar()
            bd.desconectar()
            if bd.temErro():
                raise Exception(bd.getErro())
            elif bd.count()==0:
                raise Exception("Nao foi possivel inserir os dados do usuario "+obj['email']+"!")
            else:
                return '{"status":200, "message":"Usuario registrado com sucesso!"}'
    except Exception as e:
        return HTTPResponse({'status':401, 'message': str(e)}, 400)
    except :
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)

@delete('/usuarios/<id>')
def remover(id):
    try:
        utils.controlaAcesso(request)
        bd = banco()
        bd.conectar()
        bd.prepara("select email from usuario where id=%s",(id,))
        resp = bd.executar()
        if bd.temErro():
            raise Exception(bd.getErro())
        elif bd.count() == 0:
            raise Exception('Usuario nao encontrado!')
        else:
            email = resp[0]['email']         
            bd.prepara("DELETE from usuario where id=%s",(id,))
            bd.executar()
            bd.desconectar()
            if bd.temErro():
                raise Exception(bd.getErro())
            elif bd.count() == 0:
                raise Exception('Nao foi possivel remover o usuario '+email)
            else:
                return '{"status":200, "message":"Usuario '+email+' removido com sucesso!"}'
    except Exception as e:
        return HTTPResponse({'status':401, 'message': str(e)}, 401)
    except:
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 401)

@post('/usuarios/<id>')
@put('/usuarios/<id>')
def alterar(id):
    try:
        utils.controlaAcesso(request)
        bd = banco()
        bd.conectar()
        bd.prepara("select email from usuario where id=%s",(id,))
        resp = bd.executar()
        if bd.temErro():
            raise Exception(bd.getErro())
        elif bd.count() == 0:
            raise Exception('Usuario nao encontrado!')
        else:
            email = resp[0]['email']
            obj = json.load(request.body) #request.json #
            bd.prepara('select apelido from usuario where email = %s and id <> %s',(obj['email'],id))
            bd.executar()
            if bd.count()!=0:
                raise Exception("Email já cadastrado!")
            elif len(obj['nome'])==0:
                raise Exception("O nome não pode estar em branco!")
            elif len(obj['apelido'])==0:
                raise Exception("O apelido não pode estar em branco!")
            elif len(obj['email'])==0:
                raise Exception("O email não pode estar em branco!")
            else:
                bd.prepara("UPDATE usuario set apelido=%s, nome=%s, email=%s where id=%s", (obj['apelido'],obj['nome'],obj['email'], id))
                data = bd.executar()
                bd.desconectar()
                if bd.temErro():
                    raise Exception(bd.getErro())
                elif bd.count()==0:
                    raise Exception("Nao foi possivel alterar os dados do usuario "+email+"!")
                else:
                    return '{"status":200, "message":"Usuario '+obj['email']+' alterado com sucesso!"}'
    except Exception as e:
        return HTTPResponse({'status':401, 'message': str(e)}, 400)
    except :
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)

@post('/usuarios/ativar/<id>')
def ativar(id):
    try:
        utils.controlaAcesso(request)
        bd = banco()
        bd.conectar()
        bd.prepara("select email from usuario where id=%s",(id,))
        resp = bd.executar()
        if bd.temErro():
            raise Exception(bd.getErro())
        elif bd.count() == 0:
            raise Exception('Usuario nao encontrado!')
        else:
            email = resp[0]['email']
            bd.prepara("UPDATE usuario set ativo=1 where id=%s", (id,))
            data = bd.executar()
            bd.desconectar()
            if bd.temErro():
                raise Exception(bd.getErro())
            elif bd.count()==0:
                raise Exception("Nao foi possivel ativar o usuario "+email+"!")
            else:
                return '{"status":200, "message":"Usuario '+email+' ativado com sucesso!"}'
    except Exception as e:
        return HTTPResponse({'status':401, 'message': str(e)}, 400)
    except:
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)

@post('/usuarios/desativar/<id>')
def desativar(id):
    try:
        utils.controlaAcesso(request)
        bd = banco()
        bd.conectar()
        bd.prepara("select email from usuario where id=%s",(id,))
        resp = bd.executar()
        if bd.temErro():
            raise Exception(bd.getErro())
        elif bd.count() == 0:
            raise Exception('Usuario nao encontrado!')
        else:
            email = resp[0]['email']
            bd.prepara("UPDATE usuario set ativo=0 where id=%s", (id,))
            data = bd.executar()
            bd.desconectar()
            if bd.temErro():
                raise Exception(bd.getErro())
            elif bd.count()==0:
                raise Exception("Nao foi possivel desativar o usuario "+email+"!")
            else:
                return '{"status":200, "message":"Usuario '+email+' desativado com sucesso!"}'

    except Exception as e:
        return HTTPResponse({'status':401, 'message': str(e)}, 400)
    except:
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)

@post('/usuarios/resetar/<id>')
def resetar(id):
    try:
        utils.controlaAcesso(request)
        bd = banco()
        bd.conectar()
        bd.prepara("select email from usuario where id=%s",(id,))
        resp = bd.executar()
        if bd.temErro():
            raise Exception(bd.getErro())
        elif bd.count() == 0:
            raise Exception('Usuario nao encontrado!')
        else:
            email = resp[0]['email']
            hash = hashlib.md5(utils.gerarSenha()).hexdigest()
            bd.prepara("UPDATE usuario set senha=%s where id=%s", (hash, id))
            data = bd.executar()
            bd.desconectar()
            if bd.temErro():
                raise Exception(bd.getErro())
            elif bd.count()==0:
                raise Exception("Nao foi possivel resetar a senha do usuario "+email+"!")
            else:
                return '{"status":200, "message":"Senha do usuario '+email+' resetada com sucesso!"}'
    except Exception as e:
        return HTTPResponse({'status':401, 'message': str(e)}, 400)
    except:
        return HTTPResponse({'status':401, 'message':'erro inexperado!'}, 400)

