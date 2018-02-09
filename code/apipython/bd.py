import MySQLdb
import MySQLdb.cursors
import json
import os
import collections
import datetime
import constantes

class banco(object):
    __host     = constantes._BD_HOST_
    __db_name  = constantes._BD_DATABASE_
    __username = constantes._BD_LOGIN_
    __password = constantes._BD_PASSWORD_
    __conn     = None
    __stmt     = None
    __tipo     = "QUERY"
    __erro     = ""
    __rowcount = 0
    __lastid   = 0
    def __init__(self):
        self.__tipo = "QUERY"

    def conectar(self):
        self.__erro=""
        try:
            self.__conn = MySQLdb.connect(host=self.__host, user=self.__username, passwd=self.__password, db=self.__db_name)
        except Exception as exception:
            self.__erro = 'Não foi possivel conectar com o Banco de Dados'#$exception->getMessage();

    def desconectar(self):
        if self.__stmt!=None:
            self.__stmt.close()
        self.__stmt = None
        if self.__conn!=None:
            self.__conn.close()   
        self.__conn = None

    def temErro(self):
        return self.__erro!=""

    def getErro(self):
        return self.__erro

    def prepara(self, query, valores=None):
        try:
            self.__rowcount = -1
            self.__lastid   = -1
            self.conectar()
            if (self.temErro()):
                return
            self.__stmt = self.__conn.cursor(cursorclass=MySQLdb.cursors.DictCursor)
            self.__stmt.execute(query, valores)
            query = query.lower()
            
            if query.find("insert")     != -1:
                self.__tipo = 'INSERT'
            elif query.find("update")   != -1:
                self.__tipo = 'UPDATE'
            elif query.find("delete")   != -1:
                self.__tipo = 'DELETE' 
            else:
                self.__tipo = 'QUERY'
        except Exception as exception:
            self.__erro = 'Erro na preparação da consulta ao banco de dados'+str(exception)#$exception->getMessage();
 
    def count(self):
        try:
            if self.temErro():
                return -1
            else:
                return self.__rowcount
        except Exception as exception:
            self.__erro = 'Erro ao tentar verificar a quantidade de elementos da consulta ao banco de dados'+str(exception)
    
    def lastid(self):
        try:
            if self.temErro():
                return -1
            else:
                return self.__lastid
        except Exception as exception:
            self.__erro = 'Erro ao tentar verificar o ultimo id inserido no banco de dados'+str(exception)
                    
    def executar(self):
        try:
            if self.temErro():
                return
            if self.__tipo=="QUERY":
                data = self.__stmt.fetchall() 
                self.__rowcount = self.__stmt.rowcount
            elif self.__tipo=="INSERT":
                self.__rowcount = self.__stmt.rowcount
                data = self.__lastid = self.__stmt.lastrowid
                self.__conn.commit()
            elif self.__tipo=="UPDATE":
                data = self.__rowcount = self.__stmt.rowcount
                self.__conn.commit()
            elif self.__tipo=="DELETE":
                data = self.__rowcount = self.__stmt.rowcount
                self.__conn.commit()
            else:
                raise Exception("deu problema")
            self.__stmt.close()
            self.__stmt = None    
            return data
        except Exception as exception:
            self.__conn.rollback()
            desconectar()
            self.__erro = 'Erro na execução da consulta ao banco de dados'+str(exception)
            
    def listar(self, query):
        try:
            self.prepara(query)
            return self.executar()
        except Exception as exception:
            self.__erro = 'Erro ao tentar listar os elementos do banco de dados'

    def obterPeloId(self, query, id):
        try:
            self.prepara(query, (id))
            vet = self.executar()
            if self.temErro():
                return -1
            return vet[0]
        except Exception as exception:
            self.__erro = 'Erro ao obter um elemento do banco de dados'