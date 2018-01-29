<?php
 
 require_once("libs/class.phpmailer.php");

 class email{
    private $usuario = 'emaillixo21@gmail.com';
    private $senha   = 'vinteeum';
    private $host    = 'smtp.gmail.com';
    private $porta   = 587;
    private $protocolo = 'tls';
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