const express = require('express');
const app = express();         
const bodyParser = require('body-parser');
const port = process.env.PORT || 3000; //porta padrão
const mysql = require('mysql');


//configurando o body parser para pegar POSTS mais tarde
app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());

function execSQLQuery(sqlQry, res){
    try{
        const connection = mysql.createConnection({
        host     : 'bd',
        //port     : XXX,
        user     : 'operador',
        password : '123456',
        database : 'viagem'
        });
    
        connection.query(sqlQry, function(error, results, fields){
            if(error) 
            {
            res.status(401);
            res.send('{"status":401, "message":"' + error.message +'"}');
            }
            else
            res.json(results);
            connection.end();
        });
    }catch(err)
    {
        res.status(401);
        res.send('{"status":401, "message":"' + err +'"}');
    }
}

app.get('/hello', function (req, res) {
    res.send('Hello World!');
});

app.get('/apinode/hello', function (req, res) {
    res.send('Hello World! apinode');
});

app.get('/usuarios', function (req, res) {
    execSQLQuery('select id, apelido, nome, email, ativo, data from usuario order by nome', res)
});

//inicia o servidor
app.listen(port);
console.log('API funcionando!');