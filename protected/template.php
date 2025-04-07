<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Контейнерофф - продажа сборно-разборных контейнеров с доставкой по всей России" />
    <title>Оплата онлайн</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <link rel="stylesheet" href="../css/base_style.css" type="text/css">
    <link rel="stylesheet" href="../css/style.css" type="text/css">
    <link rel="stylesheet" href="../css/style_admin.css" type="text/css">
    
    <link rel="shortcut icon" href="../images/favicon.ico" type="../image/x-icon" />

    <script type="text/javascript" src="../script/jquery.js"></script>
    <script type="text/javascript" src="../script/jqmodal.js"></script>
    <script type="text/javascript" src="../script/calendar.js"></script>
    
    <style>
        /* Дополнительные стили для шапки */
        .header-slogan {
            white-space: nowrap;
            overflow: visible;
            text-overflow: clip;
            font-size: 1.2rem;
        }
        
        .contact-phone {
            transition: all 0.3s ease;
            position: relative;
        }
        
        .contact-phone:hover {
            color: #f2722c;
        }
        
        .contact-phone::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 0;
            height: 2px;
            background: #f2722c;
            transition: width 0.3s ease;
        }
        
        .contact-phone:hover::after {
            width: 100%;
        }
        
        body {
            padding-top: 80px;
        }
        
        .site-header {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1000;
            background: white;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
    </style>
</head>

<?
define('COUNT_ON_PAGES', '50');
define('OPER_LOGIN', hash("sha512", "myuser" . 'RL7cAeTybg7lK0ICvO6wEfUagvu2dSTO'));
define('OPER_PASSWORD', hash("sha512", "mypassword" . 'aD1FoFnp6xhg3VxVFi9NAMXhWwnYa4ET'));

ini_set('display_errors', 'Off'); 
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

$isAuth = ($_SESSION["username"]==OPER_LOGIN && $_SESSION["password"]==OPER_PASSWORD);
?>  

<body class="d-flex flex-column min-vh-100">
    <div id='wrapper' class="flex-grow-1">

        <header class="site-header">
    		<div class="header-top">
        		<div class="container">
            		<div class="row align-items-center">
                		<div class="col-md-3 col-4">
                    		<a href="https://konteineroff.ru/" class="logo-link">
                        		<img src="https://konteineroff.ru/images/logo3.png" alt="Контейнерофф" class="header-logo">
                    		</a>
                		</div>
                		<div class="col-md-6 col-4 text-center">
                    		<div class="header-slogan">
                        		Контейнерофф - продажа сборно-разборных контейнеров
                    		</div>
                		</div>
                		<div class="col-md-3 col-4 text-end">
                    		<div class="header-contacts">
                        		<a href="tel:+78003504749" class="contact-phone">
                            		8 (800) 350-47-49
                        		</a>
                    		</div>
                		</div>
            		</div>
        		</div>
    		</div>
		</header>

        
		<? if ($isAuth): ?>
			<div class="menu-container">
    			<nav class="navbar navbar-expand-lg navbar-light bg-light shadow-sm">
        			<div class="container">
            			<button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-expanded="false">
                			<span class="navbar-toggler-icon"></span>
            			</button>
            			<div class="collapse navbar-collapse" id="navbarNav">
                			<ul class="navbar-nav mx-auto menu-list">
                    			<li class="nav-item mx-3">
                        			<a class="nav-link text-dark" href="./" id="orders-link">
                            			<i class="bi bi-list-check me-1"></i><b>Управление заказами</b>
                        			</a>
                    			</li>
                    			<li class="nav-item mx-3">
                        			<a class="nav-link text-dark" href="?view=journal" id="journal-link">
                            			<i class="bi bi-journal-text me-1"></i><b>Журнал</b>
                        			</a>
                    			</li>
                    			<li class="nav-item mx-3">
                        			<a class="nav-link text-dark" href="?view=quit" id="quit-link">
                            			<i class="bi bi-box-arrow-right me-1"></i><b>Выход</b>
                        			</a>
                    			</li>
                			</ul>
            			</div>
        			</div>
    			</nav>
			</div>
		<? endif; ?>
    
        <main class="flex-grow-1">
            <? if ($isAuth): ?>
                <?php
                    require "../MInB_booking/operate.php";
                    if (isset($_GET['view']) && $_GET['view']=='journal')
                        MInB_booking_logs();
                    else if (isset($_GET['view']) && $_GET['view']=='pop3')            
                        MInB_booking_pop3(true);
                    else
                        MInB_booking_payments();
                ?>
            <? else: ?>
                <? sleep(1); ?>
                <div class="auth-container d-flex justify-content-center align-items-center">
                    <div class="auth-card p-5 shadow-lg">
                        <div class="text-center mb-4">
                            <h1 class="auth-title mb-3">Авторизация</h1>
                            <p class="text-muted">Введите ваши учетные данные</p>
                        </div>
                        
                        <form action='index.php' method="post">
                            <div class="mb-4 text-center">
                                <label for="user" class="form-label auth-label"><b>Логин</b></label>
                                <input type="text" class="form-control auth-input" id="user" name="user" maxlength="20" required>
                            </div>
                            <div class="mb-4 text-center">
                                <label for="pass" class="form-label auth-label"><b>Пароль</b></label>
                                <input type="password" class="form-control auth-input" id="pass" name="pass" maxlength="20" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn auth-btn rounded-pill py-3">Войти</button>
                            </div>
                        </form>
                    </div>
                </div>
            <? endif; ?>
        </main>

    </div>

    <div id='helper'></div>
    <div class="jqmWindow" id='dialog'></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
<script>
document.addEventListener('DOMContentLoaded', function() {
    
    function clearActiveLinks() {
        document.querySelectorAll('.nav-link').forEach(link => {
            link.classList.remove('active');
        });
    }
    
    
    function setActiveLink() {
        clearActiveLinks();
        const path = window.location.pathname.split('/').pop();
        const query = window.location.search;
        
        if (query.includes('view=journal')) {
            document.getElementById('journal-link').classList.add('active');
        } else if (query.includes('view=quit')) {
            document.getElementById('quit-link').classList.add('active');
        } else {
            document.getElementById('orders-link').classList.add('active');
        }
    }
    
    // Инициализация
    setActiveLink();
    

});
</script>
</html>
