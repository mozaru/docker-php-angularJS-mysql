import psycopg2
import MySQLdb 
import json
import os
import collections
import datetime
from bottle import Bottle, request

class Sender(Bottle):
    def __init__(self):
        super().__init__()
        self.route('/hello', method='get', callback=self.oi)
        self.route('/usuarios', method='get', callback=self.usuarios)
                

    def oi(self):
        return 'oi python'

    def usuarios(self):
        connection = MySQLdb.connect(host="bd", user="operador", passwd="123456", db="viagem")
        cursor = connection.cursor()
        cursor.execute("select id, apelido, nome, email, ativo, data from usuario order by nome")
        data = cursor.fetchall()
        lista = []
        for row in data :
            d = collections.OrderedDict()
            d['id']         = row[0]
            d['apelido']    = row[1]
            d['nome']       = row[2]
            d['email']      = row[3]
            d['ativo']      = row[4]
            d['data']       = row[5].strftime("%Y-%m-%d %H:%M:%S")
            lista.append(d)
        cursor.close()
        connection.close()
        return json.dumps(lista)


if __name__ == '__main__':
    sender = Sender()
    sender.run(host='0.0.0.0', port=8000, debug=True)
