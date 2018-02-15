
const mysql = require('mysql');
const constantes = require('./constantes');


class banco {
    /*var host     = constantes._BD_HOST_;
    var db_name  = constantes._BD_DATABASE_;
    var username = constantes._BD_LOGIN_;
    var password = constantes._BD_PASSWORD_;
    var conn     = null;
    var stmt     = null;
    var tipo     = "QUERY";
    var erro     = "";
    var rowcount = 0;
    var lastid   = 0;
    var sql      = "";
    var params   = null;*/

    constructor()
    {
        this.host     = constantes._BD_HOST_;
        this.db_name  = constantes._BD_DATABASE_;
        this.username = constantes._BD_LOGIN_;
        this.password = constantes._BD_PASSWORD_;
        this.conn     = null;
        this.stmt     = null;
        this.tipo     = "QUERY";
        this.erro     = "";
        this.rowcount = 0;
        this.lastid   = 0;
        this.sql      = "";
        this.params   = null;
    }

    conectar(){
        try{
            this.conn = mysql.createConnection({ host:this.host, user:this.username, password:this.password, database:this.db_name});
        }
        catch (exception){
            this.erro = 'Não foi possivel conectar com o Banco de Dados' + exception;
        }
    }

    desconectar(){
        try{
            if (this.stmt!=null)
                this.stmt.close();
            this.stmt = null;
            if (this.conn!=null)
                this.conn.end();   
            this.conn = null;
        }
        catch (exception){
            this.erro = 'Não foi possivel conectar com o Banco de Dados' + exception;
        }
    }

    temErro(){
        return this.erro!="";
    }

    getErro(){
        return this.erro;
    }

    prepara(query, values=null){
        try{
            this.rowcount = -1;
            this.lastid   = -1;
            //this.conectar();
            this.sql = query;
            this.params = values;
            if (this.temErro())
                return;
            query = query.toLowerCase();            
            if (query.indexOf("insert")!= -1)
                this.tipo = 'INSERT';
            else if (query.indexOf("update")!= -1)
                this.tipo = 'UPDATE';
            else if (query.indexOf("delete")!= -1)
                this.tipo = 'DELETE'; 
            else
                this.tipo = 'QUERY';
        }catch(exception){
            this.erro = 'Erro na preparação da consulta ao banco de dados'+exception;
        }
    }

    count(){
        try{
            if (this.temErro())
                return -1;
            else
                return this.rowcount;
        }catch(exception){
            this.erro = 'Erro ao tentar verificar a quantidade de elementos da consulta ao banco de dados'+exception;
        }
    }

    lastid(){
        try{
            if (this.temErro())
                return -1;
            else
                return this.lastid;
        }catch(exception){
            this.erro = 'Erro ao tentar verificar o ultimo id inserido no banco de dados'+exception;
        }
    }
                    
    executar(async){
        try{
            var self = this;
            if (this.temErro())
                async(null);
            this.conn.query(this.sql, this.params, function(err, result){
                self.rowcount = -1;
                self.lastid = -1;
                if (err) 
                    self.erro = err;
                else if (self.tipo=="QUERY")
                    self.rowcount = result.length;
                else if (self.tipo=="INSERT"){
                    self.rowcount = result.affectedRows;
                    self.lastid = result.insertId;
                }else if (self.tipo=="UPDATE")
                    self.rowcount = result.affectedRows;
                else if (self.tipo=="DELETE")
                    self.rowcount = result.affectedRows;
                else
                    self.erro = "deu problema";
                async(result);
            });
        }catch(exception){
            this.desconectar();
            this.erro = 'Erro na execução da consulta ao banco de dados'+exception;
            async(null);
        }
    }
            
    listar(query, async){
        try{
            this.prepara(query);
            this.executar(async);
        }catch(exception){
            this.erro = 'Erro ao tentar listar os elementos do banco de dados';
            async(null);
        }
    }

    obterPeloId(query, id, async){
        try{
            this.prepara(query, [id]);
            this.executar(async);
        }catch(exception){
            this.erro = 'Erro ao obter um elemento do banco de dados';
            async(null);
        }
    }
}

module.exports = banco;