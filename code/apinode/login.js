const express = require('express');
const app = express();         
const bodyParser = require('body-parser');
const port = process.env.PORT || 3000; //porta padrão
const banco = require('./bd.js');
const email = require('./email.js');
const crypto = require('crypto');
const util = require('util');

const constantes = require('./constantes.js');

const utils = require('./utils');

module.exports = function(app){
    app.post('/login/logar', function (req, res) {
        try{
            var obj = req.body;
            if  (obj['grant_type'] != 'password')
                throw new Error('grant_type dif (erente de password');
            else if (obj['client_id'] != 'viagem')
                throw new Error('client_id nao permitido');
            else if (obj['client_secret'] != '123')
                throw new Error('cliente_secret nao validado');
            else if (obj['scope'] != 'admin')
                throw new Error('scope nao permitido');
            bd = new banco();
            bd.conectar();
            bd.prepara('select id, apelido, nome, email, senha, ativo from usuario where email=?',[obj['username']]);
            bd.executar(function(resp){
                try{
                    if (bd.temErro())
                        throw new Error(bd.getErro());
                    else{
                        var senha = crypto.createHash('md5').update(obj['password']).digest('hex');
                        if (bd.count()==0 || resp[0]['senha']!= senha)
                            throw new Error('Login ou Senha Invalido!');
                        else if (resp[0]['ativo']==0)
                            throw new Error('Conta inativa!\nEntre em contato com os administradores para poder reativa-la.');
                        else{
                            resp = resp[0];
                            access_token = utils.GerarPayloadJWT(resp, utils.obterIpCliente(req), true);
                            refresh_token = utils.GerarPayloadJWT(resp,utils.obterIpCliente(req), false);
                            jwt = { 
                                    "status": 200,
                                    "message": "Login realizado com sucesso",
                                    "token_type": "Bearer",
                                    "expires_in": access_token["duracao"],
                                    "expires_on": access_token["exp"],
                                    "access_token": utils.JWTEncoder(access_token),
                                    "refresh_token": utils.JWTEncoder(refresh_token)
                                };                                        
                            res.json(jwt);   
                        }
                    }
                }catch(e){
                    res.status(401);
                    res.send('{"status":401, "message":"' + e.message +'"}');
                }
            });
        }catch(e){
            res.status(401);
            res.send('{"status":401, "message":"' + e.message +'"}');
        }
    });

    app.post('/login/refreshtoken', function (req, res) {
        try{
            var obj = req.body; //request.json //
            if (obj['grant_type'] != 'refresh_token')
                throw new Error('grant_type diferente de password');
            else if (obj['client_id'] != 'viagem')
                throw new Error('client_id não permitido');
            else if (obj['client_secret'] != '123')
                throw new Error('cliente_secret não validado');
            else if (obj['scope'] != 'admin')
                throw new Error('scope não permitido');
            else if (!obj['refresh_token'])
                throw new Error('refresh_token é obrigatório');
            var refresh_token = obj['refresh_token'];
            obj = utils.checarToken(refresh_token, utils.obterIpCliente(req), "Refresh");
            bd = new banco();
            bd.conectar();
            bd.prepara("select id, apelido, nome, email, senha, ativo from usuario where email=?",[obj['email']]);
            bd.executar(function(resp){
                try{
                    if (bd.temErro())
                        throw new Error(bd.getErro());
                    else  if (bd.count()==0)
                        throw new Error('Email não cadastrado!');
                    else if (resp[0]['ativo']==0)
                        throw new Error('Conta inativa!\nEntre em contato com os administradores para poder reativa-la.');
                    else{
                        resp = resp[0];                            
                        access_token = utils.GerarPayloadJWT(resp,utils.obterIpCliente(req), true)
                        refresh_token = utils.GerarPayloadJWT(resp,utils.obterIpCliente(req), false)
                        jwt = { 
                            "status": 200,
                            "message": "Refresh Token realizado com sucesso",
                            "token_type": "Bearer",
                            "expires_in": access_token["duracao"],
                            "expires_on": access_token["exp"],
                            "access_token": utils.JWTEncoder(access_token),
                            "refresh_token": utils.JWTEncoder(refresh_token)
                            }
                            res.json(jwt);   
                        }
                    }catch(e){
                        res.status(401);
                        res.send('{"status":401, "message":"' + e.message +'"}');
                    }
                });
            }catch(e){
                res.status(401);
                res.send('{"status":401, "message":"' + e.message +'"}');
            }
        });
    
   
        app.get('/login/lembrarsenha', function (req, res) {
        try{
            var emailUsuario = req.query['email'];
            if (!emailUsuario)
                throw new Error('email é obrigatorio!')
            bd = new banco();
            bd.conectar();
            bd.prepara("select apelido, ativo from usuario where email=?",[emailUsuario]);
            bd.executar(function(obj){
                try{
                    if (bd.temErro())
                        throw new Error(bd.getErro());
                    else  if (bd.count()==0)
                        throw new Error('Email não encontrado!');
                    else{
                        obj=obj[0];
                        if ( obj['ativo'] == 0)
                            throw new Error('Esta conta está inativa!\nEntre em contato com os administradores para poder reativa-la!');
                        else{
                            var ctr = new  email();
                            var corpo = util.format('%s\n\n para poder trocar a senha use o link abaixo\n\n%s/login.html?op=lembrarsenha&codigo=%s\nAtt,\nSuporte Viagem',
                                obj['apelido'],
                                constantes._SERVER_HOST_,
                                utils.gerarChave(emailUsuario,'lembrarsenha'));
                            ctr.enviar(emailUsuario, 'Lembrar Senha', corpo, function(){
                                if (ctr.temErro())
                                    res.send('{"status":200, "message":"não foi possivel enviar email para '+emailUsuario+'!"}');
                                else
                                    res.send('{"status":200, "message":"Email Enviado para '+emailUsuario+' com sucesso!"}');
                            });
                        }
                    }
                }catch(e){
                    res.status(401);
                    res.send('{"status":401, "message":"' + e.message +'"}');
                }
            });
        }catch(e){
            res.status(401);
            res.send('{"status":401, "message":"' + e.message +'"}');
        }
    });

    app.get('/login/registrar', function (req, res) {
        try{
            emailusuario = req.query['email'];
            if (!emailusuario)
                throw new Error('Email é obrigatorio!');
            bd = new banco();
            bd.conectar();
            bd.prepara("select apelido, ativo from usuario where email=?",[emailusuario]);
            bd.executar(function(obj){
                try{
                    if (bd.temErro())
                        throw new Error(bd.getErro());
                    else if (bd.count()!=0)
                        throw new Error('Email já cadastrado!');
                    else{
                        var ctr = new  email();
                        var corpo = util.format('%s\n\n Para poder registrar, confirme o seu email, usando o link abaixo\n\n\n\n%s/login.html?op=registrar&codigo=%s\nAtt,\nSuporte Viagem',
                            'Caro Usuario',
                            constantes._SERVER_HOST_,
                            utils.gerarChave(emailusuario,'registrar'));
                        ctr.enviar(emailusuario, 'Confirmação de Email', corpo, function(){
                            if (ctr.temErro())
                                res.send('{"status":200, "message":"não foi possivel enviar email para '+emailusuario+'!"}');
                            else
                                res.send('{"status":200, "message":"Email Enviado para '+emailusuario+' com sucesso!"}');
                        });
                    }    
                }catch(e){
                    res.status(401);
                    res.send('{"status":401, "message":"' + e.message +'"}');
                }
            });       
        }catch(e){
            res.status(401);
            res.send('{"status":401, "message":"' + e.message +'"}');
        }
    });

    app.get('/login/reativar', function (req, res) {
        try{
            var emailusuario = req.query['email'];
            if (!emailusuario)
                throw new Error('Email é obrigatorio!');
            bd = new banco();
            bd.conectar();
            bd.prepara("select apelido, ativo from usuario where email=?",[emailusuario]);
            bd.executar(function(obj){
                try{
                    if (bd.temErro())
                        throw new Error(bd.getErro());
                    else if (bd.count()==0)
                        throw new Error('Email não cadastrado!');
                    else{
                        obj=obj[0];
                        if ( obj['ativo'] == 1)
                            throw new Error('Conta ja estava ativa!')
                        else
                        var ctr = new  email();
                        var corpo =  util.format('%s,\n\n Para poder reativar sua conta clique no link abaixo\n\n\n\n%s/login.html?op=reativar&codigo=%s\nAtt,\nSuporte Viagem',
                            obj['apelido'],
                            constantes._SERVER_HOST_,
                            utils.gerarChave(emailusuario,'reativar'));
                        ctr.enviar(emailusuario, 'Reativar Conta', corpo, function(){
                            if (ctr.temErro())
                                res.send('{"status":200, "message":"não foi possivel enviar email para '+emailusuario+'!"}');
                            else
                                res.send('{"status":200, "message":"Email Enviado para '+emailusuario+' com sucesso!"}');
                        });
                    }    
                }catch(e){
                    res.status(401);
                    res.send('{"status":401, "message":"' + e.message +'"}');
                }
            });       
        }catch(e){
            res.status(401);
            res.send('{"status":401, "message":"' + e.message +'"}');
        }
    });

    app.post('/login/lembrarsenha', function (req, res) {
        try{
            var obj = req.body;
            var codigo = utils.obterChave(obj['codigo']);
            if ( codigo['expirado'])
                throw new Error('Codigo Expirado!');
            else if ( codigo['email'] != obj['email'])
                throw new Error('O Codigo não é para este email!');
            else if ( obj['senha'] != obj['confirmasenha'])         
                throw new Error('A confirmaçao da senha não confere!');
            else if ( obj['senha'].length==0)
                throw new Error('A senha não pode estar vazia!');
            else if ( codigo['motivo']!='lembrarsenha')
                throw new Error('O Codigo não é para esta operação!');
            bd = new banco();
            bd.conectar();
            bd.prepara("select apelido, ativo from usuario where email=?",[obj['email']]);
            bd.executar(function(data){
                try{
                    if ( bd.temErro())
                        throw new Error(bd.getErro())
                    else if ( bd.count()==0)
                        throw new Error('Email não cadastrado!')
                    else{
                        var senha = crypto.createHash('md5').update(obj['senha']).digest('hex');
                        bd.prepara("UPDATE usuario set senha=? where email=?",[senha,obj['email']]);
                        bd.executar(function (data){
                            try{
                                if ( bd.temErro())
                                    throw new Error(bd.getErro());
                                else if ( bd.count()==0)
                                    throw new Error('Senha não atualizada!');
                                else
                                    res.send('{"status":200, "message":"Senha do usuario '+obj['email']+', alterada com sucesso!"}');
                            }catch(e){
                                res.status(401);
                                res.send('{"status":401, "message":"' + e.message +'"}');
                            }                    
                        });
                    }
                }catch(e){
                    res.status(401);
                    res.send('{"status":401, "message":"' + e.message +'"}');
                }        
            });
        }catch(e){
            res.status(401);
            res.send('{"status":401, "message":"' + e.message +'"}');
        }
    });

    app.post('/login/registrar', function (req, res) {
        try{
            var obj = req.body;
            codigo = utils.obterChave(obj['codigo']);
            if ( codigo['expirado'])
                throw new Error('Codigo Expirado!');
            else if ( codigo['motivo']!='registrar')
                throw new Error('O Codigo não é para esta operação!');
            else if ( codigo['email'] != obj['email'])
                throw new Error('O Codigo não é para este email!');
            else if ( obj['nome'].length==0)
                throw new Error("O nome não pode estar em branco!");
            else if ( obj['apelido'].length==0)
                throw new Error("O apelido não pode estar em branco!");
            else if ( obj['email'].length==0)
                throw new Error("O email não pode estar em branco!");
            else if ( obj['senha'].length==0)
                throw new Error('A senha não pode estar vazia!');
            else if ( obj['senha'] != obj['confirmasenha'])         
                throw new Error('A confirmaçao da senha não confere!');
            bd = new banco();
            bd.conectar();
            bd.prepara("select apelido, ativo from usuario where email=?",[obj['email']]);
            bd.executar(function(data){
                try{
                    if ( bd.temErro())
                        throw new Error(bd.getErro());
                    else if ( bd.count()!=0)
                        throw new Error('Email já cadastrado!');
                    else
                    {
                        var senha = crypto.createHash('md5').update(obj['senha']).digest('hex');
                        var dt = new Date();
                        bd.prepara("INSERT INTO usuario (apelido, nome, email, senha, data) values (?, ?, ?, ?, ?)", [obj['apelido'],obj['nome'],obj['email'],senha, utils.formataData(dt)]);
                        bd.executar(function(){
                            try{
                                if ( bd.temErro())
                                    throw new Error(bd.getErro());
                                else if ( bd.count()==0)
                                    throw new Error('Usuário não cadastrado!');
                                else
                                    res.send('{"status":200, "message":"Usuário '+obj['email']+', registrado com sucesso!"}');
                            }catch(e){
                                res.status(401);
                                res.send('{"status":401, "message":"' + e.message +'"}');
                            }
                        });
                    }
                }catch(e){
                    res.status(401);
                    res.send('{"status":401, "message":"' + e.message +'"}');
                }
            });
        }catch(e){
            res.status(401);
            res.send('{"status":401, "message":"' + e.message +'"}');
        }
    });

    app.post('/login/reativar', function (req, res) {
        try{
            var obj = req.body;
            var codigo = utils.obterChave(obj['codigo']);
            if ( codigo['expirado'])
                throw new Error('Codigo Expirado!');
            else if ( codigo['email'] != obj['email'])
                throw new Error('O Codigo não é para este email!');
            else if ( obj['senha'].length==0)
                throw new Error('A senha não pode estar vazia!');
            else if ( codigo['motivo']!='reativar')
                throw new Error('O Codigo não é para esta operação!');
            bd = new banco();
            bd.conectar();
            bd.prepara("select apelido, ativo, senha from usuario where email=?",[obj['email']]);
            var usuario = bd.executar(function(usuario){
                try{
                    if ( bd.temErro())
                        throw new Error(bd.getErro());
                    else if ( bd.count()==0)
                        throw new Error('Email não cadastrado!');
                    else{
                        usuario = usuario[0];
                        var senha = crypto.createHash('md5').update(obj['senha']).digest('hex');
                        if ( usuario['ativo']==1)
                            throw new Error('O Usuário já esta ativo!');
                        else if ( senha!=usuario['senha'])
                            throw new Error('A senha não confere!');
                        bd.prepara("UPDATE usuario set ativo=1 where email=?",[obj['email']]);
                        bd.executar(function(){
                            try{
                                if ( bd.temErro())
                                    throw new Error(bd.getErro());
                                else if ( bd.count()==0)
                                    throw new Error('usuario {} nao ativado!'.format(obj['email']));
                                else
                                    res.send('{"status":200, "message":"Usuário '+obj['email']+', ativado com sucesso!"}');
                            }catch(e){
                                res.status(401);
                                res.send('{"status":401, "message":"' + e.message +'"}');
                            }
                        });
                    }
                }catch(e){
                    res.status(401);
                    res.send('{"status":401, "message":"' + e.message +'"}');
                }
            });
        }catch(e){
            res.status(401);
            res.send('{"status":401, "message":"' + e.message +'"}');
        }
    });

    app.post('/login/hs256', function (req, res) {
        try{
            var obj = req.body;
            if ( !obj['texto'])
                throw new Erro("faltou o campo texto no corpo");
            var texto = obj['texto'];
            var msg = utils.hs256(texto);
            res.send('{"status":200, "message":'+msg+'}');
        }catch(e){
                res.status(401);
                res.send('{"status":401, "message":"' + e.message +'"}');
        }
    });

    app.post('/login/jwt', function (req, res) {
        try{
            var obj = req.body;
            var msg = utils.JWTEncoder(obj);
            res.send('{"status":200, "token":'+msg+'}');
        }catch(e){
            res.status(401);
            res.send('{"status":401, "message":"' + e.message +'"}');
        }
    });

    app.post('/login/jwt/valida', function (req, res) {
        var obj = null;
        var jwt = null;
        try{
            var r = req.body;
            var vet = r['token'].split('.');
            jwt = {'header': JSON.parse(utils.fromBase64(vet[0])), 
                'payload': JSON.parse(utils.fromBase64(vet[1]))
            };
            obj = utils.checarToken(r['token'],utils.obterIpCliente(req), r['tipo']);       
            res.send('{"status":200,"jwt":'+JSON.stringify(jwt)+' "obj":'+JSON.stringify(obj)+'}');
        }catch(e){
            res.status(401);
            res.send('{"status":401, "message":"' + e.message +'"}');
        }
    });
}