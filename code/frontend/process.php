<?php
 
 require_once("libs/class.phpmailer.php");

 define('GUSER', 'mozar.silva@gmail.com');	// <-- Insira aqui o seu GMail
 define('GPWD', 'zrazom90*');		// <-- Insira aqui a senha do seu GMail

    $de = GUSER; 
    $de_nome = "Mozar Silva";
    $para = $_REQUEST['email']; 
    $nome = $_REQUEST['name']; 
    $assunto = "Message de teste"; 
 
    $fields = array(); 
    $fields{"name"} = "name"; 
    $fields{"email"} = "email"; 
    $fields{"phone"} = "phone"; 
    $fields{"message"} = "message";
 
    $corpo = "Here is what was sent:\n\n"; foreach($fields as $a => $b){   $corpo .= sprintf("%20s: %s\n",$b,$_REQUEST[$a]); }
 
    $mail = new PHPMailer();
    $mail->SMTPOptions = array(
        'ssl' => array(
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        )
    );
	$mail->IsSMTP();		// Ativar SMTP
	$mail->SMTPDebug = 2;		// Debugar: 1 = erros e mensagens, 2 = mensagens apenas
	$mail->SMTPAuth = true;		// Autenticação ativada
    $mail->SMTPSecure = 'tls';	// SSL REQUERIDO pelo GMail ssl ou tls
    //172.17.10.5 ou mailpt.allen.com.br portas 25  ou 587 
	$mail->Host = 'smtp.gmail.com';	// SMTP utilizado
	$mail->Port = 587;  		// A porta 587 deverá estar aberta em seu servidor
	$mail->Username = GUSER;
	$mail->Password = GPWD;
	$mail->SetFrom($de, $de_nome);
	$mail->Subject = $assunto;
	$mail->Body = $corpo;
	$mail->AddAddress($para);
	if(!$mail->Send()) {
        $error = 'Mail error: '.$mail->ErrorInfo; 
        echo $error;
		return false;
	} else {
		$error = 'Mensagem enviada!';
		return true;
	}
 
    echo "ok";
?>