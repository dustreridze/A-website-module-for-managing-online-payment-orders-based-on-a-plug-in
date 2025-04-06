<?php
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
require_once str_replace('\\', '/', DIRNAME(__FILE__)) . "/../lib/random_compat/random.php";
// Отключаем отображение ошибок на экране
ini_set('display_errors', 'Off'); 

// Устанавливаем уровень отчетности об ошибках, исключая предупреждения и устаревшие функции
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
ini_set('max_input_time', -1);
ini_set('max_execution_time', 600);

set_error_handler('error_handler');
function error_handler($errno, $errmsg, $filename, $linenum)
{
/* Заглушка */
}

// Параметры БД
define('DB_HOST', 'localhost');
define('DB_NAME', 'u2921074_konteineroff');
define('DB_USER', 'u2921074_root');
define('DB_PASSWORD', ')))');

// Определим путь до директории плагина
define('MINB_BOOKING_PATH', str_replace('\\', '/', DIRNAME(__FILE__)) . '/');

// Определим путь до корневой директории модуля
define('BASE_PATH', str_replace('\\', '/', DIRNAME(__FILE__)) . '/../');

/*

// ID магазина по-умолчанию
define('MINB_BOOKING_MERCHANTID', '79036777');

define('PSB_TERMINAL_1', '79036777');
define('PSB_MERCHANT_1', false);
define('PSB_MERCH_NAME_1', false);
define('PSB_COMP_1_1', 'C50E41160302E0F5D6D59F1AA3925C45');
define('PSB_COMP_2_1', '00000000000000000000000000000000');


// Для отправки результатов платежа в лк тест
//define ('URL_MSG','http://nord.kymdeni.tmweb.ru/index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component');
define ('URL_MSG','https://xn--e1aalcpcdfrp2aa.xn--p1ai/index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component');//prod

*/
define ('URL_MSG','https://nord.kymdeni.tmweb.ru/index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component');
define('PSB_TERMINAL_1', '79036777');
define('PSB_MERCHANT_1', false);
define('PSB_MERCH_NAME_1', false);
define('PSB_COMP_1_1', 'C50E41160302E0F5D6D59F1AA3925C45');
define('PSB_COMP_2_1', '00000000000000000000000000000000');


// Тип сервера по-умолчанию
define('MINB_BOOKING_SERVERTYPE', 'PROD'); //TEST

// Предавторизация по-умолчанию
define('MINB_BOOKING_PREAUTH', 'OFF');

// Адреса отправки отчета
$mail_to = array('george.steam@mail.ru');

MInB_booking_control_payments();

// Инициализируем всплывающие окна
?><script>	
$(document).ready(function(){
	$('#dialog').jqm();	
}); 	
</script><?

function MInB_operate_sendmail($bookingId, $qrLink)
{
    global $mail_to;
    require 'lib/autoload.php';
    // Создаем QR-код
    $qrCode = new QrCode($qrLink); // $qrLink — ваша ссылка для оплаты
    $qrCode->setSize(300); // Размер QR-кода
    $qrCode->setMargin(10); // Отступы
    
    // Используем PNG-формат для генерации
    $writer = new PngWriter();
    $result = $writer->write($qrCode);
    
    // Преобразуем QR-код в base64
    $qrCodeImage = 'data:image/png;base64,' . base64_encode($result->getString());
    // Подключим функционал коробочного решения
    MInB_booking_boxInit();

    // Получим данные о платеже
    $payment = MInB_getPayment($bookingId, true, array(
        'bookingWord',
        'bookingName',
        'bookingDate',
        'bookingTime',
        'email',  // Email пользователя
        'bookingRoom',
        'bookingDescription',
        'bookingMailSent'
    ));

    // Проверяем, отправлялось ли письмо ранее
    if ($payment['options']['bookingMailSent'] == 'sent') return;
    if ($payment['params']['status'] == 1 ) return;

    // Формируем сообщение
    $msg = "
    <!DOCTYPE html>
    <html lang='ru'>
    <head>
        <meta charset='UTF-8'>
        <meta name='viewport' content='width=device-width, initial-scale=1.0'>
        <title>Оформление заказа</title>
        <style>
            body {
                font-family: Arial, sans-serif;
                line-height: 1.6;
                color: #333;
                background-color: #f9f9f9;
                margin: 0;
                padding: 0;
            }
            .container {
                max-width: 600px;
                margin: 20px auto;
                padding: 20px;
                background-color: #fff;
                border: 1px solid #ddd;
                border-radius: 8px;
                box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            }
            h1 {
                font-size: 24px;
                color: #007bff;
                margin-bottom: 20px;
            }
            p {
                margin: 10px 0;
            }
            .button {
                display: inline-block;
                padding: 12px 24px;
                background-color: #007bff;
                color: #fff;
                text-decoration: none;
                border-radius: 5px;
                font-size: 16px;
                margin-top: 20px;
            }
            .button:hover {
                background-color: #0056b3;
            }
            .footer {
                margin-top: 30px;
                font-size: 14px;
                color: #777;
                text-align: center;
            }
            .qr-code {
                margin-top: 20px;
                text-align: center;
            }
            .qr-code img {
                max-width: 100%;
                height: auto;
            }
        </style>
    </head>
    <body>
        <div class='container'>
            <h1>Оформление заказа</h1>
            <p><strong>Номер заказа:</strong> {$payment['options']['bookingWord']}</p>
            <p><strong>Email:</strong> {$payment['options']['email']}</p>
            <p><strong>Описание заказа:</strong> {$payment['options']['bookingDescription']}</p>
            <p><strong>Дата заказа:</strong> {$payment['options']['bookingDate']}</p>
            <p><strong>Сумма оплаты:</strong> " . ($payment['params']['startAmount'] / 100) . " руб</p>
            <p><strong>Идентификатор операции:</strong> {$payment['params']['session_id']}</p>
            <p><strong>Ссылка для оплаты (действует 72 часа):</strong></p>
            <a href='{$qrLink}' class='button'>Оплатить</a>
            <div class='qr-code'>
                <p>Или отсканируйте QR-код для оплаты:</p>
                <img src='{$qrCodeImage}' alt='QR-код для оплаты'>
            </div>
            <div class='footer'>
                <p>Если у вас возникли вопросы, свяжитесь с нашей поддержкой.</p>
                <a href='tel:+78003504749'>8 (800) 350-47-49</a>
            </div>
        </div>
    </body>
    </html>
    ";

    // Настройки почты
    date_default_timezone_set('Etc/UTC');

    require BASE_PATH . 'protected/PHPMailer/PHPMailerAutoload.php';

    $mail = new PHPMailer();
    /*
    $mail->isSMTP();
    $mail->SMTPDebug = 0; // 0 = off (for production use); 1 = client messages; 2 = client and server messages
    $mail->Debugoutput = 'html';
    $mail->CharSet = "UTF-8";
    $mail->Encoding = "base64";
    $mail->Host = "mail.pay-ok.org";
    $mail->Port = 25;
    $mail->SMTPAuth = true;
    $mail->Username = "nobody@pay-ok.org";
    $mail->Password = "3T9z2Q8q";
    */
    $mail->isSMTP();
    $mail->Host = 'smtp.mail.ru'; // SMTP-сервер Mail.ru
    $mail->SMTPAuth = true;
    $mail->CharSet = "UTF-8";
    $mail->Encoding = "base64";
    $mail->Username = 'george.steam@mail.ru'; // Ваша почта Mail.ru
    $mail->Password = '4iXxNeaPhr2wSSAjgRmq'; // Пароль от почты (может потребоваться специальный пароль для приложений)
    $mail->SMTPSecure = 'ssl'; // Mail.ru использует SSL
    $mail->Port = 465; // Порт для безопасного соединения
    $mail->setFrom('george.steam@mail.ru', 'Платежная система ООО «КОНТЕЙНЕРОФФ»');
    $mail->addReplyTo('george.steam@mail.ru', 'Платежная система ООО «КОНТЕЙНЕРОФФ»');
    
    // Отправка письма администраторам
    foreach ($mail_to as $addr) {
        $mail->addAddress($addr);
    }

    // Отправка письма пользователю
    if (!empty($payment['options']['email'])) {
        $mail->addAddress($payment['options']['email']);
    }

    $mail->Subject = "Счет на оплату № {$payment['options']['bookingWord']}";
    $mail->msgHTML($msg);
    $mail->AltBody = strip_tags($msg);

    // Отправляем письмо и логируем результат
    if ($mail->send()) {
        MInB_log(
            $bookingId,
            'Сообщение с ссылкой на оплату по заказу ' . $payment['options']['bookingWord'] . 
            ' отправлено на почту ' . $payment['options']['email'],
            'MInB_operate_sendmail'
        );


        MInB_updatePaymentOptions($bookingId, array('bookingMailSent' => 'sent'), false);
        return true;
    } else {
        MInB_log(
            $bookingId,
            'Ошибка отправки сообщения на оплату заказа '. $payment['options']['bookingWord'] . 
            ' отправлено на почту ' . $payment['options']['email'] .'ошибка ->'. $mail->ErrorInfo,
            'MInB_operate_sendmail'
        );
        return false;
    }
}

// frontend страница
function MInB_operate_sendmsg($id=null,$ORDER_STATUS=null)
{
	if ($id==null || $ORDER_STATUS==null) return (false);

	//Собираем строку ответа STATUS=STATUS&SUMMA=SUMMA&DATE=DATE&ORDER_ID=ORDER_ID&SESSION_ID=SESSION_ID&PAY_ID=PAY_ID
	$payment = MInB_getPayment($id, true, array('bookingWord',));
	$STATUS = array(
            'STATUS' => $ORDER_STATUS,  // Статус платежа
            'PAY_ID' => $payment['options']['bookingWord'],  // ID заказа
            'SUMMA' => number_format($payment['params']['paidAmount'] / 100, 2, '.', ''),  // Сумма платежа
            'ORDER_ID' => $payment['params']['order_id'],  // Номер заказа в системе
            'SESSION_ID' => $payment['params']['session_id'],  // Идентификатор сессии
            'DATE' => $payment['params']['paidDate'],  // Дата и время платежа
);

			
			//echo 'id = '.$id.'<br />';
			//echo 'Сообщение в ЛК отправлено ==> '.$STATUS.'<br />';
			//$private_key=openssl_pkey_get_private ('file://'.MINB_KEY);
			//openssl_private_encrypt ( $STATUS, $STATUS_ENC, $private_key );

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, URL_MSG);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($STATUS));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
			$answer = curl_exec($ch);	
			$error  = curl_error($ch);
			curl_close($ch);
            // Запись строки POST-запроса в файл
			/*echo '<pre>***'.$STATUS_ENC.'***</pre>';
			echo '<pre>***'.$STATUS.'***</pre>';
			$public_key=openssl_pkey_get_public ('file://'.MINB_CERT);
			openssl_public_decrypt ( $STATUS ,$STATUS , $public_key );
			echo $STATUS;*/

			if (empty($error))
				{
				MInB_log(
					$id,
					'Сообщение в ЛК отправлено ==> '. json_encode($STATUS),
					'MInB_operate_sendmsg'
					);
				}
				else
				{
				MInB_log(
					$id,
					'Ошибка отправки сообщения в ЛК ==> '.$error,
					'MInB_operate_sendmsg'
					);
				}
				
		return (empty($error));
}
function processBankResponse($resultData) {
    // Проверяем обязательные поля в массиве
    if (!isset($resultData['order_id'], $resultData['status'], $resultData['paidDate'], $resultData['paidAmount'])) {
        echo json_encode(["error" => "Отсутствуют обязательные поля в данных"]);
        http_response_code(400);
        exit;
    }

    // Логируем ответ
    MInB_log(
        $resultData['reference'],
        'Обработан ответ от банка: ' . json_encode($resultData),
        'processBankResponse'
    );
    $resultData['paidDate'] = str_replace('.', '-', $resultData['paidDate']);
    // Обновляем данные в базе, если статус платежа подтвержден
    if ($resultData['status'] === 1) {
        $db = MInB_Database::Instance();;
        $db->query('
            UPDATE
                `minbank_payments`
            SET
                `paidDate` = :paidDate,
                `status` = :status,
                `paidAmount` = :paidAmount,
                `ip` = :ip
            WHERE
                `payment_id` = :reference
        ', [
            ':paidDate' => date('Y-m-d H:i:s', strtotime('-3 hours', strtotime($resultData['paidDate']))),
            ':status' => $resultData['status'],
            ':paidAmount' => $resultData['paidAmount'],
            ':ip' => MInB_IP(),
            ':reference' => $resultData['reference'],
            
        ]);
    }

}
// /backend_logs.html
// Журнал
function MInB_booking_logs()
{
	// Подключим функционал коробочного решения
	MInB_booking_boxInit();
	
	// Настроим лимит записей
	$limit = ( MInB_post('limit', 100) === '0' ) ? false : MInB_post('limit', 100);
	
	// Проверим, возможно установлен ID
	$bookingId = MInB_post('bookingId', 0);
	
	if ( $bookingId != 0 )
	{
		$logs = MInB_getPaymentLogs($bookingId, $limit);

		if ( $logs === false )
			$logs = array();
	}
	else
		// Получим логи по всем операциям
		$logs = MInB_getPaymentsLogs($limit);
	
	// Отобразим шаблон
	require MINB_BOOKING_PATH . 'protected/templates/backend_logs.html';
}


// Инициализация коробочного решения
function MInB_booking_boxInit()
{
	// Подключим функционал коробочного решения
	require_once MINB_BOOKING_PATH . 'protected/library/MInB.php';
	
	try {
		// Настроим коробочное решение, БД
		MInB_Database::Instance('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
	}
	catch (Exception $e) {
		echo "У нас возникли временные трудности... Попробуйте повторить чуть позже...";  die();
	}
	// Определим, какой сервер использовать
	$server  = ( MINB_BOOKING_SERVERTYPE == 'TEST' ) ? 'https://test.3ds.payment.ru/cgi-bin/' :  'https://3ds.payment.ru/cgi-bin/'; 
	
	// Настроим соединение с платежной системой
	MInB_TWPG::Instance()->client()->setServer($server, MINB_BOOKING_SERVERTYPE);
}



// backend страница управление заказами
function MInB_booking_payments()
{
	// Подключим функционал коробочного решения
	MInB_booking_boxInit();
	
	// Проверим, редактирование ли это существующего платежа
	$bookingId = MInB_get('bookingId');
	
	// Проверяем
	if ( MInB_existsPayment($bookingId) )
	{
		// Действие, по-умолчанию - обновление
		$action = MInB_post('action', 'update');
		
		// Получим данные о платеже(до изменения)
		$payment = MInB_getPayment($bookingId, true, array(
			'bookingWord',
			'bookingName',
			'bookingDate',
			'bookingTime',
			'bookingTelefon',
			'bookingRoom',
			'bookingDescription'
		));
		
		
	
		switch ( $action )
		{
			// Получим данные с TWPG
			case 'getInformation':
				if ( $payment['params']['status'] != 0 && $payment['params']['status'] != 7 )
				{
					$information = MInB_getPayment($bookingId, false, array(), true);
					
					if ( isset($information['twpg_answer']) )
						$information = $information['twpg_answer'];
				}
				
				// Отобразим шаблон
				require MINB_BOOKING_PATH . 'protected/templates/backend_payment.html';
			break;
		
			// Возврат средств
			case 'refund':
				$protcontr   = trim(MInB_post('protcontr'));
				$protres   = trim(MInB_post('protres'));
				$notifyUrl = 'https://' . $_SERVER['SERVER_NAME'] . '/omist-33-2/notify.php?id=' . $bookingId .'&createdDate='.$answer['params']['createdDate'];
				if ($protcontr==$protres)			
				if ( MInB_existsPayment($bookingId, 1) || MInB_existsPayment($bookingId, 6) )
					if ( MInB_refundPayment($bookingId, MInB_post('bookingAmount', $payment['params']['paidAmount'] / 100)) )
						$updated = true;
						
				// Обновим данные
				$payment = MInB_getPayment($bookingId, true, array(
					'bookingWord',
					'bookingName',
					'bookingDate',
					'bookingTime',
					'bookingEmail',
					'bookingRoom',
					'bookingDescription'
				));
				
				// Отобразим шаблон
				require MINB_BOOKING_PATH . 'protected/templates/backend_payment.html';
			break;

			// Возврат средств
			case 'reverse':
				$protcontr   = trim(MInB_post('protcontr'));
				$protres   = trim(MInB_post('protres'));
				$notifyUrl = 'https://' . $_SERVER['SERVER_NAME'] . '/omist-33-2/notify.php?id=' . $bookingId .'&createdDate='.$answer['params']['createdDate'];
				if ($protcontr==$protres)			
				if ( MInB_existsPayment($bookingId, 1) || MInB_existsPayment($bookingId, 6) )
					if ( MInB_refundPayment($bookingId, MInB_post('bookingAmount', $payment['params']['paidAmount'] / 100)) )
						$updated = true;
						
				// Обновим данные
				$payment = MInB_getPayment($bookingId, true, array(
					'bookingWord',
					'bookingName',
					'bookingDate',
					'bookingTime',
					'bookingEmail',
					'bookingRoom',
					'bookingDescription'
				));
				
				// Отобразим шаблон
				require MINB_BOOKING_PATH . 'protected/templates/backend_payment.html';
			break;
			
			// Подтвердждение заказа
			case 'approve':
				if ( MInB_existsPayment($bookingId, 5) )
					if ( MInB_completionPayment($bookingId, MInB_post('bookingAmount', $payment['params']['startAmount'] / 100)) )
						$updated = true;
										
				// Обновим данные
				$payment = MInB_getPayment($bookingId, true, array(
					'bookingWord',
					'bookingName',
					'bookingDate',
					'bookingTime',
					'bookingEmail',
					'bookingRoom',
					'bookingDescription'
				));
				
				// Отобразим шаблон
				require MINB_BOOKING_PATH . 'protected/templates/backend_payment.html';
			break;
		
			// Удаление заказа
			case 'delete':
				if ( MInB_existsPayment($bookingId, 0) ) {
					echo MInB_isBlockedPayment ($bookingId);
					if ( !MInB_isBlockedPayment ($bookingId) ) // ************* ПАТЧ *************
						if ( MInB_deletePayment($bookingId) )
							$updated = true;
				}
				// Обновим данные
				$payment = MInB_getPayment($bookingId, true, array(
					'bookingWord',
					'bookingName',
					'bookingDate',
					'bookingTime',
					'bookingEmail',
					'bookingRoom',
					'bookingDescription'
				));
				
				// Отобразим шаблон
				require MINB_BOOKING_PATH . 'protected/templates/backend_payment.html';
			break;
		
			// Обновление заказа
			default:
			case 'update':
				// Проверим, если удаленный, запретим обновлять
				if ( MInB_existsPayment($bookingId, 0) )
					if ( !MInB_isBlockedPayment ($bookingId) ) // ************* ПАТЧ *************
				{
					// Флаг обновления
					$updated = false;
				
					// Посмотрим, пришло ли секретное слово
					$bookingWord = MInB_post('bookingWord', '');
					// и остальные параметры
					$startAmount = MInB_post('startAmount', $payment['params']['startAmount'] / 100);
					
					$bookingName = MInB_post('bookingName', $payment['options']['bookingName']);
					$bookingDate = MInB_post('bookingDate', $payment['options']['bookingDate']);
					$bookingTime = MInB_post('bookingTime', $payment['options']['bookingTime']);
					$bookingEmail = MInB_post('bookingEmail', $payment['options']['bookingEmail']);
					$bookingRoom = MInB_post('bookingRoom', $payment['options']['bookingRoom']);
					$bookingDescription = MInB_post('bookingDescription', $payment['options']['bookingDescription']);
					
					if ( !empty($bookingWord) )
					{
						MInB_updatePaymentParams ($bookingId, array(
							'startAmount' => $startAmount
						));
						
						// Обновляем данные платежа
						MInB_updatePaymentOptions($bookingId, array(
							'bookingWord' => $bookingWord,
							'bookingName' => $bookingName,
							'bookingDate' => $bookingDate,
							'bookingTime' => $bookingTime,
							'bookingEmail' => $bookingEmail,
							'bookingRoom' => $bookingRoom,
							'bookingDescription' => $bookingDescription
						));
					
						// Обновим флаг
						$updated = true;
					}
				}
				
				// Обновим данные
				$payment = MInB_getPayment($bookingId, true, array(
					'bookingWord',
					'bookingName',
					'bookingDate',
					'bookingTime',
					'bookingEmail',
					'bookingRoom',
					'bookingDescription'
					));
					
				// Отобразим шаблон
				require MINB_BOOKING_PATH . 'protected/templates/backend_payment.html';
			break;
		}
	}
	else
	{
		// Определим url текущей страницы
		$url = $_SERVER['REQUEST_URI'];
		$verifyError = false;
		
		// Сумма нового заказа
		$bookingAmount = MInB_post('bookingAmount');
		
		// Номер нового заказа
		$bookingWord = MInB_post('bookingWord', '');
		
		// Телефон клиента
		$bookingTelefon = MInB_post('bookingTelefon', '');
		
		// Проверим, есть ли уже такое секретное слово
		$verifyWord = MInB_getPaymentsByOption('bookingWord', $bookingWord);
		
		// Проверяем условия создания
		if ( !empty($bookingWord) )
		{
			if ( is_numeric($bookingAmount) && empty($verifyWord))
			{
				// Создаем заказ
				$booking = MInB_createPayment($bookingAmount, array(
					'bookingWord'        => $bookingWord,
					'bookingName'        => MInB_post('bookingName'),
					'bookingDate'      => MInB_post('bookingDate'),
					'bookingEmail'     => MInB_post('bookingEmail'),
					'bookingDescription' => MInB_post('bookingDescription'),
				));
			}
			else
				$verifyError = true;
		}

		// Ошибки, которые могут возникнуть
		$errors = array(
			'dataReverse'=>false,
			);	
		
		$dateBegin = MInB_post('dateBegin', '01.01.2024');
		$dateEnd = MInB_post('dateEnd', date('d.m.Y', strtotime('tomorrow')));
		if (strtotime($dateBegin)>strtotime($dateEnd)) $errors['dataReverse'] = true;
		
		$search_select = MInB_post('search_select');
		
		if ($search_select == '') $statusArr = array (0,1,5,6,7,8);
		if ($search_select == 'noopl') $statusArr = array (0,7,8);
		if ($search_select == 'opl') $statusArr = array (1,5,6);
		
		// ПОЛУЧИМ КОЛИЧЕСТВО ПЛАТЕЖЕЙ ПО УСЛОВИЮ
		$paymentsCount = MInB_getPayments(false, false, false, false, $statusArr, $dateBegin, $dateEnd);	
		
		$pages=ceil($paymentsCount/COUNT_ON_PAGES);
		if ($pages==0) $pages=1;
		$cpg=(is_numeric(MInB_post('cpg'))!==false && MInB_post('cpg')<=$pages && MInB_post('cpg')>=1)? MInB_post('cpg'):$pages;
		
		$first=($cpg-1)*COUNT_ON_PAGES;
		$size=COUNT_ON_PAGES;
	
		// Получим платежи
		$payments = MInB_getPayments(true, array(
			'bookingWord',
			'bookingName',
			'bookingDate',
			'bookingTime',
			'bookingEmail',
			'bookingRoom',
			'bookingDescription',
			'procDate',
			'procPP',
			'paymentmethod'
		), $first, $size, $statusArr, $dateBegin, $dateEnd);
		
		
		// Отобразим шаблон
		require MINB_BOOKING_PATH . 'protected/templates/backend_payments.html';
	}
}

// Получение ID из секретного слова
function MInB_booking_getIdByWord($word)
{
	$id = MInB_getPaymentsByOption('bookingWord', $word);
	
	return ( empty($id) ) ? false : $id[0]['payment_id'];
}

// frontend страница
function MInB_booking_frontend()
{
	// Подключим функционал коробочного решения
	MInB_booking_boxInit();
	$db = MInB_Database::Instance();

	// Блокируем таблицы на период обработки платежа
	$stmt = $db->query('
		LOCK TABLES `minbank_payments` WRITE, `minbank_payments_options` WRITE, `minbank_payments_logs` WRITE
		');
	
	// Ошибки, которые могут возникнуть
	$errors = array(
		'paymentNotExists' => false,
		'paymentAlreadyProcessed' => false,
		'paymentTemporaryUndefined' => false,
		'paymentNotDefined' => false
		);
	
	// Флаг, найдено
	$founded = false;

	// Данные, которые должны быть
	$bookingId   = MInB_booking_getIdByWord(MInB_post('bookingWord'));
	
	// Данные для подтверждения платежа
	$bookingGetId = MInB_booking_getIdByWord(MInB_get('bookingWord'));
	
	// Проверяем, возможно подтверждение
	if ( $bookingGetId !== false && MInB_existsPayment($bookingGetId) ) {
		// Получим ответ
		sleep(2);
		$answer=MInB_getPaymentStatus($bookingGetId);
		
		if ((isset($answer['twpg_answer']['RESULT']) && ($answer['twpg_answer']['RESULT'] == 0))
				|| $answer['params']['status']==1 || $answer['params']['status']==2) {
				// При повторном запросе на подтверждение не дергать TWPG
				if ($answer['params']['status']==0 &&  strcasecmp($answer['twpg_answer']['RC'],'00') == 0) {
					MInB_approvePayment($bookingGetId);
					//MInB_operate_sendmail($bookingGetId);
					MInB_operate_sendmsg($bookingGetId,'APPROVED');
				}
				elseif(strcasecmp($answer['twpg_answer']['RC'],'00') == 0) {
					MInB_operate_sendmail($bookingGetId);
					//MInB_operate_sendmsg($bookingGetId,'APPROVED');
				}
			}
		// Отобразим шаблон
		require MINB_BOOKING_PATH . 'protected/templates/frontend_result.html';	
		return;
	}
	elseif ( $bookingId !== false ) {
		// Получаем платеж
		$payment = MInB_getPayment($bookingId, true, array(
			'bookingWord',
			'bookingName',
			'bookingDate',
			'bookingTime',
			'bookingEmail',
			'bookingRoom',
			'bookingDescription',
		));
		
		// Проверяем платеж
		if ( is_array($payment) && $payment['params']['status'] == 0 ) {
			$description = 'Оплата заказа №' . $payment['options']['bookingWord'];
			if ( MInB_post('action') != 'pay' ) {
				// Флаг, найдена заказ
				$founded = true;
			}
			else {
				// Получим ответ
				sleep(2);
				$answer=MInB_getPaymentStatus($bookingId);
				
				if ((isset($answer['twpg_answer']['RESULT']) && ($answer['twpg_answer']['RESULT'] == 0))
					|| $answer['params']['status']==1 || $answer['params']['status']==2) {
					// При повторном запросе на подтверждение не дергать TWPG
					if ($answer['params']['status']==0 &&  strcasecmp($answer['twpg_answer']['RC'],'00') == 0) {
						MInB_approvePayment($bookingGetId);
						//MInB_operate_sendmail($bookingGetId);
						MInB_operate_sendmsg($bookingGetId,'APPROVED');
					}
					
					$errors['paymentAlreadyProcessed'] = true;
				}
				else {
					// URL
					$url = "https://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?bookingWord=' . MInB_post('bookingWord').'&createdDate='.$answer['params']['createdDate'];
					$notifyUrl = 'https://' . $_SERVER['SERVER_NAME'] . '/omist-33-2/notify.php?id=' . $bookingId .'&createdDate='.$answer['params']['createdDate'];
					
					// Процессируем платеж
					$booking = MInB_processPayment($bookingId, array(
						'description' => $description,
						'orderType'   => 'Purchase',
						'notifyUrl'  => $notifyUrl,
						'approveUrl'  => $url,
						'declineUrl'  => $url,
						'cancelUrl'   => $url,
						'email' => $payment['options']['bookingEmail']
					));	
				}
			}
		}
		elseif ( is_array($payment) && ($payment['params']['status'] == 1 || $payment['params']['status'] == 5 || $payment['params']['status'] == 6)) {
				// Если уже оплачен
				$errors['paymentAlreadyProcessed'] = true;
		}
		else {
				// Если уже удален/возвращен или др.
				$errors['paymentNotExists'] = true;
		}
		
	}
	else {
		// Платеж не найден или секретное слово неверно
		if (MInB_post('bookingWord')!='') $errors['paymentNotExists'] = true;
	}
	
	// Отобразим шаблон
	require MINB_BOOKING_PATH . 'protected/templates/frontend.html';
}
/*
function MInB_operate_sendmail($bookingId)
{
	global $mail_to;

	// Подключим функционал коробочного решения
	MInB_booking_boxInit();

	// Получим данные о платеже
	$payment = MInB_getPayment($bookingId, true, array(
		'bookingWord',
		'bookingName',
		'bookingDate',
		'bookingTime',
		'bookingEmail',
		'bookingRoom',
		'bookingDescription',
		'bookingMailSent'
	));
	
	if ($payment['options']['bookingMailSent']=='sent') return;
	if ($payment['params']['status']!=1 && $payment['params']['status']!=6) return;
	
	$msg="Получена оплата по счету (заказу) № {$payment['options']['bookingWord']}".
		 "<br/>ФИО: {$payment['options']['bookingName']}".
		 "<br/>Email: {$payment['options']['bookingEmail']}".
		 "<br/>Описание заказа: {$payment['options']['bookingDescription']}".
		 "<br/>Дата заказа: {$payment['options']['bookingDate']}".
		 
		 "<br/>Дата, время оплаты: {$payment['params']['paidDate']}".
		 "<br/>Сумма оплаты: ".($payment['params']['paidAmount']/100)." руб".
		 "<br/>Идентификатор операции:  {$payment['params']['order_id']}"
		 ;

	 // ПОЧТА
	date_default_timezone_set('Etc/UTC');

	require BASE_PATH.'protected/PHPMailer/PHPMailerAutoload.php';

	$mail = new PHPMailer();
	$mail->isSMTP();
	$mail->SMTPDebug = 0; // 0 = off (for production use); 1 = client messages ;2 = client and server messages
	$mail->Debugoutput = 'html';
	$mail->CharSet = "UTF-8";
	$mail->Host = "mail.pay-ok.org";
	$mail->Port = 25;
	$mail->SMTPAuth = true;
	$mail->Username = "nobody@pay-ok.org";  
	$mail->Password = "3T9z2Q8q";
	$mail->setFrom('nobody@pay-ok.org', 'Платежная система ООО Омис');
	$mail->addReplyTo('nobody@pay-ok.org', 'Платежная система ООО Омис');
	foreach ($mail_to as $addr) $mail->addAddress($addr);
	$mail->Subject = "Получена оплата";
	$mail->msgHTML($msg);
	$mail->AltBody = ' ';
		
		//send the message, check for errors
	if ($mail->send())
		{
		MInB_log(
			$bookingId,
			'Сообщение отправлено',
			'MInB_operate_sendmail'
			);
			
		MInB_updatePaymentOptions($id,array('bookingMailSent' => 'sent'), false);
		return true;
		}
		else
		{
		MInB_log(
			$bookingId,
			'Ошибка отправки сообщения: '.$mail->ErrorInfo,
			'MInB_operate_sendmail'
			);
		return false;
		}
	
}
*/
function MInB_isBlockedPayment ($id)
{
	$payment = MInB_getPayment($id, true, array());

	if ( $payment === false ) return false;
	
	if ($payment['params']['status'] != 0)  return true;
		
	else
	{
		$answer = MInB_getPaymentStatus($id);
	
		if ((isset($answer['twpg_answer']['RESULT']) && ($answer['twpg_answer']['RESULT'] == 0))
			|| $answer['params']['status']==1 || $answer['params']['status']==2) {
			// При повторном запросе на подтверждение не дергать TWPG
			if ($answer['params']['status']==0 &&  strcasecmp($answer['twpg_answer']['RC'],'00') == 0) {
				MInB_approvePayment($id);
				//MInB_operate_sendmail($id);
				return true;
			}
			
		}
	}
	
	return false;
}

//**************************************************
// backend страница проверки статуса просроченных платежей
function MInB_booking_control_payments()
{
	// Подключим функционал коробочного решения
	MInB_booking_boxInit();
	$db = MInB_Database::Instance();
	
	// Получим платеж
	$stmt = $db->query('
		SELECT
			`payment_id`
		FROM
			`minbank_payments`
		WHERE
			`status`=0
			AND
			`checkedDate` < DATE_SUB(NOW(), INTERVAL 10 MINUTE)
			AND
			`checkedDate` IS NOT NULL
		ORDER BY RAND()
		LIMIT 1
		');
		
	if ( $stmt->rowCount() > 0 )
	{
		$row = $stmt->fetch();
		// Получим ответ
		$answer=MInB_getPaymentStatus($row['payment_id']);
		
		if ((isset($answer['twpg_answer']['RESULT']) && ($answer['twpg_answer']['RESULT'] == 0))
			|| $answer['params']['status']==1 || $answer['params']['status']==2) {
			// При повторном запросе на подтверждение не дергать TWPG
			if ($answer['params']['status']==0 &&  strcasecmp($answer['twpg_answer']['RC'],'00') == 0) {
				MInB_approvePayment($id);
				//MInB_operate_sendmail($id);
			}
			
		}
		else {
			$db->query('
				UPDATE
					`minbank_payments`
				SET
					`checkedDate` = NOW()
				WHERE
					`payment_id`=:payment_id
			', array(':payment_id' => $row['payment_id']));
			
			}
	}
		
}

