import smtplib
import constantes
# Import the email modules we'll need
#from email.MIMEMultipart import MIMEMultipart
#from email.MIMEText import MIMEText

class email(object):
    __usuario       = constantes._EMAIL_USER_
    __senha         = constantes._EMAIL_PASSWORD_
    __host          = constantes._EMAIL_HOST_
    __porta         = constantes._EMAIL_PORT_
    __protocolo     = constantes._EMAIL_PROTOCOL_
    __erro          = ''
    
    def temErro():
        return self.__erro != '' and self.__erro!= None

    def getErro():
        return self.__erro

    def enviar(para, assunto, texto):
        try:
            self.__erro = ''
            #msg = MIMEMultipart()
            #msg['From'] = self.__usuario
            #msg['To'] = para
            #msg['Subject'] = assunto
            
            #msg.attach(MIMEText(texto, 'plain'))
            
            server = smtplib.SMTP(__host, self.__porta)
            server.starttls()
            server.login(self.__usuario, self.__senha)
            server.sendmail(self.__usuario, para, texto)
            server.quit()
            return True
        except Exception as e:
            self.__erro = str(e)
            return False
        except :
            self.__erro = 'Erro Inexperado ao enviar email'
            return False
        
