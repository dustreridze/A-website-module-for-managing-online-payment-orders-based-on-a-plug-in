<!DOCTYPE html>
<head>
    <title>konteineroff.ru</title>
	<meta content="text/html; charset=UTF-8" http-equiv="content-type">
	<link rel="stylesheet" href="../css/style.css" type="text/css">
	<link rel="shortcut icon" href="../images/favicon.ico" type="image/x-icon" />
	<script type="text/javascript" src="../script/jquery.js"></script>
	<script type="text/javascript" src="../script/calendar.js"></script>
</head>

<?
// Число записей на страницу
define('COUNT_ON_PAGES', '50');

// Пользователь (оператор)
define('OPER_LOGIN', '4659c671a1e102f55b9dc2533d99db6e0a85f5c1dfa47c5ea81cb71ac843219f19fc6b676aa2b1b8e6f93a42dc0e124091ba7902ac1c03844ef347aed4b8d58b');
define('OPER_PASSWORD', '90b894effc19a3e304f0fcf93a5a1a5579df7b0e99c46d682b3ee5dd9e7138ce17467832d9e4bf853389d1b605cef9e6f8e0aa0bb0049b0d188e810fb5c49fa6');
?>  
<div class="wrapper">
<div class="header">
	<div class="logo">
		<a href="http://konteineroff.ru/">
		<img src="../images/logo3.png" alt="" title="" border="0">
		</a>
	</div>
</div>
<br />
 
<div class="clearboth"></div>
<body>
<div id="content">
	<ul class="menu">
		<?if ($_SESSION["username"]==OPER_LOGIN && $_SESSION["password"]==OPER_PASSWORD)
			{?>
			<li class="menu_item"><a href="?view=otch">Отчет по платежам</a></li>
			<li class="menu_item"><a href="?view=nootch">Отказы</a></li>
			<li class="menu_item"><a href="?view=journal">Журнал</a></li>
			<li class="menu_item"><a href="?view=mail">Реестр</a></li>
			<li class="menu_item"><a href="?view=pop3">Прием реестра п/п</a></li>
			<li class="menu_item"><a href="?view=quit">Выход</a></li>
			<?}?>
	</ul>
	
	<div class="centered">
	<br>	
<?
	// Интерфейс пользователя с правом ввода-редактирования броней (оператор)
	if ($_SESSION["username"]==OPER_LOGIN && $_SESSION["password"]==OPER_PASSWORD):
		require "../MInB/operate.php";
		if (isset($_GET['view']) && $_GET['view']=='journal')
			MInB_operate_logs();
			else
			if (isset($_GET['view']) && $_GET['view']=='mail')
				MInB_operate_mail();
				else
				if (isset($_GET['view']) && $_GET['view']=='pop3')
					MInB_operate_pop3(true);
					else
					if (isset($_GET['view']) && $_GET['view']=='nootch')
						MInB_operate_nopayments();
						else
						MInB_operate_payments();
		else:
		// Защита от подбора пароля брутфорсом
		sleep (1);
		?>
		<form action='index.php' method=post>
		<table style="width:250px;">
			<tr>
			  <td style="border:none">Логин</td>
			  <td  style="border:none"><input maxlength="20" name="user"></td>
			</tr>
			<tr>
			  <td style="border:none">Пароль</td>
			  <td style="border:none"><input maxlength="20" name="pass" type="password"></td>
			</tr>
			<tr>
			  <td colspan="2" rowspan="1" style="border:none;">
			  </br>
			  <p><button type="submit" class="button">Вход</button></p>
			</tr>
		</table>
		</form>
	<?endif;?>
	</div>
</div>
</div>


