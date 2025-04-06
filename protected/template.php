<!DOCTYPE html>
<html>
<head>
<meta content="text/html; charset=UTF-8" http-equiv="content-type">
<meta name="description" content="Контейнерофф - продажа сборно-разборных контейнеров с доставкой по всей России" />

<link rel="stylesheet" href="../css/base_style.css" type="text/css">
<link rel="stylesheet" href="../css/style.css" type="text/css">
<link rel="stylesheet" href="../css/style_admin.css" type="text/css">
<title>Оплата онлайн</title>

<script type="text/javascript" src="../script/jquery.js"></script>
<script type="text/javascript" src="../script/jqmodal.js"></script>
<script type="text/javascript" src="../script/calendar.js"></script>

<link rel="shortcut icon" href="../images/favicon.ico" type="../image/x-icon" />

<!--[if IE 6]>
<style>
/* Background iframe styling for IE6. Prevents ActiveX bleed-through (<select> form elements, etc.) */
* iframe .jqm {position:absolute;top:0;left:0;z-index:-1;
	width: expression(this.parentNode.offsetWidth+'px');
	height: expression(this.parentNode.offsetHeight+'px');
}

/* Fixed posistioning emulation for IE6
     Star selector used to hide definition from browsers other than IE6
     For valid CSS, use a conditional include instead */
* html .jqmWindow {
     position: absolute;
     top: expression((document.documentElement.scrollTop || document.body.scrollTop) + Math.round(17 * (document.documentElement.offsetHeight || document.body.clientHeight) / 100) + 'px');
}
</style>
<![endif]-->
</head>

<?
// Число записей на страницу
define('COUNT_ON_PAGES', '50');

// Пользователь (оператор)
//define('OPER_LOGIN', '4659c671a1e102f55b9dc2533d99db6e0a85f5c1dfa47c5ea81cb71ac843219f19fc6b676aa2b1b8e6f93a42dc0e124091ba7902ac1c03844ef347aed4b8d58b');
//define('OPER_PASSWORD', '90b894effc19a3e304f0fcf93a5a1a5579df7b0e99c46d682b3ee5dd9e7138ce17467832d9e4bf853389d1b605cef9e6f8e0aa0bb0049b0d188e810fb5c49fa6');
define('OPER_LOGIN', hash("sha512", "myuser" . 'RL7cAeTybg7lK0ICvO6wEfUagvu2dSTO'));
define('OPER_PASSWORD', hash("sha512", "mypassword" . 'aD1FoFnp6xhg3VxVFi9NAMXhWwnYa4ET'));
// Отключаем отображение ошибок на экране
ini_set('display_errors', 'Off'); 

// Устанавливаем уровень отчетности об ошибках, исключая предупреждения и устаревшие функции
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
?>  

<body>
<div id='wrapper'>
<!-- header -->
	<header>
			<div class="header-container">
				<div class="logo">
					<a href="https://konteineroff.ru/">
						<img src="https://konteineroff.ru/images/logo3.png" 
							alt="Контейнерофф - продажа сборно-разборных контейнеров с доставкой по всей России" title="Контейнерофф - продажа сборно-разборных контейнеров с доставкой по всей России">
					</a>							
				</div>
				<div class="logo-text">
					<span>Контейнерофф - продажа сборно-разборных контейнеров</span>
				</div>
				<div class="header-contacts">
					<div>
						<a rel="nofollow" href="tel:+78003504749">8 (800) 350-47-49</a>
						<div class="dropdown">
							<div class="">
								<div>
									<div style="position:relative; top:0; left:0;">
										<div class="more_phone">
											<a rel="nofollow" class="no-decript" href="tel:+78003504749">
											8 (800) 350-47-49
											</a>
										</div>
										<div class="more_phone">
											<a rel="nofollow" class="no-decript" href="tel:+78003504749">
											8 (800) 350-47-49
											</a>
										</div>
									</div>
								</div>
							</div>
						</div>
						 <i class="svg inline  svg-inline-down" aria-hidden="true">
							<svg xmlns="http://www.w3.org/2000/svg" width="5"
								height="3" viewBox="0 0 5 3">
								<path class="cls-1" d="M250,80h5l-2.5,3Z" transform="translate(-250 -80)"></path>
							</svg>
						</i>
					</div>
				</div>
			</div>
		</header>

		<div class="menu-container">
			<!--TOPMENU-->
			<nav>
				<?if ($_SESSION["username"]==OPER_LOGIN && $_SESSION["password"]==OPER_PASSWORD)
					{?>
					<div class='item small '><a href="./" class='small'>Управление заказами</a></div>
					<div class='item small '><a href="?view=journal" class='small'>Журнал</a></div>
					<!-- div class='item small '><a href="?view=pop3" class='small'>Прием реестра п/п</a></div -->
					<div class='item small '><a href="?view=quit" class='small'>Выход</a></div>
					<?}?>
			</nav>
		<!--END TOPMENU-->
		</div>
	
<!-- header END-->

<div class="content">
<?
// Интерфейс пользователя с правом просмлтра журналов
if ($_SESSION["username"]==OPER_LOGIN && $_SESSION["password"]==OPER_PASSWORD):
	require "../MInB_booking/operate.php";
	if (isset($_GET['view']) && $_GET['view']=='journal')
		MInB_booking_logs();
		else
		if (isset($_GET['view']) && $_GET['view']=='pop3')			
			MInB_booking_pop3(true);
			else
			MInB_booking_payments();
	else:
	// Защита от подбора пароля брутфорсом
	sleep (1);
	?>
	<br /><br /><br />
	<form action='index.php' method=post>
	<table style="width:250px;">
		<tr>
		  <td style="padding-right:10px;"><h2>Логин</h2></th>
		  <td><input maxlength="20" name="user" type="text"></td>
		</tr>
		<tr>
		  <td style="padding-right:10px;"><h2>Пароль</h2></th>
		  <td><input maxlength="20" name="pass" type="password"></td>
		</tr>
		<tr>
		  <td colspan="2" rowspan="1">
		  </br>
		  <p><button type="submit" class="button">Вход</button></p>
		</tr>
	</table>
	</form>
	<?endif;?>
</div>

</div>

<?//Заполняющий DIV для затемнения всего окна?>
<div id='helper'></div>
<div class="jqmWindow" id='dialog'></div>

</body>
</html>


