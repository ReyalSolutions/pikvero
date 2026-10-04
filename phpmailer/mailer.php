<?php
// Import PHPMailer classes into the global namespace
// These must be at the top of your script, not inside a function
require_once("../includes/connection.php"); 
session_start();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

//Load composer's autoloader
require 'vendor/autoload.php';

$mail = new PHPMailer(true);   


if(isset($_POST['submitID'])){
// Passing `true` enables exceptions
$result = mysql_query("SELECT * FROM instructor WHERE email_add = '".$_POST['email']."'") or die(mysql_error()); 
    if(mysql_num_rows($result)>0){
    $rowfetch = mysql_fetch_array($result);
try {
    //Server settings
    $mail->SMTPDebug = 0;                                 // Enable verbose debug output
    $mail->isSMTP();                                      // Set mailer to use SMTP
    $mail->Host = 'smtp.gmail.com';  // Specify main and backup SMTP servers
    $mail->SMTPAuth = true; 
    $mail->Username = 'info@bnsc.edu.ph';                 // SMTP username
    $mail->Password = 'hnawclobqhknffhb';                                // Enable SMTP authentication
    // $mail->Username = 'jeeptayco@gmail.com';                 // SMTP username
    // $mail->Password = 'urohujzxgyovmicr';                           // SMTP password
    $mail->SMTPSecure = 'StartTLS';    
    // $mail->SMTPAutoTLS = false;
    // $mail->SMTPSecure = 'none';                        // Enable TLS encryption, `ssl` also accepted
    $mail->Port = 587;                             // TCP port to connect to
    $mail->SMTPOptions = array(
    'ssl' => array(
        'verify_peer' => false,
        'verify_peer_name' => false,
        'allow_self_signed' => true
    )
);

// For most clients expecting the Priority header:
    // 1 = High, 2 = Medium, 3 = Low
    $mail->Priority = 1;
    // MS Outlook custom header
    // May set to "Urgent" or "Highest" rather than "High"
    $mail->AddCustomHeader("X-MSMail-Priority: Urgent");
    // Not sure if Priority will also set the Importance header:
    $mail->AddCustomHeader("Importance: Highest");
    
    //Recipients
    $mail->setFrom('info@bnsc.edu.ph','BNSC Contact Mailer');
    //
    //$mail->addAddress(trim($_POST['email']), 'Domz Recipients');     // Add a recipient
    $mail->addAddress($_POST['email']);               // Name is optional - This the BNSC respective email receiver
    // $mail->addReplyTo(trim('jeeptayco@gmail.com'),'Jeff Lawrence Tayco');
    //$mail->addCC('cc@example.com');
    //$mail->addBCC('bcc@example.com');

    //Attachments
    //$mail->addAttachment('/var/tmp/file.tar.gz');         // Add attachments
    //$mail->addAttachment('/tmp/image.jpg', 'new.jpg');    // Optional name
    //Content
    $mail->isHTML(true);                                  // Set email format to HTML
    $mail->Subject = 'Password Recovery';
    $mail->Body    = '
    <style>a:hover{color:blue;}</style>
    <div style="width:100%;overflow:hidden;border-bottom:1px solid #E2E2E2;">
    <center>
        <img src="https://www.bnsc.edu.ph/src/img/mlogo.png" width="200" height="200" style="position:absolute;top:50px;">
        <br>
        <a href="https://www.bnsc.edu.ph" style="text-decoration:none;" target="_blank"><h1>BOHOL NORTHERN STAR COLLEGE</h1></a>
    </center>
    </div>
    <br>
    <h3 style="font-size:25px;">Hi, '.$rowfetch['fullname'].'!</h3>
    <p style="font-size:22px;">This is your password, you can check remember me in login page so that you can login without typing your password! <a href="http://localhost/htdocs/teachers/index.php">go to teacher login</a><p/>
    <div style="padding:10px;font-size:25px;background:#0275d8;text-align:center;border-radius:30px;color:#fff;">'.$rowfetch['p_word'].'</div>

    <br><br>
    <center>
    <a href="https://www.bnsc.edu.ph" style="text-decoration:none;color:gray;" target="_blank"><p>Bohol Northern Star College, inc.</p></a>
    </center>
     <br><br<br><br><br>
    ';
    //<img src="https://www.bnsc.edu.ph/src/img/bnsc_front01.jpg" style="position:absolute;width:100%;">
    //$mail->AltBody = 'Test body This is the body in plain text for non-HTML mail clients';//No tags read/tags excludeded strip_tags($body)
    if($mail->send()){
         $_SESSION["msg_alert"] = '<p class="alert-msg" style="background:green;"id="alert-msg">Email successfuly sent</p>';
    }else{
        $_SESSION["msg_alert"] = '<p class="alert-msg" id="alert-msg">Something went wrong! :(</p>';
    }
   
} catch (Exception $e) {
    //echo '<br/><br/>Message could not be sent.';
    //echo '<br/><br/>Mailer Error: ' . $mail->ErrorInfo;
    $_SESSION["msg_alert"] = '<p class="alert-msg" id="alert-msg">Something went wrong! :(</p>';
     // echo $e;
}
 
}else{
    $_SESSION["msg_alert"] = '<p class="alert-msg" id="alert-msg">Your email address did not exist in the database!</p>';
} 
header("location: ../forgot_pass.php");
}
?>