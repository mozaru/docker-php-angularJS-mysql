const express = require('express');         
const bodyParser = require('body-parser');
const port = process.env.PORT || 3000; //porta padrão


var app = express();

//configurando o body parser para pegar POSTS mais tarde
app.use(bodyParser.urlencoded({ extended: true }));
app.use(bodyParser.json());

Date.prototype.toJSON = function(){return this.toISOString().replace(/T/, ' ').replace(/\..+/, '')};


require('./usuarios.js')(app);
require('./login.js')(app);

app.get('/hello', function (req, res) {
    res.send('Hello World!');
});

app.post('/eco', function (req, res) {
   console.log(req.body);      // your JSON
   res.send(req.body); 
});

app.get('/apinode/hello', function (req, res) {
    res.send('Hello World! apinode');
});


//inicia o servidor
app.listen(port);
console.log('API funcionando!');