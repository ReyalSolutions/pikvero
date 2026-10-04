<?php //index TEACHERS
    error_reporting(0);
	ob_start();
	session_start();
	require_once("../includes/connection.php"); 
?>      
<!DOCTYPE html>
<html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title>Teachers' Login</title>
  <link rel="icon" type="image/x-icon" href="../favicon.png">
  <!-- Tell the browser to be responsive to screen width -->
  <meta content="width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no" name="viewport">
  <!-- Bootstrap 3.3.6 -->
  <link rel="stylesheet" href="../css/bootstrap.min.css">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.5.0/css/font-awesome.min.css">
  <!-- Ionicons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/ionicons/2.0.1/css/ionicons.min.css">
  <!-- Theme style -->
  <link rel="stylesheet" href="../css/AdminLTE.min.css">
  <!-- iCheck -->
  <link rel="stylesheet" href="../fontawesome-5.15.4/css/all.min.css"> 
  <link rel="stylesheet" href="../css/blue.css">
  <link rel="stylesheet" href="../css/custom_style.css">
  <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
  <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
  <!--[if lt IE 9]>
  <script src="https://oss.maxcdn.com/html5shiv/3.7.3/html5shiv.min.js"></script>
  <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
  <![endif]-->
</head>
<style type="text/css">
  body{
     background-image: url("../images/bnsc_front01.jpg");
     background-repeat: no-repeat;
     background-size: cover;
     height: 80%;
     filter: brightness(50%);
  }
  .badge-title{
    position: absolute;
    background-color: #fff;
    /*box-shadow: 0px 0px 10px 0px rgba(0,0,0,.4);*/
    /*border:1px solid #E2E2E2;*/
    border-top-left-radius:5px;
    font-size: 25px;
    padding:5px 15px;
    left:70.3%;
    bottom:-13px;
    color:#0275d8;
  }.btn{
    background-color: #0275d8;
    padding:5px;
    width: 100%;
    color:#fff;
    border-radius: 0px;
  }
  .btn:hover{
    color:#fff;
    opacity: .9;
  }
</style>
<body class="">
<div class="login-box">
  <!-- <div class="login-logo">
    <a href="#"><b>BNSC </b>EDP</a>

  </div> -->
  <!-- /.login-logo -->
  <div class="login-div">
    <div class="header-login">
      <a href="https://www.bnsc.edu.ph/" target="_blank" title="Navigate to BNSC website">
      <img src="../images/BNSC-logo.png" width="90" height="90">
      </a>
      <label class="compname"><a href="#"><b>TEACHER</b></a></label>
      <span class="badge-title">Sign in</span>
    </div>
    
    <div class="pd-2"> 
    
    <?php if ($_SESSION['message'] != ""){echo '<p class="alert-msg" id="alert-msg">'.$_SESSION['message'].'</p>'; 
    unset($_SESSION['message']); }?>
    <p id="alert" style="background-color: orange;"></p>
    <form method="post" action="check_teacher_login.php">
      <div class="form-group has-feedback">
        <input autofocus autocomplete="off" type="text" id="uname" name="username" class="form-control" placeholder="Username"/>
        <span class="glyphicon glyphicon-user form-control-feedback"></span>
      </div>
      <div class="form-group has-feedback">
        <input type="password" name="password" class="form-control" id="pass" placeholder="Password"/>
        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
      </div>
      <div class="row">
       <div class="col-xs-8">
          <div class="checkbox icheck">
            <label>
              <input type="checkbox" name="remember"> Remember Me
            </label>
          </div>
          <a href="forgot_pass.php">I forgot my password.</a>
        </div>
        <!-- /.col -->
        <div class="col-xs-4">
          <button type="submit" id="btnlogin" class="btn btn-primary btn-block btn-flat"><i class="fa fa-sign-in-alt"></i> Sign In</button>
        </div>
        <!-- /.col -->

      </div>
    </form><hr>
    <a href="../login.php" class="btn">Admin Login</a>
    
    <br>

  </div>
</div>
  <!-- /.login-box-body -->
</div>
<!-- /.login-box -->

<!-- jQuery 2.2.3 -->
<script src="../js/jquery-2.2.3.min.js"></script>
<!-- Bootstrap 3.3.6 -->
<script src="../js/bootstrap.min.js"></script>
<!-- iCheck -->
<script src="../js/icheck.min.js"></script>
<script>
  $(function () {
    $('input').iCheck({
      checkboxClass: 'icheckbox_square-blue',
      radioClass: 'iradio_square-blue',
      increaseArea: '20%' // optional
    });
      
    $("#btnlogin").on('click',function(e){
        var uname = $("#uname").val();
        var pass = $("#pass").val();
        var dept = $("#dept").val();
        if(uname == ""){
          // $("#alert").val("Please input your username or password!");
          // $("#alert").attr("class","alert-msg");
           $("#dept").removeAttr("style","border:1px solid red;background-color:#fff0f0;");
          $("#pass").removeAttr("style","border:1px solid red;background-color:#fff0f0;");
          $("#uname").attr("style","border:1px solid red;background-color:#fff0f0;");
          $("#uname").focus();
          e.preventDefault();
        }else if(pass == ""){
          $("#dept").removeAttr("style","border:1px solid red;background-color:#fff0f0;");
          $("#uname").removeAttr("style","border:1px solid red;background-color:#fff0f0;");
          $("#pass").attr("style","border:1px solid red;background-color:#fff0f0;");
          $("#pass").focus();
          e.preventDefault();
        }
        else if(dept == ""){
          $("#pass").removeAttr("style","border:1px solid red;background-color:#fff0f0;");
          $("#uname").removeAttr("style","border:1px solid red;background-color:#fff0f0;");
          $("#dept").attr("style","border:1px solid red;background-color:#fff0f0;");
          $("#dept").focus();
          // $("#alert").val("Please select department!");
          // $("#alert").attr("class","alert-msg");
          e.preventDefault();
        }
    });
    setTimeout(function(){
      $("#alert-msg").attr("style","display:none;");
    },3000);
  });
</script>
</body>
</html>
