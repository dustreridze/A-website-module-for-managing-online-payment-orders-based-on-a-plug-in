<?php
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
define('DB_USER', 'u2921074_lager');
define('DB_PASSWORD', 'lager123');

// Определим путь до директории плагина
define('MINB_BOOKING_PATH', str_replace('\\', '/', DIRNAME(__FILE__)) . '/');

// Определим путь до корневой директории модуля
define('BASE_PATH', str_replace('\\', '/', DIRNAME(__FILE__)) . '/../');

// ID магазина по-умолчанию
define('MINB_BOOKING_MERCHANTID', '79036777');
define('PSB_TERMINAL_1', '79036777');
define('PSB_MERCHANT_1', false);
define('PSB_MERCH_NAME_1', false);
define('PSB_COMP_1_1', 'C50E41160302E0F5D6D59F1AA3925C45');
define('PSB_COMP_2_1', '00000000000000000000000000000000');
// Тип сервера по-умолчанию
define('MINB_BOOKING_SERVERTYPE', 'TEST');

// Предавторизация по-умолчанию
define('MINB_BOOKING_PREAUTH', 'OFF');

// Проверка статусов созданных платежей
//MInB_booking_control_payments();

// Адреса отправки отчета
//$mail_to = array('linkmail@ya.ru', 'office@omist-33.ru');

// Инициализируем всплывающие окна
?><script>	
$(document).ready(function(){
	$('#dialog').jqm();	
}); 	
</script><?
	
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
	$server  = ( MINB_BOOKING_SERVERTYPE == 'TEST' ) ? 'https://mpit.minbank.ru:5443/Exec' : 'https://mpi.minbank.ru:5443/Exec';
	
	// Настроим соединение с платежной системой
	//MInB_TWPG::Instance()->client()->setServer($server)->setSSL(MINB_BOOKING_PATH . 'protected/pems/E0410091_158213.pem', MINB_BOOKING_PATH . 'protected/pems/E0410091_key.pem', false, MINB_BOOKING_PATH . 'protected/pems/Root_CA.pem');
	MInB_TWPG::Instance()->client()->setServer($server);
	
	// Настроим параметры магазина
	MInB_TWPG::Instance()->payment();//->setMerchantID(MINB_BOOKING_MERCHANTID);	
}

// backend страница управление заказами
function MInB_booking_payments()
{
	// Подключим функционал коробочного решения
	MInB_booking_boxInit();
	
	MInB_booking_pop3(false);
	
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
					'bookingTelefon',
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
				if ($protcontr==$protres)			
				if ( MInB_existsPayment($bookingId, 1) || MInB_existsPayment($bookingId, 6) )
					if ( MInB_reversePayment($bookingId, MInB_post('bookingAmount', $payment['params']['paidAmount'] / 100)) )
						$updated = true;
						
				// Обновим данные
				$payment = MInB_getPayment($bookingId, true, array(
					'bookingWord',
					'bookingName',
					'bookingDate',
					'bookingTime',
					'bookingTelefon',
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
					'bookingTelefon',
					'bookingRoom',
					'bookingDescription'
				));
				
				// Отобразим шаблон
				require MINB_BOOKING_PATH . 'protected/templates/backend_payment.html';
			break;
		
			// Удаление заказа
			case 'delete':
				if ( MInB_existsPayment($bookingId, 0) )
					if ( !MInB_isBlockedPayment ($bookingId) ) // ************* ПАТЧ *************
						if ( MInB_deletePayment($bookingId) )
							$updated = true;
				
				// Обновим данные
				$payment = MInB_getPayment($bookingId, true, array(
					'bookingWord',
					'bookingName',
					'bookingDate',
					'bookingTime',
					'bookingTelefon',
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
					$bookingTelefon = MInB_post('bookingTelefon', $payment['options']['bookingTelefon']);
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
							'bookingTelefon' => $bookingTelefon,
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
					'bookingTelefon',
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
					'bookingTime'     => MInB_post('bookingTime'),
					'bookingTelefon'     => MInB_post('bookingTelefon'),
					'bookingRoom'     => MInB_post('bookingRoom'),
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
		
		$dateBegin = MInB_post('dateBegin', '01.01.2015');
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
			'bookingTelefon',
			'bookingRoom',
			'bookingDescription',
			'procDate',
			'procPP'
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
	if ( $bookingGetId !== false && MInB_existsPayment($bookingGetId) )
		{
		// Получим ответ
		$answer=MInB_getPaymentStatus($bookingGetId);

		if ((isset($answer['twpg_answer']['OrderStatus']) && ($answer['twpg_answer']['OrderStatus'] == 'APPROVED' || $answer['twpg_answer']['OrderStatus'] == 'PREAUTH-APPROVED'))
			|| $answer['params']['status']==1 || $answer['params']['status']==6)
			{
			// При повторном запросе на подтверждение не дергать TWPG
			if ($answer['params']['status']==0) 
				{
				MInB_approvePayment($bookingGetId);
				MInB_operate_sendmail($bookingGetId);
				}
			}
		// Отобразим шаблон
		require MINB_BOOKING_PATH . 'protected/templates/frontend_result.html';	
		}
		else
		{
		// Проверяем, пришли ли данные
		if ( $bookingId !== false )
			{
			// Получаем платеж
			$payment = MInB_getPayment($bookingId, true, array(
				'bookingWord',
				'bookingName',
				'bookingDate',
				'bookingTime',
				'bookingTelefon',
				'bookingRoom',
				'bookingDescription',
			));

			// Проверяем платеж
			if ( is_array($payment) && $payment['params']['status'] == 0 )
				{
				$description = 'Оплата заказа №' . $payment['options']['bookingWord'];
			
				// Проверяем, пересылать на оплату или нет
				if ( MInB_post('action') == 'pay' )
					{
					$answer=MInB_getPaymentStatus($bookingId);
					if (isset($answer['twpg_answer']['OrderStatus']) && ($answer['twpg_answer']['OrderStatus'] == 'APPROVED' || $answer['twpg_answer']['OrderStatus'] == 'PREAUTH-APPROVED'))
						{
						// Отбивка попытки заплатить повторно при невозврате на сайт
						MInB_approvePayment($bookingId);
						MInB_operate_sendmail($bookingId);
						$errors['paymentAlreadyProcessed'] = true;
						}					
						else
						{
						if (isset($answer['twpg_answer']['OrderStatus']) && ($answer['twpg_answer']['OrderStatus'] == 'ERROR' || $answer['twpg_answer']['OrderStatus'] == 'EXPIRED' || $answer['twpg_answer']['OrderStatus'] == 'CANCELED' || $answer['twpg_answer']['OrderStatus'] == 'DECLINED')
						|| ( isset($answer['params']) && empty($answer['params']['order_id']) && empty($answer['params']['session_id']) ))
							// если финитный статус... или платеж не был процессирован... тогда можно процессировать платеж
							{
							// URL
							$url = "https://".$_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'].'?bookingWord=' . MInB_post('bookingWord').'&createdDate='.$answer['params']['createdDate'];
						
							// Процессируем платеж
							$booking = MInB_processPayment($bookingId, array(
								'description' => $description,
								'orderType'   => (MINB_BOOKING_PREAUTH == 'ON' ) ? 'PreAuth' : 'Purchase',
								'approveUrl'  => $url,
								'declineUrl'  => $url,
								'cancelUrl'   => $url
							));							
							}
							else
							{
							if (isset($answer['twpg_answer']['OrderStatus']) && $answer['twpg_answer']['OrderStatus'] == 'CREATED')
								{
								// если был создан ранее и по какой то причине прерван (CREATED)
								$booking['payment_url']='https://mpi.minbank.ru/index.jsp?ORDERID='.$answer['params']['order_id'].'&SESSIONID='.$answer['params']['session_id'];
												
								MInB_log(
										$bookingId,
										'Статус платежа: CREATED. Производится оплата с использованием полученных ранее параметров: ORDERID='.$answer['params']['order_id'].'&SESSIONID= '.$answer['params']['session_id'],
										'MInB_booking_frontend'
									);
								}
								else
								{
								// если нефинитный статус или нет ответа TWPG
								$errors['paymentTemporaryUndefined'] = true;
													
								if (!isset($answer['twpg_answer']['OrderStatus'])) $answer['twpg_answer']['OrderStatus']='UNDEFINED';
														
								MInB_log(
									$bookingId,
									'Предыдущий платеж не завершен. Статус: '.$answer['twpg_answer']['OrderStatus'],
									'MInB_booking_frontend'
									);
								}
							}
						}						
					}
					else
					{	
					// Флаг, найдена заказ
					$founded = true;
					}
				}
				else
				{
				if ( is_array($payment) && ($payment['params']['status'] == 1 || $payment['params']['status'] == 5 || $payment['params']['status'] == 6))
					{
					// Если уже оплачен
					$errors['paymentAlreadyProcessed'] = true;
					}
					else
					{
					// Если уже удален/возвращен или др.
					$errors['paymentNotExists'] = true;
					}
				}
			}
			else
			{
			// Платеж не найден или секретное слово неверно
			if (MInB_post('bookingWord')!='') $errors['paymentNotExists'] = true;
			}
		
		// Отобразим шаблон
		require MINB_BOOKING_PATH . 'protected/templates/frontend.html';
		}
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
				'MInB_booking_control_payments'
				);
				
			if ($answer['twpg_answer']['OrderStatus'] == 'APPROVED' || $answer['twpg_answer']['OrderStatus'] == 'PREAUTH-APPROVED')
				{
				// Подтверждаем платеж (повторный запрос статуса платежа)
				MInB_approvePayment($row['payment_id']);
				MInB_operate_sendmail($row['payment_id']);
				}
				else
				{
				if ($answer['twpg_answer']['OrderStatus'] == 'ERROR' || $answer['twpg_answer']['OrderStatus'] == 'EXPIRED' || $answer['twpg_answer']['OrderStatus'] == 'CANCELED' || $answer['twpg_answer']['OrderStatus'] == 'DECLINED')
					{
					// Снимаем с контроля платеж с финитным по TWPG статусом
					$db->query('
						UPDATE
							`minbank_payments`
						SET
							`checkedDate` = NULL
						WHERE
							`payment_id`=:payment_id
					', array(':payment_id' => $row['payment_id']));

					MInB_log(
						$row['payment_id'],
						'Платеж со статусом '.$answer['twpg_answer']['OrderStatus'].' снят с контроля',
						'MInB_booking_control_payments'
						);
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
						'MInB_booking_control_payments'
						);
					}
				}
			}
			else
			{
			MInB_log(
				$row['payment_id'],
				'Проверка статуса платежа. Ответ TWPG не получен либо не содержит поле OrderStatus: '.print_r ($answer,true),
				'MInB_booking_control_payments'
				);
			}
		}
	return;
}

//**************************************************
// backend страница загрузки писем с реестром
function MInB_booking_pop3($debug=false)
{
	// Подключим функционал коробочного решения
	MInB_booking_boxInit();
	$db = MInB_Database::Instance();

	require MINB_BOOKING_PATH.'../protected/pop3/pop3.php';

	$pop3 = new wspPop3();
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
					if (trim($arr[0])!=MINB_BOOKING_MERCHANTID) 
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

						if ($strarr[1] < 0)						
							{
							if ($debug) echo '&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; В строке '.$numstr.' отрицательная сумма платежа - платежка на возврат ('.$csvstr.'). Строка пропущена<br />';	
							$okstr++;
							continue;
							}
							
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
		'bookingTelefon',
		'bookingRoom',
		'bookingDescription'
	));
	
	if ($payment['params']['status']!=1 && $payment['params']['status']!=6) return;
	
	$msg="Получена оплата по счету (заказу) № {$payment['options']['bookingWord']}".
		 "<br/>ФИО: {$payment['options']['bookingName']}".
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
	$mail->Username = "";  
	$mail->Password = "";
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

function MInB_isBlockedPayment ($id)
{
	$payment = MInB_getPayment($id, true, array());

	if ( $payment === false ) return false;
	
	if ($payment['params']['status'] != 0) 
		{	
		return true;
		}
		else
		{
		$answer = MInB_getPaymentStatus($id);
	
		// Если финитный статус с отклонением или платеж не процессировался
		if ( isset($answer['twpg_answer']['OrderStatus']) && ($answer['twpg_answer']['OrderStatus'] == 'ERROR' || $answer['twpg_answer']['OrderStatus'] == 'EXPIRED' || $answer['twpg_answer']['OrderStatus'] == 'CANCELED' || $answer['twpg_answer']['OrderStatus'] == 'DECLINED' )
			|| ( isset($answer['params']) && empty($answer['params']['order_id']) && empty($answer['params']['session_id']) ))
			return false;
			else
			return true;	
		}	
}