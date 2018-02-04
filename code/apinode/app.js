const express = require('express');
const app = express();         
const bodyParser = require('body-parser');
const port = process.env.PORT || 3000; //porta padrão
const mysql = require('mysql');


//configurando o body parser para pegar POSTS mais tarde
app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());

function execSQLQuery(sqlQry, res){
    const connection = mysql.createConnection({
      host     : 'bd',
      //port     : XXX,
      user     : 'operador',
      password : '123456',
      database : 'viagem'
    });
   
    connection.query(sqlQry, function(error, results, fields){
        if(error) 
          res.json(error);
        else
          res.json(results);
        connection.end();
    });
}

app.get('/hello', function (req, res) {
    res.send('Hello World!');
});

app.get('/apinode/hello', function (req, res) {
    res.send('Hello World! apinode');
});

app.get('/usuarios', function (req, res) {
    execSQLQuery('SELECT * FROM usuario', res)
});

//inicia o servidor
app.listen(port);
console.log('API funcionando!');