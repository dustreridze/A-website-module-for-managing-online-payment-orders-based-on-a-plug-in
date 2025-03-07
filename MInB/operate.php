<?php
// Параметры БД
define('DB_HOST', 'localhost');
define('DB_NAME', 'u2921074_konteineroff');
define('DB_USER', 'u2921074_root');
define('DB_PASSWORD', '');

// Логгирование ошибок
set_error_handler('err_handler');

function err_handler($errno, $errmsg, $filename, $linenum)
{
}

// Часовой пояс - Европа/Москва
date_default_timezone_set( 'Etc/GMT-3' );

// Определим путь до директории плагина
define('MINB_PATH', str_replace('\\', '/', DIRNAME(__FILE__)) . '/');

// Параметры мерчанта
define('MINB_MERCHANTID', 'E0410082');

define('MINB_CERT', MINB_PATH . 'protected/pems/E0410082_158089.pem');
define('MINB_KEY', MINB_PATH . 'protected/pems/E0410082_key.pem');
define('MINB_ROOTCA', MINB_PATH . 'protected/pems/Root_CA.pem');

define ('URL_MSG','http://konteineroff.ru/index.php?option=com_virtuemart&view=pluginresponse&task=pluginnotification&tmpl=component');
define ('URL_OUT','http://konteineroff.ru/index.php?option=com_virtuemart&view=pluginresponse&task=pluginresponsereceived');
define ('URL_CAN','http://konteineroff.ru/index.php?option=com_virtuemart&view=pluginresponse&task=pluginuserpaymentcancel');

// Тип сервера по-умолчанию
define('MINB_SERVERTYPE', 'PROD');

// Предавторизация по-умолчанию
define('MINB_PREAUTH', 'OFF');

// Временный каталог
define('TMP_DIR', str_replace('\\', '/', DIRNAME(__FILE__)) . '/../tmp/');

// Адреса отправки отчета
$mail_to = array('linkmail@ya.ru');

// проверка статуса просроченных платежей (по 1 за проход)
try {
MInB_operate_control_payments();
}
catch (MInB_Exception $e) {
}

// Инициализация коробочного решения
function MInB_operate_boxInit()
{
	// Подключим функционал коробочного решения
	require_once MINB_PATH . 'protected/library/MInB.php';
	
	try {
	// Настроим коробочное решение, БД
	MInB_Database::Instance('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME, DB_USER, DB_PASSWORD);
	}
	catch (Exception $e) {
	echo "У нас возникли временные трудности... Попробуйте повторить чуть позже...";  die();
	}
	
	// Определим, какой сервер использовать
	$server  = ( MINB_SERVERTYPE == 'TEST' ) ? 'https://mpit.minbank.ru:5443/Exec' : 'https://mpi.minbank.ru:5443/Exec';
	
	MInB_TWPG::Instance()->client()->setServer($server);
}

//Функции криптозащиты
function encrypt($text,$cryptkey)
	{
	$iv=str_repeat('#',mcrypt_get_iv_size (MCRYPT_3DES, MCRYPT_MODE_CBC));
    $crypttext = base64_encode(mcrypt_encrypt (MCRYPT_3DES, $cryptkey, $text, MCRYPT_MODE_CBC, $iv));
	return $crypttext;
	}

function decrypt($crypttext,$cryptkey)
	{
	$iv=str_repeat('#',mcrypt_get_iv_size (MCRYPT_3DES, MCRYPT_MODE_CBC));
	$text = mcrypt_decrypt (MCRYPT_3DES, $cryptkey, base64_decode ($crypttext), MCRYPT_MODE_CBC, $iv);
    return str_replace(chr(0),'',$text);
	}
	
// frontend страница
function MInB_operate_createmsg($id=null,$ORDER_STATUS=null)
{
	if ($id==null || $ORDER_STATUS==null) return (false);

	//Собираем строку ответа STATUS=STATUS&SUMMA=SUMMA&DATE=DATE&ORDER_ID=ORDER_ID&SESSION_ID=SESSION_ID&PAY_ID=PAY_ID
	$payment = MInB_getPayment($id, true, array('PAY_ID','SUMMA'));
	$STATUS='STATUS='.$ORDER_STATUS.
			'&SUMMA='.$payment['options']['SUMMA'].
			'&DATE='.$payment['params']['paidDate'].
			'&ORDER_ID='.$payment['params']['order_id'].
			'&SESSION_ID='.$payment['params']['session_id'].
			'&PAY_ID='.$payment['options']['PAY_ID'];
			
			$key = 'cSG6fMAMGQUYYEcqhI3MreBb';
			$STATUS_ENC=urlencode(encrypt($STATUS,$key));
			//$STATUS=decrypt($STATUS_ENC,$key);
			
	return ($STATUS_ENC);
}
	
// frontend страница
function MInB_operate_sendmsg($id=null,$ORDER_STATUS=null)
{
	if ($id==null || $ORDER_STATUS==null) return (false);

	//Собираем строку ответа STATUS=STATUS&SUMMA=SUMMA&DATE=DATE&ORDER_ID=ORDER_ID&SESSION_ID=SESSION_ID&PAY_ID=PAY_ID
	$payment = MInB_getPayment($id, true, array('PAY_ID','SUMMA'));
	$STATUS='STATUS='.$ORDER_STATUS.
			'&SUMMA='.$payment['options']['SUMMA'].
			'&DATE='.$payment['params']['paidDate'].
			'&ORDER_ID='.$payment['params']['order_id'].
			'&SESSION_ID='.$payment['params']['session_id'].
			'&PAY_ID='.$payment['options']['PAY_ID'];
			
			//echo 'id = '.$id.'<br />';
			//echo 'Сообщение в ЛК отправлено ==> '.$STATUS.'<br />';
			
			$key = 'cSG6fMAMGQUYYEcqhI3MreBb';
			$STATUS_ENC=encrypt($STATUS,$key);
			$STATUS=decrypt($STATUS_ENC,$key);
			
			//$private_key=openssl_pkey_get_private ('file://'.MINB_KEY);
			//openssl_private_encrypt ( $STATUS, $STATUS_ENC, $private_key );

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, URL_MSG);
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_POSTFIELDS, array('STATUS' => $STATUS_ENC));
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);
			curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);
			$answer = curl_exec($ch);	
			$error  = curl_error($ch);
			curl_close($ch);

			/*echo '<pre>***'.$STATUS_ENC.'***</pre>';
			echo '<pre>***'.$STATUS.'***</pre>';
			$public_key=openssl_pkey_get_public ('file://'.MINB_CERT);
			openssl_public_decrypt ( $STATUS ,$STATUS , $public_key );
			echo $STATUS;*/

			if (empty($error))
				{
				MInB_updatePaymentParams($id, array ('sended' => 1));
				MInB_log(
					$id,
					'Сообщение в ЛК отправлено ==> '.$STATUS,
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

// frontend страница
function MInB_operate_frontend()
{
	// Подключим функционал коробочного решения
	MInB_operate_boxInit();
	$db = MInB_Database::Instance();

	// Если пришел id
	$id   = MInB_get('id');
	
	// Если пришел oid (order_id) для чека
	$oid   = MInB_get('oid');

	if ($oid!==false)
		{
		$stmt = $db->query('
		SELECT
			`payment_id`
		FROM
			`minbank_payments`
		WHERE
			`order_id`=:oid
		', array(':oid' => $oid));
		
		if ( $stmt->rowCount() == 1 )
			{
			$row = $stmt->fetch();

			$id = $row['payment_id'];
			}
			
		}

	// Проверяем, возможно подтверждение
	if ( $id!== false && MInB_existsPayment($id) )
		{
		// Получим ответ
		$answer=MInB_getPaymentStatus($id);

		if (isset($answer['twpg_answer']['OrderStatus']))
			{
			if ($answer['twpg_answer']['OrderStatus'] == 'APPROVED' || $answer['twpg_answer']['OrderStatus'] == 'PREAUTH-APPROVED')
				{
				// Подтверждаем платеж (дублирование запроса статуса платежа)
				// При повторном запросе на подтверждение не дергать TWPG
				if ($answer['params']['status']==0) MInB_approvePayment($id);
				}		

			
			// Можно было только по APPROVED посылать тогда по остальным через час только придет в ЛК а так по всем подряд
			// но в ЛК по отклоненным 2 раза пошлется один раз здесь второй в контроле
			// Посылаем данные в ЛК
			MInB_operate_sendmsg($id, $answer['twpg_answer']['OrderStatus']);
				
			$payment = MInB_getPayment($id, true, array('PAY_ID','SUMMA'));
			
			$STAT_MSG = MInB_operate_createmsg($id,$answer['twpg_answer']['OrderStatus']);
			// Отобразим шаблон
			require MINB_PATH . 'protected/templates/frontend_result.html';

			//<script>
			//window.onload=function(){document.go.submit()}
			//</script>
			}
		
		}
		else
		{
		// Получим данные из ЛК
		$SUMMA   = MInB_get('SUMMA');
		$PAY_ID   = MInB_get('PAY_ID');
        // Указываем путь к файлу
        $file = 'data.txt';

        // Создаем строку, которую будем записывать в файл
        $data = "SUMMA: $SUMMA\nPAY_ID: $PAY_ID\n";
        
        // Записываем данные в файл
        file_put_contents($file, $data, FILE_APPEND); 
		try {
			if ($SUMMA===false || $PAY_ID===false)
				throw new Exception('Неполный набор входных данных');
			if (!preg_match("/^[0-9]+\.[0-9]{2}$/", $SUMMA))
				throw new Exception('Ошибка формата данных в поле SUMMA');
			if ($SUMMA<0)
				throw new Exception('Значение поля SUMMA отрицательное');
			if ($SUMMA==0)
				throw new Exception('Значение поля SUMMA равно 0');		
			if (!preg_match("/^[0-9A-Za-z]{1,32}$/", $PAY_ID))
				throw new Exception('Ошибка формата данных в поле PAY_ID');		

			// Генерируем уникальный id платежа
			do
			$id=uniqid($PAY_ID.'-');
			while (MInB_existsPayment($id) && MInB_existsArchivePayment($id));
			
			MInB_createPayment($SUMMA, $id);
				
			MInB_updatePaymentOptions($id, array (
				'SUMMA' => $SUMMA,
				'PAY_ID' => $PAY_ID
				));
				
			// URL
			$url = "https://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?id=' . $id;
			
			// Процессируем платеж
			$processed = MInB_processPayment($id, array(
				'description' => 'Оплата по заказу № '.$PAY_ID,
				'orderType'   => (MINB_PREAUTH == 'ON' ) ? 'PreAuth' : 'Purchase',
				'approveUrl'  => $url,
				'declineUrl'  => $url,
				'cancelUrl'   => $url
				));	

		} 
		catch (MInB_Exception $e) {
			echo '<p>Извините, но в даный момент нет связи с банком. Попробуйте обновить страницу (кнопка F5) через некоторое время. <br /> Мы работаем над исключением подобных ситуаций. Спасибо за понимание.</p>';
		}		
		catch (Exception $e) {
			echo '<br /><p>ОШИБКА: ',  $e->getMessage(), "</p>";
			//echo '<p><button type="button" class="button" onClick="history.go(-1)">Назад...</button></p>';
		}	
			
		// Отобразим шаблон
		require MINB_PATH . 'protected/templates/frontend.html';	
		}

}

// backend страница журнала
function MInB_operate_logs()
{
	// Подключим функционал коробочного решения
	MInB_operate_boxInit();
	
	// Настроим лимит записей
	$limit = ( MInB_post('limit', 100) === '0' ) ? false : MInB_post('limit', 100);
	
	// Проверим, возможно установлен штрих-код
	$shtrKod = MInB_post('shtrKod', 0);
	
	if ( $shtrKod != 0 )
	{
		$logs = MInB_getPaymentLogs($shtrKod, $limit);
	}
	else
		// Получим логи по всем операциям
		$logs = MInB_getPaymentsLogs($limit);
	
	// Отобразим шаблон
	require MINB_PATH . 'protected/templates/backend_logs.html';
}

// backend страница отчета по платежам
function MInB_operate_payments()
{
	MInB_operate_pop3(false);
	
	// Подключим функционал коробочного решения
	MInB_operate_boxInit();

	// ПОЛУЧИМ КОЛИЧЕСТВО ПЛАТЕЖЕЙ ПО УСЛОВИЮ
	$paymentsCount = MInB_getPayments(false, false, false, false, 1);	

	$pages=ceil($paymentsCount/COUNT_ON_PAGES);

	if ($pages==0) $pages=1;
	$cpg=(is_numeric(MInB_post('cpg'))!==false && MInB_post('cpg')<=$pages && MInB_post('cpg')>=1)? MInB_post('cpg'):$pages;
	
	$first=($cpg-1)*COUNT_ON_PAGES;
	$size=COUNT_ON_PAGES;
	
	// Получим платежи
	$payments = MInB_getPayments(true, array('procDate','procPP','PAY_ID','SUMMA'), $first, $size, 1);
	
	// Отобразим шаблон
	require MINB_PATH . 'protected/templates/backend_payments.html';	
}

// backend страница отчета по отклоненным платежам
function MInB_operate_nopayments()
{
	// Подключим функционал коробочного решения
	MInB_operate_boxInit();
	$db = MInB_Database::Instance();

	// Переделанный запрос из MInB_getPayments
	$result = array();

	$stmt = $db->query('
		SELECT
			`payment_id`,
			`createdDate`,
			`startAmount`,
			`order_id`,
			`session_id`,
			`ip`,
			`OrderStatus`
		FROM
			`minbank_payments_archive`
	');
	
	if ( $stmt->rowCount() > 0 )
		while ( $row = $stmt->fetch() )
			$result[$row['payment_id']]['params'] = $row;

	// Разделено в расчете на оформление в виде библиотечной функции
	$payments=$result;
		
	// Отобразим шаблон
	require MINB_PATH . 'protected/templates/backend_nopayments.html';	
}

// backend страница отчета по платежам
function MInB_operate_mail()
{
	MInB_operate_pop3(false);
	
	global $mail_to;
	
	// Подключим функционал коробочного решения
	MInB_operate_boxInit();
	
	// Подготовим временную папку
	$files = glob(TMP_DIR.'*');
	if (count($files) > 0)
		foreach ($files as $file)  
			if (file_exists($file))
				{unlink($file);}		   
	
	// Получим платежи
	$payments = MInB_getPayments(true, array('procDate','procPP','PAY_ID','SUMMA'));
	
	$reestr_date=MInB_post('reestr_date', date("Y-n-j",mktime(0, 0, 0, date("m")  , date("d")-2, date("Y"))));

	if (MInB_post('reestr_date')!==false || ($_SERVER["PHP_SELF"]=='/protected/mail.php')):
	
		$db = MInB_Database::Instance();
		
		$nl = chr(13).chr(10);

		//Создаем файл
		$filename='r01'.date('dmY',strtotime($reestr_date)).'.txt';
		$fp = fopen(TMP_DIR.$filename, 'w');

		// [<ФИО>];[<город>,<улица>,<дом>[,<кв>]];<счет>;<сумма>;<Услуга>;<Дата начала расчетного периода>;< Дата конца расчетного периода>;< Дополнение>;<Дата оплаты>;<Остаток>;<Номер в системе>		

		$date_nrp=date("d/m/Y",mktime(0, 0, 0, date("m",strtotime($reestr_date))  , 1 , date("Y", strtotime($reestr_date))));
		$date_krp=date("d/m/Y",mktime(0, 0, 0, date("m",strtotime($reestr_date))+1  , 0 , date("Y", strtotime($reestr_date))));

		foreach ($payments as $reestr)
			{
			if (($reestr['params']['status'] != 1 && $reestr['params']['status'] != 6) ||
			strtotime(substr($reestr['params']['paidDate'],0,10))!=strtotime($reestr_date))
				continue;
			
			fwrite($fp,
				';;'.
				$reestr['options']['SUMMA'].';'.
				$reestr['options']['PAY_ID'].';'.
				date('d.m.Y',strtotime(substr($reestr['params']['paidDate'],0,10))).';'.
				$reestr['options']['procDate'].' '.$reestr['options']['procPP'].';'.
				$nl);
			}
			
		fclose($fp);
			
		// ПОЧТА
		date_default_timezone_set('Etc/UTC');

		require 'PHPMailer/PHPMailerAutoload.php';
		
		$mail = new PHPMailer();
		$mail->isSMTP();
		$mail->SMTPDebug = 0; // 0 = off (for production use); 1 = client messages ;2 = client and server messages
		$mail->Debugoutput = 'html';
		$mail->CharSet = "UTF-8";
		$mail->Host = "mail.pay-ok.org";
		$mail->Port = 25;
		$mail->SMTPAuth = true;
		$mail->Username = "nobody@pay-ok.org";  
		$mail->Password = "";
		$mail->setFrom('nobody@pay-ok.org', 'Платежная система магазина konteineroff');
		$mail->addReplyTo('nobody@pay-ok.org', 'Платежная система магазина konteineroff');
		foreach ($mail_to as $addr) $mail->addAddress($addr);
		$mail->Subject = "Отчет о произведенных платежах за ".$reestr_date;
		$mail->msgHTML(' ');
		$mail->AltBody = ' ';
		$mail->addAttachment(TMP_DIR.$filename);

		//send the message, check for errors
		if ($mail->send())
			$reestr_create=true;
			else
			$reestr_create=false;
	endif;
	
	// Отобразим шаблон
	if ($_SERVER["PHP_SELF"]!='/protected/mail.php')
		require MINB_PATH . 'protected/templates/backend_mail.html';
}

//**************************************************
// backend страница проверки статуса просроченных платежей
function MInB_operate_control_payments()
{
	// Подключим функционал коробочного решения
	MInB_operate_boxInit();
	$db = MInB_Database::Instance();
			
	// Получим платеж
	$stmt = $db->query('
		SELECT
			`payment_id`
		FROM
			`minbank_payments`
		WHERE
			(`status`=0 OR `sended`!=1)
			AND
			`checkedDate` < DATE_SUB(NOW(), INTERVAL 1 HOUR)
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

		if (isset($answer['twpg_answer']['OrderStatus']))
			{
			MInB_log(
				$row['payment_id'],
				'Проверка статуса платежа. Ответ:'.print_r ($answer['twpg_answer']['OrderStatus'],true),
				'MInB_operate_control_payments'
				);
				
			if ($answer['twpg_answer']['OrderStatus'] == 'APPROVED' || $answer['twpg_answer']['OrderStatus'] == 'PREAUTH-APPROVED')
				{
					// Подтверждаем платеж (повторный запрос статуса платежа)
					// При повторном запросе на подтверждение не дергать TWPG
					if ($answer['params']['status']==0) MInB_approvePayment($row['payment_id']);
					
					// Посылаем данные в ЛК
					MInB_operate_sendmsg($row['payment_id'], $answer['twpg_answer']['OrderStatus']);
				}
				else
				{
				if (($answer['twpg_answer']['OrderStatus'] == 'ERROR' || $answer['twpg_answer']['OrderStatus'] == 'EXPIRED' || $answer['twpg_answer']['OrderStatus'] == 'CANCELED' || $answer['twpg_answer']['OrderStatus'] == 'DECLINED')
					&& MInB_operate_sendmsg($row['payment_id'], $answer['twpg_answer']['OrderStatus']))					
					{
					// Перенесем просроченный платеж в архив только при успешной отправке данных в ЛК
					$db->query('
						INSERT INTO
							`minbank_payments_archive`
							(
							`payment_id`,
							`createdDate`,
							`startAmount`,
							`order_id`,
							`session_id`,
							`ip`,
							`OrderStatus`
							)
						VALUES 
							(
							:payment_id,
							:createdDate,
							:startAmount,
							:order_id,
							:session_id,
							:ip,
							:OrderStatus
							)
						',array(
							':payment_id' => $row['payment_id'],
							':createdDate' => $answer['params']['createdDate'],
							':startAmount' => $answer['params']['startAmount'],
							':order_id' => $answer['params']['order_id'],
							':session_id' => $answer['params']['session_id'],
							':ip' => $answer['params']['ip'],
							':OrderStatus' => $answer['twpg_answer']['OrderStatus']						
							));

					// Строку записали?
					$count = $db->query('
						SELECT ROW_COUNT()
						')->fetch();	
							
					if ($count['ROW_COUNT()']==1)
						{
						// Удалим просроченный платеж из базы
						$db->query('
							DELETE 
							FROM 
								`minbank_payments` 
							WHERE 
								`payment_id` = :payment_id
							LIMIT 1
							',array(':payment_id' => $row['payment_id']));

						MInB_log(
							$row['payment_id'],
							'Платеж со статусом '.$answer['twpg_answer']['OrderStatus'].' перенесен в архив',
							'MInB_operate_control_payments'
							);
						}
					}
					else
					{
					$db->query('
						UPDATE
							`minbank_payments`
						SET
							`checkedDate` = NOW()
						WHERE
							`payment_id`=:payment_id
					', array(':payment_id' => $row['payment_id']));
					
					MInB_log(
						$row['payment_id'],
						'Обновлено время последней проверки платежа со статусом '.$answer['twpg_answer']['OrderStatus'],
						'MInB_operate_control_payments'
						);
					}
				}
			}
			else
			{
			MInB_log(
				$row['payment_id'],
				'Проверка статуса платежа. Ответ TWPG не получен либо не содержит поле OrderStatus: '.print_r ($answer,true),
				'MInB_operate_control_payments'
				);
			}
		}
	
	// Получим непроцессированные платежи
	$stmt = $db->query('
		SELECT
			`payment_id`,
			`createdDate`,
			`startAmount`,
			`order_id`,
			`session_id`,			
			`ip`
		FROM
			`minbank_payments`
		WHERE
			`status`=0
			AND
			`createdDate` < DATE_SUB(NOW(), INTERVAL 1 HOUR)
			AND
			(`order_id` = ""
			OR
			`order_id` IS NULL
			OR
			`session_id` = ""
			OR
			`session_id` IS NULL)
		LIMIT 1
		');		
			
	if ( $stmt->rowCount() > 0)
		{
		$row = $stmt->fetch();
		
		if (MInB_operate_sendmsg($row['payment_id'], 'ERROR'))
			{
			MInB_log(
				$row['payment_id'],
				'Проверка статуса платежа. Платеж не был процессирован (отсутствуют ORDERID или SESSIONID)',
				'MInB_operate_control_payments'
				);
	
			// Удалим непроцессированный платеж
			$db->query('
				DELETE 
				FROM 
					`minbank_payments` 
				WHERE 
					`payment_id` = :payment_id
				LIMIT 1
				',array(':payment_id' => $row['payment_id']));

			MInB_log(
				$row['payment_id'],
				'Просроченный непроцессированный платеж удален',
				'MInB_operate_control_payments'
				);
			}
		}
		
}

//**************************************************
// backend страница загрузки писем с реестром
function MInB_operate_pop3($debug=false)
{
	// Подключим функционал коробочного решения
	MInB_operate_boxInit();
	$db = MInB_Database::Instance();
	
	require 'pop3/pop3.php';

	$pop3 = new wspPop3();
	$opn = $pop3->Open('mail.ilsn.ru', 'payeric33@ilsn.ru', 'Breeze76');
	if ($opn)
		{
		$mls = array();
		$pop3->Lst($mls);
		
		$okmail=0;
		echo 'Писем в почтовом ящике: '.count($mls).'<br />'; 
		foreach($mls as $msgkey => $val)
			{
			$head = null; $text = null;
			if($pop3->GetMail($msgkey, $head, $text, false))
				{
				//print_r ($head);
				//print_r ($text);
				 
				$nl = chr(13).chr(10); 
				$shead=implode($nl,$head);
				$stext=implode($nl,$text);
				
				if (preg_match("/boundary\s*=\s*\"(.*?)\"/i", $shead, $match))
					$boundary=$match[1];

				if (preg_match("/message-id\s*:\s*\<(.*?)\>/i", $shead, $match))
					$msgid=$match[1];
		
				if ($debug)  echo '<br />Обрабатываем письмо с ID='.$msgid.'<br />';

				$mailarr=explode('--'.$boundary,$stext);

				// Распознаем все части, содержащие вложения
				$fileatt=null;
				foreach($mailarr as $val)
					if (preg_match("/Content-Disposition\s*:\s*attachment/i", $val) || preg_match("/Content-Type\s*:\s*application\/octet-stream\s*;/i", $val))
						{
						if (preg_match("/Content-Disposition\s*:\s*attachment/i", $val))	
							if (!preg_match('/filename\s*=\s*\"(.*?)\"/i',$val,$match) && !preg_match('/filename\s*=\s*(.*?)\s*;/i',$val,$match))
								{
								if ($debug)  echo 'Не распознано поле имени вложения'.'<br />';	
								continue;
								}
								
						if (preg_match("/Content-Type\s*:\s*application\/octet-stream\s*;/i", $val))	
							if (!preg_match('/name\s*=\s*\"(.*?)\"/i',$val,$match) && !preg_match('/name\s*=\s*(.*?)\s*;/i',$val,$match))
								{
								if ($debug)  echo 'Не распознано поле имени вложения'.'<br />';	
								continue;
								}	

						$filename=$match[1];
							
						if (!preg_match("/Content-Transfer-Encoding\s*:\s*(.*)\s*/i", $val,$match))
							{
							if ($debug)  echo 'Не распознано поле кодировки вложения'.'<br />';	
							continue;
							}
							
						$filecode=trim($match[1]);							
						
						if ($debug)  echo 'Найдено вложение с именем: '.$filename.' Кодировка вложения: '.$filecode.'<br />';
						
						if (!preg_match('/'.$nl.$nl.'([\S\s]*)/i',$val,$match)) // Исправлено \S|\s на \S\s, проверить
							{
							if ($debug)  echo 'Не найдено тело вложения'.'<br />';		
							continue;
							}

						switch ($filecode)
							{
							case 'base64': $fileatt[$filename]=base64_decode($match[1]); break;
							case '7bit': $fileatt[$filename]=$match[1]; break;
							default : if ($debug)  echo 'Кодировка вложения не распознана: '.$filecode.'<br />';
							}
						}

				// можно проверить на наличие вложений

				if ($debug) echo 'Количество вложений '.count($fileatt).'<br />';
				
				if (count($fileatt)==0)
					{
					if ($debug) echo 'Обработка письма с ID='.$msgid.' прекращена<br />';
					continue;
					}
					
				// Количество успешно обработанных вложений
				$okinc=0; 
				
				// Обработаем массив вложений
				foreach ($fileatt as $fname => $fval)
					{
					if ($debug) echo '&nbsp;&nbsp;&nbsp;Проверяем вложение с именем '.$fname.'<br />';
					
					// Проверка filename на допустимость
					$arr=explode ('_',$fname); // Парсим filename
					if (trim($arr[0])!=MINB_MERCHANTID) 
						{
						if ($debug) echo '&nbsp;&nbsp;&nbsp;Имя вложения не содержит MerchantID соответствующего данному сервису<br />';
						continue;
						}

					if (trim($arr[3])!='MINB.txt') 
						{
						if ($debug) echo '&nbsp;&nbsp;&nbsp;Имя вложения не содержит сигнатуры "MINB.txt"'.'<br />';
						continue;
						}
						
					if (strlen(trim($arr[1]))!=8)
						{
						if ($debug) echo '&nbsp;&nbsp;&nbsp;Имя вложения не содержит дату платежа в установленном формате<br />';
						continue;
						}
						else
						{
						$arr[1]=trim($arr[1]);
						if (checkdate (substr($arr[1],4,2), substr($arr[1],6,2), substr($arr[1],0,4)))
							{
							$dplat=substr(trim($arr[1]),0,4).'-'.substr(trim($arr[1]),4,2).'-'.substr(trim($arr[1]),6,2);
							}
							else
							{
							if ((int)substr($arr[1],2,2)<=13 && (checkdate ( substr($arr[1],2,2),substr($arr[1],0,2), substr($arr[1],4,4))))
								{
								$dplat=substr(trim($arr[1]),4,4).'-'.substr(trim($arr[1]),2,2).'-'.substr(trim($arr[1]),0,2);
								if ($debug) echo '&nbsp;&nbsp;&nbsp;Поле "Дата платежа" в имени вложения перевернуто. Разворачиваем<br />';
								}
								else
								{
								if ($debug) echo '&nbsp;&nbsp;&nbsp;Неправильный формат даты в имени вложения<br />';
								continue;
								}
							}
						}

					// Номер п/п
					$nplat=trim($arr[2]);

					if ($debug) echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Обрабатываем содержимое вложения<br />';
					
					// Парсим вложение
					$arr=explode ($nl,$fval); // Парсим csv
					
					// Текущая строка и количество успешно обработанных строк
					$numstr=$okstr=0;

					foreach ($arr as $csvstr)
						{
						$numstr++;
						
						if ($csvstr=='')
							{
							if ($debug) echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Строка '.$numstr.' пустая<br />';
							$okstr++;
							continue;
							}
						
						$strarr=explode (';',$csvstr);
						
						$strarr[2]=trim($strarr[2]);
						// Если прислали реестр с датой в формате ДДММГГГГ разворачиваем
						if ((int)substr($strarr[2],2,2)<=13 && (checkdate ( substr($strarr[2],2,2),substr($strarr[2],0,2), substr($strarr[2],4,4))))
								{
								$strarr[2]=substr($strarr[2],4,4).substr($strarr[2],2,2).substr($strarr[2],0,2);
								if ($debug) echo '&nbsp;&nbsp;&nbsp;Поле "Дата платежа" перевернуто. Разворачиваем<br />';
								}
														
						// Получим платежи по orderId
						$stmt = $db->query('
							SELECT
								`payment_id`,
								`paidDate`,
								`paidAmount`
							FROM
								`minbank_payments`
							WHERE
								`order_id`=:order_id AND
								DATE_FORMAT(`paidDate`,"%Y%m%d")=:payDate AND
								`paidAmount`=:amount
							', array(':order_id' => trim($strarr[0]),':payDate' => $strarr[2],':amount' => round($strarr[1]*100))
							);
						
						if ( $stmt->rowCount() == 0 )
							{
							if ($debug) echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Не найден платеж для строки '.$numstr.' ('.$csvstr.')<br />';
							continue;
							}

						if ( $stmt->rowCount() > 1 )
							{
							if ($debug) echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Неоднозначность определения платежа для строки '.$numstr.' ('.$csvstr.')<br />';
							continue;
							}
							
						$row = $stmt->fetch();
						
						MInB_updatePaymentOptions($row['payment_id'],array('procDate'=>$dplat,'procPP'=>$nplat), false);
						$okstr++;
						if ($debug) echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Обработан платеж для строки '.$numstr.'<br />';
						}
						
					if ($numstr!=$okstr)
						{
						if ($debug) echo '&nbsp;&nbsp;&nbsp;Обработано строк: '.$okstr.' из '.$numstr.'<br />';
						if ($debug) echo '&nbsp;&nbsp;&nbsp;Вложение с именем '.$fname.' обработано не полностью<br />';
						}
						else
						{
						if ($debug) echo '&nbsp;&nbsp;&nbsp;Вложение с именем '.$fname.' обработано<br />';
						$okinc++;
						}
					}
				
				if (count($fileatt)!=$okinc)
					{
					if ($debug) echo 'Письмо с ID='.$msgid.' обработано не полностью<br />';
					}
					else
					{
					$okmail++;
					if ($debug) echo 'Письмо с ID='.$msgid.' обработано<br />';
					if ($pop3->DelMail($msgkey, true))
						if ($debug) echo 'Письмо с ID='.$msgid.' удалено из почтового ящика<br />';
						else
						if ($debug) echo 'Не могу удалить письмо с ID='.$msgid.' из почтового ящика<br />';
						
					}
				
				}
			if ($debug) echo $pop3->Error.'<br />';
			}
		echo 'Из них обработано писем: '.$okmail.'<br /><br />';	
		}

	$pop3->Close();
	unset($pop3);
}