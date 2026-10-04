<?php
	session_start();
	require_once '../inc/conn.php';
	use PHPMailer\PHPMailer\PHPMailer;
	use PHPMailer\PHPMailer\Exception;
	require 'vendor/autoload.php';
	$mail = new PHPMailer(true);
	//	
	if(isset($_POST['oppform'])){
		//Check if already applied
		$email = trim($_POST['email']);		
		$stmt = $DBconn->query("SELECT job_applicants.job_id, job_vacancy.job_status AS jstat, job_vacancy.job_title AS jtitle FROM job_applicants LEFT JOIN job_vacancy ON job_applicants.job_id = job_vacancy.job_id WHERE email = '".$email."'");
		$result = $stmt->fetch();
		if($stmt->rowCount() != 0){
			if($result['jstat'] == 'OPEN'){
				echo 'Applicant have recently applied a job vacancy as: '. $result['jtitle'];
				return;
			}
		}
	  //==== Start File Upload ===//
	  if(isset($_FILES['ufile'])){		  
		  $errors = '';
		  $file_name = $_FILES['ufile']['name'];
		  $file_size = $_FILES['ufile']['size'];
		  $file_tmp = $_FILES['ufile']['tmp_name'];
		  $file_type = $_FILES['ufile']['type'];
		  $file_ext=strtolower(end(explode('.',$_FILES['ufile']['name'])));
		  //Random File Name
		  $cdate = date('Gismdn');
		  $remspc = str_replace(' ', '', str_replace('.', '', $file_name));
		  $limitstr = substr($remspc, 0, 15);
		  $nfilename = strtolower(str_shuffle($limitstr.$cdate)).'.'.$file_ext;
		  //
		  $extensions= array("docx","doc","pdf");

		  if(in_array($file_ext,$extensions)=== false){
			 $errors .= "*File upload format cannot be accepted. (e.g.) *.docx, *.doc, *.pdf<br/>";
		  }

		  if($file_size > 2097152) {
			 $errors .= '*File size limit is 2MB.<br/>';
		  }
		  
		  if(strlen($errors) == 0) {
					//Send Notification to Sender					
				try {
					//Server settings
					$mail->SMTPDebug = 0;                                 
					$mail->isSMTP();                                      
					$mail->Host = 'smtp.gmail.com';  
					$mail->SMTPAuth = true;                               
					$mail->Username = 'bnsc.bnef@gmail.com';                
					$mail->Password = 'bnscbnef2021';                           
					$mail->SMTPSecure = 'StartTLS';                        
					$mail->Port = 587;
					$mail->SMTPOptions = array(
					'ssl' => array(
					'verify_peer' => false,
					'verify_peer_name' => false,
					'allow_self_signed' => true
					)
					);
					$mail->Priority = 1;
					$mail->AddCustomHeader("X-MSMail-Priority: Urgent");    
					$mail->AddCustomHeader("Importance: Highest");

					//Sent From
					$mail->setFrom('info@boholnorthernstarcollege.edu.ph', 'BNSC - HRDMO');
					//Receiver
					$mail->addAddress($email);
					//Reply To
					$mail->addReplyTo('bnsc.bnef@gmail.com', 'BNSC - HRDMO');
					//HTML Body
					$mail->isHTML(true);
					$mail->Subject = 'BNSC - HRDMO - Job Application';
					$mail->Body    = 'Hello, '. $_POST['fullname'] .'<br/><br/>
	This is a confirmation that we have received your application. You have applied for the Job Position -<b>' . $_POST['job_applied'] .'.</b>After sorting all the applicants, we will let you know and notify if you are to put through to the interviewing round or not. More information about our hiring process is available and will be disclosed during interview.<br/>Thank you for your application, and have a nice day.<br/><br/>
Regards,<br/>
HRDMO<br/>
<i>Bohol Northern Star College, Inc.</i>';    
					if($mail->send()){
						//Move to file storage
						move_uploaded_file($file_tmp,"../src/cv_upload/".$nfilename);
						//Save to database
						$pst = "INSERT INTO job_applicants (fullname, email, mobile, job_id, file, date_applied,status) VALUES (?,?,?,?,?,?,?)";
						$pstmt = $DBconn->prepare($pst);
						$pstmt->execute([$_POST['fullname'], $_POST['email'], $_POST['phone'], $_SESSION['job-id'], $nfilename, date('Y-m-d'), 'PENDING']);
						//
						echo 'success';					
					}else{
					echo '*Your application was not sent, Please try again later.';
						return;
					}
				} catch (Exception $e) {
					echo '*Send Email encountered an error.';
					return;
					}
				//End Send Notification
			}else{
			 echo $errors;
		  	}			 
		  }//==== End File Upload ===//
   } //==== OppForm isset ===//
?>