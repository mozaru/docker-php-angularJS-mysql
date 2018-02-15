const constantes = require('./constantes');
var nodemailer = require('nodemailer');

class email {
    constructor()
    {
        this.usuario       = constantes._EMAIL_USER_;
        this.senha         = constantes._EMAIL_PASSWORD_;
        this.host          = constantes._EMAIL_HOST_;
        this.porta         = constantes._EMAIL_PORT_;
        this.protocolo     = constantes._EMAIL_PROTOCOL_;
        this.erro          = '';
    }

    
    temErro(){
        return this.erro != '' && this.erro!=null;
    }

    getErro(){
        return this.erro;
    }

    enviar(para, assunto, texto, async){
        try{
            var self = this;
            this.erro = ''  
            var email = {
                from: this.usuario, // Quem enviou este e-mail
                to: para, // Quem receberá
                subject: assunto,  // Um assunto bacana :-) 
                html: texto // O conteúdo do e-mail
            };
            var transporte = nodemailer.createTransport({
                host: this.host,
                port: this.porta,
//                ignoreTLS: false,
//                requireTLS: true,
//                secure: true, 
                service: 'Gmail',
                auth: {
                  user: this.usuario, // Basta dizer qual o nosso usuário
                  pass: this.senha    // e a senha da nossa conta
                } 
            });            
            transporte.sendMail(email, function(err){
                if(err)
                  self.erro=err; 
                async();
            });
        }catch(exception){
            this.erro = exception;
            async();
        }
    }
}

module.exports = email;
