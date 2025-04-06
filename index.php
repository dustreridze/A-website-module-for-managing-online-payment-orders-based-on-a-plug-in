<!DOCTYPE html>
<html>
<head>
<meta content="text/html; charset=UTF-8" http-equiv="content-type">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="description" content="Контейнерофф - продажа сборно-разборных контейнеров с доставкой по всей России" />
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="css/ripple.css" type="text/css">
<link rel="stylesheet" href="css/base_style.css" type="text/css">
<link rel="stylesheet" href="css/style.css" type="text/css">

<script type="text/javascript" src="script/jquery.js"></script>
<script type="text/javascript" src="script/jqmodal.js"></script>
<script type="text/javascript" src="script/calendar.js"></script>
<script type="text/javascript" src="script/ripple.js"></script>
<script type="text/javascript" src="script/script.js"></script>
<!--<script type="text/javascript" src="script/chosen.jquery.min.js"></script>
<link rel="stylesheet" href="css/chosen.css" charset="utf-8" />-->

<link rel="shortcut icon" href="images/favicon.ico" type="image/x-icon" />

<title>Оплата онлайн</title>

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

<div class="wrapper">
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
<!-- header END-->

<div class="content">

<!--<br /><br />
<h3 style='line-height:1.5em;'>Уважаемые Клиенты!<br />Оплата через этот сервис временно приостановлена по техническим причинам. <br />Приносим свои извинения<br /> Пожалуйста, используйте иной способы оплаты.</h3>
<br /><br />-->
<?
require "MInB_booking/operate.php";
MInB_booking_frontend();
?>

</div>

</div>

<footer>
	<div class="footer-inner">
		<div class="footer-left">
			<div>
				2023 © “Контейнерофф”: Продажа сборно-разборных контейнеров						
			</div>
			<div>
				Адрес:  Владимир,Промышленный проезд, 5б					
			</div>
			<div>
				E-mail: <a href="mailto:MAIL@KONTEINEROFF.RU">MAIL@KONTEINEROFF.RU</a>				
			</div>
		</div>
		<div class="footer-right">
			<div class="pays">
				<i title="ПСБ" class="psb"></i>
				<i title="МИР" class="mir"></i>
				<i title="Visa" class="visa"></i>
				<i title="MasterCard" class="mastercard"></i>						
			</div>
		</div>
	</div>
</footer>
<?//Заполняющий DIV для затемнения всего окна?>
<div id='helper'></div>
<div class="jqmWindow" id='dialog'></div>

	
</body>
</html>
