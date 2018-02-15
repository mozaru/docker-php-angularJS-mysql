import smtplib
import constantes
from email.mime.text import MIMEText

class email(object):
    __usuario       = constantes._EMAIL_USER_
    __senha         = constantes._EMAIL_PASSWORD_
    __host          = constantes._EMAIL_HOST_
    __porta         = constantes._EMAIL_PORT_
    __protocolo     = constantes._EMAIL_PROTOCOL_
    __erro          = ''
    
    def temErro(self):
        return self.__erro != '' and self.__erro!= None

    def getErro(self):
        return self.__erro

    def enviar(self, para, assunto, texto):
        try:
            self.__erro = ''  
            server = smtplib.SMTP(self.__host, self.__porta)
            server.starttls()
            server.login(self.__usuario, self.__senha)
            msg = MIMEText(texto)
            msg['From'] = self.__usuario
            msg['To'] = para
            msg['Subject'] = assunto          
            server.sendmail(self.__usuario, para, msg.as_string())
            server.quit()
            return True
        except Exception as e:
            self.__erro = str(e)
            return False
        except :
            self.__erro = 'Erro Inexperado ao enviar email'
            return False
        
