<?php

 require_once("libs/class.phpmailer.php");
 require_once('constantes.php');

 class email{
    private $usuario = _EMAIL_USER_;
    private $senha   = _EMAIL_PASSWORD_;
    private $host    = _EMAIL_HOST_;
    private $porta   = _EMAIL_PORT_;
    private $protocolo = _EMAIL_PROTOCOL_;
    private $erro     = '';
    
    public function enviar($para, $assunto, $texto)
    {
        $mail = new PHPMailer();
        /*$mail->SMTPOptions = array(
            'ssl' => array(
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true
            )
        );*/
        $mail->IsSMTP();		// Ativar SMTP
        $mail->SMTPDebug = 0;		// Debugar: 1 = erros e mensagens, 2 = mensagens apenas
        $mail->SMTPAuth = true;		// Autenticação ativada
        $mail->SMTPSecure = $this->protocolo;	// SSL REQUERIDO pelo GMail ssl ou tls
        //172.17.10.5 ou mailpt.allen.com.br portas 25  ou 587 
        $mail->Host = $this->host;	// SMTP utilizado
        $mail->Port = $this->porta;  		// A porta 587 deverá estar aberta em seu servidor
        $mail->Username = $this->usuario;
        $mail->Password = $this->senha;
        $mail->SetFrom($para, $para);
        $mail->Subject = $assunto;
        $mail->Body = $texto;
        $mail->AddAddress($para);

        try{
            ob_start();
             $resp = $mail->Send();
            ob_end_clean();
            if(!$resp) {
                $this->erro = 'Mail error: '.$mail->ErrorInfo; 
                return false;
            } else {
                $this->erro = '';
                return true;
            }     
        }catch (Exception $e) {
            $this->erro = $e->getMessage(); 
            return false;
        }
    } 
 }

?>