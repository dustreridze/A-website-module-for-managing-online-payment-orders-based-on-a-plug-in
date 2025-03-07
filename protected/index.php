<?php
 session_start();
 if (!isset($_GET['view'])) $_GET['view']=''; 
 if ($_GET['view']=='quit') session_unset();

 if (isset($_POST['user']) && isset($_POST['pass']))
	{
	$_SESSION["username"] =hash("sha512", $_POST['user'].'RL7cAeTybg7lK0ICvO6wEfUagvu2dSTO');
	$_SESSION["password"]=hash("sha512", $_POST['pass'].'aD1FoFnp6xhg3VxVFi9NAMXhWwnYa4ET');
	}

 if (!isset($_SESSION["username"]) || !isset($_SESSION["password"])) $_SESSION["username"]=$_SESSION["password"]='';

 require "template.php";
?>

