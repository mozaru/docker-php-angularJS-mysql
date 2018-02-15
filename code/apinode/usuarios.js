const express = require('express');
const app = express();         
const bodyParser = require('body-parser');
const port = process.env.PORT || 3000; //porta padrão
const banco = require('./bd.js');
const email = require('./email.js');
const crypto = require('crypto');
const util = require('util');

require('./constantes.js');

const utils = require('./utils');

module.exports = function(app){
    app.get('/usuarios', function (req, res) {
        try{
            utils.controlaAcesso(req);
            bd = new banco();
            bd.conectar();
            bd.prepara('select id, apelido, nome, email, ativo, data from usuario order by nome');
            bd.executar(function(obj){
                try{
                    if(bd.temErro())
                        throw new Error(bd.getErro());
                    res.json(obj);
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

    app.get('/usuarios/:id', function( req, res){
        try{
            utils.controlaAcesso(req);
            bd = new banco();
            bd.conectar();
            bd.prepara("select id, apelido, nome, email, ativo, senha from usuario where id=?",[req.params.id]);
            bd.executar(function(obj){
                try{
                    if (bd.temErro())
                        throw new Error(bd.getErro()); 
                    else if (bd.count()==0)
                        throw new Error("Usuário não encontrado!");  
                    else
                        res.json(obj);
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

    app.post('/usuarios/inserir', function( req, res){
        try{
            utils.controlaAcesso(req);
            obj = req.body; 
            bd = new banco();
            bd.conectar();
            bd.prepara('select apelido from usuario where email = ? ',[obj['email']]);
            bd.executar(function(data){
                try{
                    if (bd.count()!=0)
                        throw new Error("Email já cadastrado!");
                    else if (obj['nome'].length==0)
                        throw new Error("O nome não pode estar em branco!");
                    else if (obj['apelido'].length==0)
                        throw new Error("O apelido não pode estar em branco!");
                    else if (obj['email'].length==0)
                        throw new Error("O email não pode estar em branco!");
                    else{
                        var dt = new Date();
                        var senha = utils.gerarSenha();
                        var hash = crypto.createHash('md5').update(senha).digest('hex');
                        bd.prepara("INSERT INTO usuario (apelido, nome, email, senha, data) values (?, ?, ?, ?, ?)", [obj['apelido'],obj['nome'],obj['email'],hash, utils.formataData(dt)]);
                        bd.executar(function(data2){
                            try{
                                if (bd.temErro())
                                    throw new Error(bd.getErro());
                                else if (bd.count()==0)
                                    throw new Error("Nao foi possivel inserir os dados do usuario "+obj['email']+"!");
                                else{
                                    var ctr = new  email();
                                    var corpo = util.format("%s,\n\n sua conta foi criada pelo administrador\n%s\nlogin:%s\nsenha:%s\n\nAtt,\nSuporte Viagem",
                                        obj['apelido'],
                                        "/",
                                        obj['email'],
                                        senha);
                                    ctr.enviar(obj['email'],"Usuário Registrado", corpo, function(){
                                            if (ctr.temErro())
                                                res.send('{"status":200, "message":"Usuario registrado com sucesso, mas email não foi enviado!"}');
                                            else
                                                res.send('{"status":200, "message":"Usuario registrado com sucesso!"}');
                                    });
                                }
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
    
    app.delete('/usuarios/:id', function( req, res){
        try{
            utils.controlaAcesso(req);
            bd = new banco();
            bd.conectar();
            bd.prepara("select email from usuario where id=?",[req.params.id]);
            bd.executar(function(obj){
                try{
                    if (bd.temErro())
                        throw new Error(bd.getErro());
                    else if (bd.count() == 0)
                        throw new Error('Usuario nao encontrado!');
                    else{
                        var email = obj[0]['email'];     
                        bd.prepara("DELETE from usuario where id=?",[req.params.id]);
                        bd.executar(function(obj){
                            try{
                                if (bd.temErro())
                                    throw new Error(bd.getErro());
                                else if (bd.count() == 0)
                                    throw new Error('Nao foi possivel remover o usuario '+email);
                                else
                                    res.send('{"status":200, "message":"Usuario '+email+' removido com sucesso!"}');
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
    
    //@post('/usuarios/<id>')
    app.put('/usuarios/:id', alterar = function( req, res){
        try{
            utils.controlaAcesso(req);
            bd = new banco();
            bd.conectar();
            bd.prepara("select email from usuario where id=?",[req.params.id]);
            bd.executar(function(data){
                try{
                    if (bd.temErro())
                        throw new Error(bd.getErro());
                    else if (bd.count() == 0)
                        throw new Error('Usuario nao encontrado!');
                    else{
                        var email = data[0]['email'];
                        var obj = req.body; //request.json //
                        bd.prepara('select apelido from usuario where email = ? and id <> ?',[email,req.params.id]);
                        bd.executar(function(data){
                            try{
                                if (bd.count()!=0)
                                    throw new Error("Email já cadastrado!");
                                else if (obj['nome'].length==0)
                                    throw new Error("O nome não pode estar em branco!")
                                else if  (obj['apelido'].length==0)
                                    throw new Error("O apelido não pode estar em branco!");
                                else if (obj['email'].length==0)
                                    throw new Error("O email não pode estar em branco!");
                                else{
                                    bd.prepara("UPDATE usuario set apelido=?, nome=?, email=? where id=?", [obj['apelido'],obj['nome'],obj['email'], req.params.id]);
                                    bd.executar(function(data){
                                        try{
                                            if (bd.temErro())
                                                throw new Error(bd.getErro());
                                            else if (bd.count()==0)
                                                throw new Error("Nao foi possivel alterar os dados do usuario "+email+"!");
                                            else
                                                res.send('{"status":200, "message":"Usuario '+obj['email']+' alterado com sucesso!"}');
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
    app.post('/usuarios/:id', alterar);

    app.post('/usuarios/ativar/:id', function( req, res){
        try{
            utils.controlaAcesso(req);
            bd = new banco();
            bd.conectar();
            bd.prepara("select email,ativo from usuario where id=?",[req.params.id]);
            bd.executar(function(obj){
                try{
                    if (bd.temErro())
                        throw new Error(bd.getErro());
                    else if (bd.count() == 0)
                        throw new Error('Usuario nao encontrado!');
                    else if (obj[0]['ativo']==1)
                        throw new Error('Usuario '+obj[0]['email']+' já estava ativo!');                    
                    else{
                        var email = obj[0]['email'];     
                        bd.prepara("UPDATE usuario set ativo=1 where id=?", [req.params.id]);
                        bd.executar(function(obj){
                            try{
                                if (bd.temErro())
                                    throw new Error(bd.getErro());
                                else if (bd.count()==0)
                                    throw new Error("Nao foi possivel ativar o usuario "+email+"!");
                                else
                                    res.send('{"status":200, "message":"Usuario '+email+' ativado com sucesso!"}');
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
    
    app.post('/usuarios/desativar/:id', function( req, res){
        try{
            utils.controlaAcesso(req);
            bd = new banco();
            bd.conectar();
            bd.prepara("select email,ativo from usuario where id=?",[req.params.id]);
            bd.executar(function(obj){
                try{
                    if (bd.temErro())
                        throw new Error(bd.getErro());
                    else if (bd.count() == 0)
                        throw new Error('Usuario nao encontrado!');
                    else if (obj[0]['ativo']==0)
                        throw new Error('Usuario '+obj[0]['email']+' já estava desativado!');                    
                    else{
                        var email = obj[0]['email'];     
                        bd.prepara("UPDATE usuario set ativo=0 where id=?", [req.params.id]);
                        bd.executar(function(obj){
                            try{
                                if (bd.temErro())
                                    throw new Error(bd.getErro());
                                else if (bd.count()==0)
                                    throw new Error("Nao foi possivel desativar o usuario "+email+"!");
                                else
                                    res.send('{"status":200, "message":"Usuario '+email+' desativado com sucesso!"}');
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
      
    app.post('/usuarios/resetar/:id', function( req, res){
        try{
            utils.controlaAcesso(req);
            bd = new banco();
            bd.conectar();
            bd.prepara("select email from usuario where id=?",[req.params.id]);
            bd.executar(function(obj){
                try{
                    if (bd.temErro())
                        throw new Error(bd.getErro());
                    else if (bd.count() == 0)
                        throw new Error('Usuario nao encontrado!')
                    else{
                        var emailUsuario = obj[0]['email'];
                        var hash = crypto.createHash('md5').update(utils.gerarSenha()).digest('hex');
                        bd.prepara("UPDATE usuario set senha=? where id=?", [hash, req.params.id]);
                        bd.executar(function(data){
                            if (bd.temErro())
                                throw new Error(bd.getErro());
                            else if (bd.count()==0)
                                throw new Error("Nao foi possivel resetar a senha do usuario "+emailUsuario+"!");
                            else{
                                var ctr = new  email();
                                var corpo = util.format("%s,\n\n sua conta foi criada pelo administrador\n%s\nlogin:%s\nsenha:%s\n\nAtt,\nSuporte Viagem",
                                    obj['apelido'],
                                    "/",
                                    obj['email'],
                                    senha);
                                ctr.enviar(obj['email'],"Usuário Registrado", corpo, function(){
                                    if (ctr.temErro())
                                        res.send('{"status":200, "message":"Senha do usuario '+emailUsuario+' resetada com sucesso!, mas email não foi enviado!"}');
                                    else
                                        res.send('{"status":200, "message":"Senha do usuario '+emailUsuario+' resetada com sucesso!"}');
                                });
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
}
