<?php

// Собрано: UNDEFINED

// Клиент ОАО МИнБ для TWPG
class MInB_Client
{
	// Тексты ошибок
	const
		EXCEPTION_ADDRESS = 'Неправильно указан адрес сервера: ',
		EXCEPTION_MODE    = 'Неизвестный режим соединения: ',
		EXCEPTION_CERT    = 'Не найден сертификат: ',
		EXCEPTION_KEY     = 'Не найден ключ: ';
		
	// Значения
	const
		NOT_ISSET = 'не указан(а)';
	
	// Настройки сервера
	protected $server = array(
		'address' => false,
		'type'    => 'TEST'
	);
	
	// Данные SSL
	protected $ssl = array(
		'cert'       => false,
		'key'        => false,
		'certPasswd' => false,
		'caInfo'     => false
	);
	
	// Конструктор
	public function __construct($server = array(), $ssl = array())
	{
		if ( is_array($server) && isset($server['address']) )
			$this->setServer($server['address'], ( isset($server['type']) ? $server['type'] : 'TEST' ));
			
		if ( is_array($ssl) && isset($ssl['cert']) && isset($ssl['key']) )
			$this->setSSL($ssl['cert'], $ssl['key'], ( isset($ssl['certPasswd']) ? $ssl['certPasswd'] : false ), ( isset($ssl['caInfo']) ? $ssl['caInfo'] : false ));
	}
	
	// Установка адреса сервера
	public function setServer($address, $type = 'TEST')
	{
		if ( empty($address) || !is_string($address) )
			throw new MInB_Exception(self::EXCEPTION_ADDRESS . $address, 5);
		
		if ( $type != 'TEST' && $type != 'PROD' )
			throw new MInB_Exception(self::EXCEPTION_MODE . $type, 6);
		
		$this->server['address'] = $address;
		$this->server['type']    = $type;
		
		return $this;
	}
	
	// Получение адреса сервера
	public function getAddress()
	{
		return $this->server['address'];
	}
	
	// Получение режима соединения
	public function getMode()
	{
		return $this->server['type'];
	}
	
	// Установка сертификатов
	public function setSSL($cert, $key, $certPasswd = false, $caInfo = false)
	{
		if ( !file_exists($cert) )
			throw new MInB_Exception(self::EXCEPTION_CERT . $cert, 7);
		
		if ( !file_exists($key) )
			throw new MInB_Exception(self::EXCEPTION_KEY . $key, 8);
		
		if ( $certPasswd !== false )
			$this->ssl['certPasswd'] = $certPasswd;
		
		if ( $caInfo !== false )
		{
			if ( !file_exists($caInfo) )
				throw new MInB_Exception(self::EXCEPTION_CAINFO . $caInfo, 9);
			else
				$this->ssl['caInfo'] = $caInfo;
		}
		
		$this->ssl['cert'] = $cert;
		$this->ssl['key']  = $key;
		
		return $this;
	}

	// Получение сертификата
	public function getSSL()
	{
		return $this->ssl;
	}
	
	// Получение ответа
	public function getAnswer($payment)
	{
		MInB_Environment::DebugXML('Отправка XML-запроса', $payment->get());
	
		if ( $this->server['address'] === false )
			throw new MInB_Exception(self::EXCEPTION_ADDRESS . self::NOT_ISSET, 17);
	
		$curl = curl_init();
		
		curl_setopt_array($curl, array(
			CURLOPT_URL            => $this->server['address'],
			CURLOPT_SSL_VERIFYPEER => ( $this->server['type'] == 'PROD' ) ? 1 : 0,
			CURLOPT_SSL_VERIFYHOST => ( $this->server['type'] == 'PROD' ) ? 2 : 0,
			CURLOPT_SSLCERTPASSWD  => $this->ssl['certPasswd'],
			CURLOPT_SSLCERT        => $this->ssl['cert'],
			CURLOPT_SSLKEY         => $this->ssl['key'],
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_HTTPHEADER     => array('Content-Type: text/xml'),
			CURLOPT_POST           => true,
			CURLOPT_POSTFIELDS     => $payment->get(),
			CURLOPT_VERBOSE        => true
		));
		
		if ( $this->ssl['caInfo'] !== false && $this->server['type'] == 'PROD' )
			curl_setopt($curl, CURLOPT_CAINFO, $this->ssl['caInfo']);
		
		$answer = curl_exec($curl);	
		$error  = curl_error($curl);
		
		if ( !empty($error) )
			throw new MInB_Exception($error, 10);
			
		MInB_Environment::DebugXML('Получен XML-ответ', $answer);
		
		curl_close($curl);
		
		return $payment->parse($answer);
	}
}

// База данных PDO
class MInB_Database
{
	private $db;

	// Код инициализации БД
	private function __construct($dsn, $username, $password)
	{
		$this->db = new PDO($dsn, $username, $password, array(
			PDO::MYSQL_ATTR_INIT_COMMAND => 'SET CHARSET UTF8',
			PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
			PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION
		));
	}
	
	// Код выполнения запроса
	public function query($sql, $replace = array())
	{
		$stmt = $this->db->prepare($sql);
		$stmt->execute($replace);
		
		return $stmt;
	}
	
	// Последний id
	public function lastID()
	{
		return $this->db->lastInsertID();
	}
	
	// Singleton
	private function __clone()
	{
	}
	
	private function __wakeup()
	{
	}
	
	private static $instance = null;
	
	public static function Instance($dsn = null, $username = null, $password = null)
	{
		if ( self::$instance === null )
			self::$instance = new self($dsn, $username, $password);
			
		return self::$instance;
	}
}

// Окружение для библиотек MInB_Client, MInB_Payment
class MInB_Environment
{
	const VERSION = '2.1e';

	public static $Debug = false;
	
	public static function ParseAmount($amount)
	{
		return intVal(round($amount * 100, 0));
	}
	
	public static function DebugXML($title, $xml)
	{
		if ( self::$Debug )
		{
			echo '<h2>' . htmlspecialchars($title) . '</h2>';
			
			echo '<pre>' . htmlspecialchars(strtr($xml, array('>' => ">\n", '</' => "\n</"))) . '</pre>';
		}
	}
}

// Исключения для MInB_Payment, MInB_Client
class MInB_Exception extends Exception
{
	public function __construct($err, $errno)
		{
		parent::__construct($err, $errno);
		MInB_log(
			'',
			"Ошибка ($err, $errno)",
			'MInB_Exception'
		);
		
		}
}

// Платеж для ОАО МИнБ TWPG
class MInB_Payment
{
	// Тексты ошибок
	const
		EXCEPTION_OPERATION      = 'Неправильный тип операции: ',
		EXCEPTION_AMOUNT         = 'Неправильная сумма платежа(должна быть int): ',
		EXCEPTION_URLTYPE        = 'Неправильный тип URL: ',
		EXCEPTION_URLVAL         = 'Неправильный параметр URL: ',
		EXCEPTION_ORDERTYPE      = 'Неправильный тип платежа: ',
		EXCEPTION_PARAMKEY       = 'Неправильный тип ключа параметра: ',
		EXCEPTION_PARAMVALUE     = 'Неправильный тип значения параметра: ',
		EXCEPTION_MERCHANTID     = 'Неправильный ID магазина: ',
		EXCEPTION_DESCRIPTION    = 'Неправильный параметр описания: ',
		EXCEPTION_ORDERID        = 'Неправильный параметр OrderID: ',
		EXCEPTION_SESSIONID      = 'Неправильный параметр SessionID: ',
		EXCEPTION_SHOWOPERATIONS = 'Неправильный тип ShowOperations(должен быть bool): ',
		EXCEPTION_SHOWPARAMS     = 'Неправильный тип ShowParams(должен быть bool): ';
		
	// Стандартные параметры
	const
		DEFAULT_CURRENCY = 643,
		DEFAULT_ECI      = 52,
		DEFAULT_LANGUAGE = 'RU';

	// Значения
	const
		NOT_ISSET = 'не указан(а)';
		
	// Операция CreateOrder, GetOrderStatus и т. д.
	protected $operation;
	
	// ID магазина
	protected $merchantId;
	
	// Сумма платежа
	protected $amount;
	
	// Описание платежа
	protected $description;
	
	// URLs
	protected $urls = array(
		'APPROVE' => false,
		'DECLINE' => false,
		'CANCEL'  => false
	);
	
	// Тип платежа
	protected $orderType;
	
	// Номер платежа
	protected $orderID;
	
	// Номер сессии
	protected $sessionID;
	
	// CAVV
	protected $CAVV;
	
	// eci
	protected $eci;
	
	// Добавочные параметры
	protected $addParams = array();
	
	// ON/OFF ShowOperations
	protected $showOperations = true;
	
	// ON/OFF ShowParams
	protected $showParams = true;
	
	// Конструктор
	public function __construct($merchantId = false)
	{
		if ( $merchantId !== false )
			$this->setMerchantID($merchantId);
	}
	
	// ShowOperations
	public function showOperations($switch)
	{
		if ( !is_bool($switch) )
			throw new MInB_Exception(EXCEPTION_SHOWOPERATIONS . gettype($switch), 26);
			
		$this->showOperations = $switch;
		
		return $this;
	}
	
	// ShowParams
	public function showParams($switch)
	{
		if ( !is_bool($switch) )
			throw new MInB_Exception(EXCEPTION_SHOWPARAMS . gettype($switch), 27);
			
		$this->showParams = $switch;
		
		return $this;
	}
	
	// Добавление параметров
	public function addParam($key, $value)
	{
		if ( !$this->isParamKey($key) )
			throw new MInB_Exception(self::EXCEPTION_PARAMKEY . gettype($key), 15);
			
		if ( !$this->isParamValue($value) )
			throw new MInB_Exception(self::EXCEPTION_PARAMVALUE . gettype($value), 16);
	
		$this->addParams[$key] = $value;
		
		return $this;
	}
	
	// Проверка значений параметров
	public function isParamKey($key)
	{
		return ( is_string($key) ) ? true : false;
	}
	
	public function isParamValue($value)
	{
		return ( !is_array($value) && !is_object($value) ) ? true : false;
	}
	
	// Получение параметров
	public function getParams()
	{
		return $this->addParams;
	}
	
	// Отчистка параметров
	public function clearParams()
	{
		$this->addParams = array();
		
		return $this;
	}
	
	// Установка eci
	public function setEci($eci)
	{
		$this->eci = $eci;
		
		return $this;
	}
	
	// Получение eci
	public function getEci()
	{
		return $this->eci;
	}
	
	// Установка CAVV
	public function setCAVV($CAVV)
	{
		$this->CAVV = $CAVV;

		return $this;
	}
	
	// Получение CAVV
	public function getCAVV()
	{
		return $this->CAVV;
	}
	
	// Установка типа операции
	public function setOperation($type)
	{
		if ( !$this->isOperation($type) )
			throw new MInB_Exception(self::EXCEPTION_OPERATION . $type, 11);
				
		$this->operation = $type;
		
		return $this;
	}
	
	// Проверка типа операции
	public function isOperation($type)
	{
		$operations = array(
			'CreateOrder',
			'GetOrderStatus',
			'Completion',
			'GetOrderInformation',
			'Refund',
			'Reverse',
			'Recurring'
		);
	
		if ( !in_array($type, $operations) )
			return false;
		
		return true;
	}
	
	// Получение типа операции
	public function getOperation()
	{
		return $this->operation;
	}
	
	// Установка ID магазина
	public function setMerchantID($merchantId)
	{
		$this->merchantId = $merchantId;
		
		return $this;
	}

	// Получение ID магазина
	public function getMerchantID()
	{
		return $this->merchantId;
	}

	// Установка вендора магазина
//	public function setVendor($vendor)
//	{
//		$this->vendor = $vendor;
		
//		return $this;
//	}
	
	// Получение вендора магазина
//	public function getVendor()
//	{
//		return $this->vendor;
//	}

	// Установка суммы платежа
	public function setAmount($amount)
	{
		if ( !$this->isAmount($amount) )
			throw new MInB_Exception(self::EXCEPTION_AMOUNT . $amount, 12);
		
		$this->amount = $amount;
		
		return $this;
	}
	
	// Проверка суммы платежа
	public function isAmount($amount)
	{
		if ( !is_int($amount) || $amount <= 0 )
			return false;
		
		return true;
	}
	
	// Получение суммы платежа
	public function getAmount()
	{
		return $this->amount;
	}
	
	// Установка описания платежа
	public function setDescription($description)
	{
		$this->description = $description;
		
		return $this;
	}
	
	// Получение описания платежа
	public function getDescription()
	{
		return $this->description;
	}
	
	// Установка URL
	public function setUrl($type, $url)
	{
		if ( !$this->isUrlType($type) )
			throw new MInB_Exception(self::EXCEPTION_URLTYPE . $type, 13);
		
		$this->urls[$type] = $url;
		
		return $this;
	}
	
	// Проверка типа URL
	public function isUrlType($type)
	{
		if ( $type != 'APPROVE' && $type != 'CANCEL' && $type != 'DECLINE' )
			return false;
		
		return true;
	}
	
	// Получение URL
	public function getUrl($type)
	{
		return $this->urls[$type];
	}
	
	// Установка типа платежа
	public function setOrderType($orderType)
	{
		if ( !$this->isOrderType($orderType) )
			throw new MInB_Exception(self::EXCEPTION_ORDERTYPE . $orderType, 14);
		
		$this->orderType = $orderType;
		
		return $this;
	}
	
	// Проверка типа платежа
	public function isOrderType($orderType)
	{
		if ( $orderType != 'Payment' && $orderType != 'Purchase' && $orderType != 'PreAuth' ) // Добавлен тип $orderType != 'Payment' && (для оплат в пользу вендора)
			return false;
		
		return true;
	}
	
	// Получение типа платежа
	public function getOrderType()
	{
		return $this->orderType;
	}
	
	// Установка номера платежа
	public function setOrderID($orderID)
	{
		$this->orderID = $orderID;
		
		return $this;
	}
	
	// Получение номера платежа
	public function getOrderID()
	{
		return $this->orderID;
	}
	
	// Установка номера сессии
	public function setSessionID($sessionID)
	{
		$this->sessionID = $sessionID;
		
		return $this;
	}
	
	// Получение номера сессии
	public function getSessionID()
	{
		return $this->sessionID;
	}
	
	// Возвращает готовый результат запроса
	public function get()
	{
		$xml = new XMLWriter();
		
		$xml->openMemory();
		$xml->startDocument('1.0', 'UTF-8');
		
		$xml->startElement('TKKPG');
		$xml->startElement('Request');
		
		if ( !$this->isOperation($this->getOperation()) )
			throw new MInB_Exception(self::EXCEPTION_OPERATION . ( ( $this->getOperation() == null ) ? self::NOT_ISSET : $this->getOperation() ), 18);
		
		$xml->writeElement('Operation', ( $this->getOperation() == 'Recurring' ) ? 'Purchase' : $this->getOperation());
		$xml->writeElement('Language', self::DEFAULT_LANGUAGE);
		
		$xml->startElement('Order');
		
		if ( $this->getMerchantID() == null )
			throw new MInB_Exception(self::EXCEPTION_MERCHANTID . self::NOT_ISSET, 19);
		
		$xml->writeElement('Merchant', $this->getMerchantId());
		
		switch ( $this->getOperation() )
		{
			case 'CreateOrder':
				if ( !$this->isAmount($this->getAmount()) )
					throw new MInB_Exception(self::EXCEPTION_AMOUNT . self::NOT_ISSET, 20);
			
				$xml->writeElement('Amount', $this->getAmount());				
				$xml->writeElement('Currency', self::DEFAULT_CURRENCY);
				
				if ( $this->getDescription() == null )
					throw new MInB_Exception(self::EXCEPTION_DESCRIPTION . self::NOT_ISSET, 21);
				
				$xml->writeElement('Description', $this->getDescription());
				
				$urlsType = array_keys($this->urls);
				foreach ( $urlsType as &$urlType )
					if ( $this->getUrl($urlType) == null )
						throw new MInB_Exception(self::EXCEPTION_URLVAL . $urlType . ' ' . self::NOT_ISSET, 22);
				
				$xml->writeElement('ApproveURL', $this->getUrl('APPROVE'));
				$xml->writeElement('CancelURL', $this->getUrl('CANCEL'));
				$xml->writeElement('DeclineURL', $this->getUrl('DECLINE'));
				
				if ( !$this->isOrderType($this->getOrderType()) )
					throw new MInB_Exception(self::EXCEPTION_ORDERTYPE . self::NOT_ISSET, 23);
					
				$xml->writeElement('OrderType', $this->getOrderType());
			break;
			
			case 'Recurring':
			case 'Reverse':
			case 'Refund':
			case 'GetOrderInformation':
			case 'Completion':
			case 'GetOrderStatus':
				if ( $this->getOrderID() == null )
					throw new MInB_Exception(self::EXCEPTION_ORDERID . self::NOT_ISSET, 24);
			
				$xml->writeElement('OrderID', $this->getOrderID());
			break;
		}
		
		if ( !empty($this->addParams) )
		{
			$xml->startElement('AddParams');
		
			foreach ( $this->addParams as $key => &$value )
				$xml->writeElement($key, $value);
				
			$xml->endElement();
		}
		
		$xml->endElement();
		
		switch ( $this->getOperation() )
		{
			case 'Reverse':
			case 'GetOrderStatus':
				if ( $this->getSessionID() == null )
					throw new MInB_Exception(self::EXCEPTION_SESSIONID . self::NOT_ISSET, 25);
			
				$xml->writeElement('SessionID', $this->getSessionID());
			break;
			
			case 'Completion':
				if ( $this->getSessionID() == null )
					throw new MInB_Exception(self::EXCEPTION_SESSIONID . self::NOT_ISSET, 25);
					
				$xml->writeElement('SessionID', $this->getSessionID());
				
				if ( !$this->isAmount($this->getAmount()) )
					throw new MInB_Exception(self::EXCEPTION_AMOUNT . self::NOT_ISSET, 28);
				
				$xml->writeElement('Amount', $this->getAmount());
				$xml->writeElement('Currency', self::DEFAULT_CURRENCY);
				
				if ( $this->getDescription() == null )
					throw new MInB_Exception(self::EXCEPTION_DESCRIPTION . self::NOT_ISSET, 29);
					
				$xml->writeElement('Description', $this->getDescription());
			break;
			
			case 'GetOrderInformation':
				if ( $this->getSessionID() == null )
					throw new MInB_Exception(self::EXCEPTION_SESSIONID . self::NOT_ISSET, 25);
					
				$xml->writeElement('SessionID', $this->getSessionID());
				$xml->writeElement('ShowParams', ( $this->showParams ) ? 'true' : 'false');
				$xml->writeElement('ShowOperations', ( $this->showOperations ) ? 'true' : 'false');
			break;
			
			case 'Refund':
				if ( $this->getSessionID() == null )
					throw new MInB_Exception(self::EXCEPTION_SESSIONID . self::NOT_ISSET, 25);
					
				$xml->writeElement('SessionID', $this->getSessionID());
				
				$xml->startElement('Refund');
				
				if ( !$this->isAmount($this->getAmount()) )
					throw new MInB_Exception(self::EXCEPTION_AMOUNT . self::NOT_ISSET, 30);
				
				$xml->writeElement('Amount', $this->getAmount());
				$xml->writeElement('Currency', self::DEFAULT_CURRENCY);
				
				$xml->endElement();
			break;
			
			case 'Recurring':			
				if ( $this->getSessionID() == null )
					throw new MInB_Exception(self::EXCEPTION_SESSIONID . self::NOT_ISSET, 25);
					
				$xml->writeElement('SessionID', $this->getSessionID());
				
				if ( !$this->isAmount($this->getAmount()) )
					throw new MInB_Exception(self::EXCEPTION_AMOUNT . self::NOT_ISSET, 31);
				
				$xml->writeElement('Amount', $this->getAmount());
				$xml->writeElement('Currency', self::DEFAULT_CURRENCY);
				$xml->writeElement('PAN', '');
				$xml->writeElement('ExpDate', '');
				$xml->writeElement('CVV2', '');
				$xml->writeElement('CAVV', $this->getCAVV());
				$xml->writeElement('eci', ( $this->getEci() == null ) ? self::DEFAULT_ECI : $eci);
				$xml->writeElement('DraftCaptureFlag', '');
				$xml->writeElement('IP', '');
				$xml->writeElement('isMOTO', '');
			break;
		}
		
		$xml->endElement();
		$xml->endElement();
		
		return $xml->outputMemory();
	}
	
	// Возвращает готовый результат ответа
	public function parse($answer)
	{
		$xml = new XMLReader();
		$xml->XML($answer);
	
		switch ( $this->getOperation() )
		{
			case 'CreateOrder':
				$result = array(
					'Status'    => false,
					'OrderID'   => false,
					'SessionID' => false,
					'URL'       => false
				);
				
				$result = $this->getSimpleVars($xml, $result);
			break;
			
			case 'GetOrderStatus':
				$result = array(
					'Status'         => false,
					'OrderID'        => false,
					'OrderStatus'    => false,
					'AdditionalInfo' => false,
					'Receipt'        => false
				);
				
				$result = $this->getSimpleVars($xml, $result);
			break;
			
			case 'Reverse':
				$result = array(
					'Status'      => false,
					'OrderID'     => false,
					'RespCode'    => false,
					'RespMessage' => false
				);
				
				$result = $this->getSimpleVars($xml, $result);
			break;

			case 'GetOrderInformation':
				// @TODO: Избавиться от $switch, завязать на $i
				$result = array();
				$i = 0;
				$switch = false;
				
				// Избавимся от лишних полей
				$escape = array(
					'#text',
					'Order'
				);
				
				while ( $xml->read() )
				{
					if ( $xml->name == 'row' )
					{
						if ( !$switch )
							$switch = true;
						else
						{
							$switch = false;
							$i++;
						}
					}
					else
						if ( !in_array($xml->name, $escape) && $xml->expand()->nodeValue != '' )
							$result[$i][$xml->name] = $xml->expand()->nodeValue;
				}
			break;
			
			case 'Completion':
				$result = array(
					'Status'       => false,
					'ResponseCode' => false,
					'F'            => false,
					'R'            => false,
					'a'            => false,
					'h'            => false,
					't'            => false
				);
				
				while ( $xml->read() )
				{
					if ( $xml->name == 'Status' && !empty($xml->expand()->nodeValue) )
					{
						$result['Status'] = $xml->expand()->nodeValue;
						break;
					}
				}
				
				$result = $this->getTPTPVars($xml, $result);
			break;
		
			case 'Recurring':
				$xml->read();
				
				if ( $xml->name == 'TKKPG' )
				{
					$result = array(
						'Status' => false
					);
					
					$result = $this->getSimpleVars($xml, $result);
				}
				else
				{		
					$result = array(
						'ResponseCode' => false,
						'R'            => false,
						'a'            => false,
						'h'            => false,
						't'            => false
					);
					
					$result = $this->getTPTPVars($xml, $result);
				}
			break;
			
			case 'Refund':
				$result = array(
					'Status' => false
				);
				
				$result = $this->getSimpleVars($xml, $result);
			break;
		}
		
		$xml->close();
		
		return $result;
	}
	
	// Простая выборка параметров(подходит для протокола TWEC PG но не TPTP)
	protected function getSimpleVars($xml, $result)
	{
		while ( $xml->read() )
		{
			if ( isset($result[$xml->name]) && !empty($xml->expand()->nodeValue) )
				$result[$xml->name] = $xml->expand()->nodeValue;
		}
		
		return $result;
	}
	
	// Выборка для TPTP
	protected function getTPTPVars($xml, $result)
	{
		while ( $xml->read() )
		{
			if ( isset($result[$xml->getAttribute('name')]) )
				$result[$xml->getAttribute('name')] = $xml->getAttribute('value');
		}
		
		return $result;
	}
}

// Оболочка для MInB_Client, MInB_Payment для "коробочных решений"
class MInB_TWPG
{
	private $client;
	private $payment;

	public function client()
	{
		return $this->client;
	}
	
	public function payment()
	{
		return $this->payment;
	}

	// Singleton
	private static $instance = null;
	
	public static function Instance()
	{
		if ( self::$instance === null )
			self::$instance = new self();
			
		return self::$instance;
	}
	
	private function __clone()
	{
	}
	
	private function __wakeup()
	{
	}
	
	private function __construct()
	{
		$this->client  = new MInB_Client();
		$this->payment = new MInB_Payment();
	}
}

// Определение IP адреса пользователя
function MInB_IP()
{
	if ( isset($_SERVER['HTTP_CLIENT_IP']) && !empty($_SERVER['HTTP_CLIENT_IP']) )
		return $_SERVER['HTTP_CLIENT_IP'];
		
	if ( isset($_SERVER['HTTP_X_FORWARDED_FOR']) && !empty($_SERVER['HTTP_X_FORWARDED_FOR']) )
		return $_SERVER['HTTP_X_FORWARDED_FOR'];
		
	if ( isset($_SERVER['REMOTE_ADDR']) && !empty($_SERVER['REMOTE_ADDR']) )
		return $_SERVER['REMOTE_ADDR'];
		
	return '0.0.0.0';
}

// Подтверждение платежа
function MInB_approvePayment($id)
{
	if ( !MInB_existsPayment($id) )
		return false;

	$params = MInB_getPayment($id);
	
	// Установить клиента по id 
	if (!MInB_SetClientById($id)) return false;
	
	$twpg = MInB_TWPG::Instance();

	$answer = $twpg->client()->getAnswer(
		$twpg->payment()
			->setOrderID($params['params']['order_id'])
			->setSessionID($params['params']['session_id'])
			->setOperation('GetOrderInformation')
	);
	
	if ( !isset($answer[0]) )
	{
		MInB_log(
			$id,
			'В ответ от TWPG не получено данных',
			'MInB_approvePayment'
		);
	
		return false;
	}
	
	if ( !isset($answer[0]['Orderstatus']) )
	{
		MInB_log(
			$id,
			'В ответ от TWPG не получено поле Orderstatus',
			'MInB_approvePayment'
		);
		
		return false;
	}

	if ( $answer[0]['Orderstatus'] != 'APPROVED' && $answer[0]['Orderstatus'] != 'PREAUTH-APPROVED' )
	{
		MInB_log(
			$id,
			'В ответ от TWPG получен OrderStatus не равный APPROVED, PREAUTH-APPROVED: ' . $answer[0]['Orderstatus'],
			'MInB_approvePayment'
		);
		
		return false;	
	}

	if ( $params['params']['status'] == 1 ||  $params['params']['status'] == 5 ||  $params['params']['status'] == 6)
	{
		MInB_log(
			$id,
			'Повторный запрос статуса платежа: ' . $answer[0]['Orderstatus'],
			'MInB_approvePayment'
		);
	
		return array(
		'twpg_answer' => $answer
		);
	}
	
	if ( $params['params']['status'] != 0 )
	{
		MInB_log(
			$id,
			'Попытка подтвердить платеж с некорректным статусом: ' . $params['params']['status'],
			'MInB_approvePayment'
		);
	
		return false;
	}
	
	MInB_log(
		$id,
		'Статус платежа подтвержден: ' . $params['params']['status'] . ' => ' . ( ( $answer[0]['Orderstatus'] == 'APPROVED' ) ? 1 : 5 ),
		'MInB_approvePayment'
	);
	
	$db = MInB_Database::Instance();
	
	if ( $answer[0]['Orderstatus'] == 'APPROVED' )
		$db->query('
			UPDATE
				`minbank_payments`
			SET
				`paidDate`=:payDate,
				`status`=:status,
				`paidAmount`=:amount,
				`ip`=:ip
			WHERE
				`payment_id`=:id
		', array(':payDate'=>$answer[0]['payDate'],  ':status' => 1, ':id' => $id, ':amount' => $params['params']['startAmount'], ':ip'=>MInB_IP())
		);
	else
		$db->query('
			UPDATE
				`minbank_payments`
			SET
				`paidDate`=:payDate,
				`status`=:status,
				`ip`=:ip
			WHERE
				`payment_id`=:id
		', array(':payDate'=>$answer[0]['payDate'], ':status' => 5, ':id' => $id, ':ip'=>MInB_IP())
		);	
	
	//MInB_updatePaymentOptions($id,array('answer'=>serialize($answer)),false);
	
	return array(
		'twpg_answer' => $answer
	);
}

// Определение браузера пользователя
function MInB_browser()
{
	if ( isset($_SERVER['HTTP_USER_AGENT']) && !empty($_SERVER['HTTP_USER_AGENT']) )
		return $_SERVER['HTTP_USER_AGENT'];
	
	return 'unknown';
}

// Отмена платежа
function MInB_cancelPayment($id)
{
	if ( !MInB_existsPayment($id) )
		return false;

	$params = MInB_getPayment($id);
	if ( $params['params']['status'] != 0 )
	{
		MInB_log(
			$id,
			'Попытка отменить платеж с некорректным статусом: ' . $params['params']['status'],
			'MInB_cancelPayment'
		);
	
		return false;
	}
	
	$params = MInB_getPayment($id);
	
	// Установить клиента по id 
	if (!MInB_SetClientById($id)) return false;
	
	$twpg = MInB_TWPG::Instance();
	
	$answer = $twpg->client()->getAnswer(
		$twpg->payment()
			->setOrderID($params['params']['order_id'])
			->setSessionID($params['params']['session_id'])
			->setOperation('GetOrderInformation')
	);
	
	if ( !isset($answer[0]) )
	{
		MInB_log(
			$id,
			'В ответ от TWPG не получено данных',
			'MInB_cancelPayment'
		);
	
		return false;
	}
	
	if ( !isset($answer[0]['Orderstatus']) )
	{
		MInB_log(
			$id,
			'В ответ от TWPG не получено поле Orderstatus',
			'MInB_cancelPayment'
		);
		
		return false;
	}
	
	if ( $answer[0]['Orderstatus'] != 'CANCELED' )
	{
		MInB_log(
			$id,
			'В ответ от TWPG получен OrderStatus не равный CANCELED: ' . $answer[0]['Orderstatus'],
			'MInB_cancelPayment'
		);
		
		return false;	
	}
	
	MInB_log(
		$id,
		'Платеж отменен: ' . $params['params']['status'] . ' => 3',
		'MInB_cancelPayment'
	);
	
	$db = MInB_Database::Instance();
	$db->query('
		UPDATE
			`minbank_payments`
		SET
			`status`=3
		WHERE
			`payment_id`=:id
	', array(':id' => $id));

	return true;
}

// Завершение предавторизации
function MInB_completionPayment($id, $amount = false, $description = false)
{
	if ( !MInB_existsPayment($id) )
		return false;
		
	$params = MInB_getPayment($id);
	
	// Установить клиента по id 
	if (!MInB_SetClientById($id)) return false;
	
	$twpg = MInB_TWPG::Instance();
	
	if ( $params['params']['status'] != 5 )
	{
		MInB_log(
			$id,
			'Попытка завершить платеж с некорректным статусом: ' . $params['params']['status'],
			'MInB_completionPayment'
		);
	
		return false;
	}
	
	$amount      = ( $amount !== false ) ? MInB_Environment::ParseAmount($amount) : intVal($params['params']['startAmount']);
	$description = ( $description !== false ) ? str_replace(':id', $id, $description) : 'Подтверждение операции #' . $id;
	
	if ( $amount > intVal($params['params']['startAmount']) )
	{
		MInB_log(
			$id,
			'Попытка указания суммы большей начальной: ' . $amount . ', начальная: ' . intVal($params['params']['startAmount']),
			'MInB_completionPayment'
		);
	
		return false;
	}
	
	MInB_log(
		$id,
		'Подтверждаем платеж, сумма: ' . $params['params']['startAmount'] . ' => ' . $amount . ', описание: ' . $description,
		'MInB_completionPayment'
	);
	
	$db = MInB_Database::Instance();
	$answer = $twpg->client()->getAnswer(
		$twpg->payment()
			->setOrderID($params['params']['order_id'])
			->setSessionID($params['params']['session_id'])
			->setOperation('Completion')
			->setAmount($amount)
			->setDescription($description)
	);
	
	if ( !isset($answer['Status']) )
	{
		MInB_log(
			$id,
			'От TWPG не получено поле Status',
			'MInB_completionPayment'
		);
	
		return false;
	}
	
	if ( $answer['Status'] != '00' )
	{
		MInB_log(
			$id,
			'Поле Status отлично от 00: ' . $answer['Status'],
			'MInB_completionPayment'
		);
	
		return false;
	}
	
	if ( !isset($answer['ResponseCode']) )
	{
		MInB_log(
			$id,
			'Не установлено поле ResponseCode от TWPG',
			'MInB_completionPayment'
		);
	
		return false;
	}
	
	if ( $answer['ResponseCode'] != '001' )
	{
		MInB_log(
			$id,
			'Поле ResponseCode отлично от 001: ' . $answer['ResponseCode'],
			'MInB_completionPayment'
		);
	
		return false;
	}
	
	MInB_log(
		$id,
		'Статус платежа подтвержден: ' . $params['params']['status'] . ' => 6',
		'MInB_completionPayment'
	);

	$db->query('
		UPDATE
			`minbank_payments`
		SET
			`paidDate`=NOW(),
			`status`=:status,
			`paidAmount`=:amount
		WHERE
			`payment_id`=:id
	', array(':status' => 6, ':id' => $id, ':amount' => $amount));
	
	return array(
		'twpg_answer' => $answer
	);
}

// Конвертация id статуса в текст
function MInB_convertPaymentStatus($id)
{
	$text = array(
		0 => 'СОЗДАН',
		1 => 'ОПЛАЧЕН',
		2 => 'ВЫГРУЖЕН',
		3 => 'ОТМЕНЕН',
		4 => 'ОТКАЗАН БАНКОМ',
		5 => 'ПРЕДАВТОРИЗАЦИЯ, СРЕДСТВА ЗАБЛОКИРОВАНЫ',
		6 => 'ОПЛАЧЕН(ПРЕДАВТОРИЗАЦИЯ)',
		7 => 'УДАЛЕН',
		8 => 'ВОЗВРАЩЕН'
	);
	
	return ( !isset($text[$id]) ) ? 'НЕИЗВЕСТНЫЙ' : $text[$id];
}

// Создание платежа (добавлено $id в параметры)
function MInB_createPayment($amount, $id, $options = array())
{
	$parsedAmount = MInB_Environment::ParseAmount($amount);

	$db = MInB_Database::Instance();
	$db->query('
		INSERT INTO
			`minbank_payments`
		(
			`payment_id`,
			`createdDate`,
			`startAmount`,
			`ip`
		)
		VALUES
		(
			:payment_id,
			NOW(),
			:amount,
			:ip
		)
	', array('payment_id' => $id, ':amount' => $parsedAmount, ':ip'=>MInB_IP()));
	
//	$id = $db->lastID();
	MInB_log(
		$id,
		'Создан платеж на сумму: ' . $amount . ' => ' . $parsedAmount,
		'MInB_createPayment'
	);
	
	if ( !empty($options) )
		MInB_updatePaymentOptions($id, $options);
	
	return array(
		'payment_id' => $id
	);
}

// Отмена платежа банком
function MInB_declinePayment($id)
{
	if ( !MInB_existsPayment($id) )
		return false;

	$params = MInB_getPayment($id);
	if ( $params['params']['status'] != 0 )
	{
		MInB_log(
			$id,
			'Попытка отказать платеж банком с некорректным статусом: ' . $params['params']['status'],
			'MInB_declinePayment'
		);
	
		return false;
	}
	
	$params = MInB_getPayment($id);
	
	// Установить клиента по id 
	if (!MInB_SetClientById($id)) return false;
	
	$twpg = MInB_TWPG::Instance();
	
	$answer = $twpg->client()->getAnswer(
		$twpg->payment()
			->setOrderID($params['params']['order_id'])
			->setSessionID($params['params']['session_id'])
			->setOperation('GetOrderInformation')
	);
	
	if ( !isset($answer[0]) )
	{
		MInB_log(
			$id,
			'В ответ от TWPG не получено данных',
			'MInB_declinePayment'
		);
	
		return false;
	}
	
	if ( !isset($answer[0]['Orderstatus']) )
	{
		MInB_log(
			$id,
			'В ответ от TWPG не получено поле Orderstatus',
			'MInB_declinePayment'
		);
		
		return false;
	}
	
	if ( $answer[0]['Orderstatus'] != 'DECLINED' )
	{
		MInB_log(
			$id,
			'В ответ от TWPG получен OrderStatus не равный DECLINED: ' . $answer[0]['Orderstatus'],
			'MInB_declinePayment'
		);
		
		return false;	
	}
	
	MInB_log(
		$id,
		'Платеж отказан банком: ' . $params['params']['status'] . ' => 4',
		'MInB_declinePayment'
	);
	
	$db = MInB_Database::Instance();
	$db->query('
		UPDATE
			`minbank_payments`
		SET
			`status`=4
		WHERE
			`payment_id`=:id
	', array(':id' => $id));

	return true;
}

// Удаление платежа
function MInB_deletePayment($id)
{
	if ( !MInB_existsPayment($id) )
		return false;
		
	$params = MInB_getPayment($id);
	if ( $params['params']['status'] == 7 )
	{
		MInB_log(
			$id,
			'Попытка удалить платеж с некорректным статусом: ' . $params['params']['status'],
			'MInB_deletePayment'
		);
	
		return false;
	}
	
	MInB_log(
		$id,
		'Платеж удален: ' . $params['params']['status'] . ' => 7',
		'MInB_deletePayment'
	);
	
	$db = MInB_Database::Instance();
	$db->query('
		UPDATE
			`minbank_payments`
		SET
			`status`=7
		WHERE
			`payment_id`=:id
	', array(':id' => $id));

	return true;
}

// Экранирование html-символов
function MInB_escape($html)
{
	return htmlspecialchars($html);
}

// Проверка существования платежа
function MInB_existsPayment($id, $status = false)
{
	if ( !MInB_isPaymentID($id) )
		return false;

	$db = MInB_Database::Instance();
	
	$sql = '
		SELECT
			`payment_id`
		FROM
			`minbank_payments`
		WHERE
			`payment_id`=:id
		';
	$replace = array(':id' => $id);
	
	if ( $status !== false )
	{
		$sql .= '
			AND
		`status`=:status
		';
		
		$replace[':status'] = $status;
	}
	
	$stmt = $db->query($sql, $replace);
	return ( $stmt->rowCount() > 0 ) ? true : false;
}

// Для работы с $_GET
function MInB_get($key, $default = false)
{
	return ( isset($_GET[$key]) ) ? $_GET[$key] : $default;
}

// Получение данных о платеже
function MInB_getPayment($id, $params = true, $options = array(), $twpg = false)
{
	if ( !MInB_existsPayment($id) )
		return false;
		
	$db = MInB_Database::Instance();
	$result = array();
	
	if ( $params !== false )
	{
		$result['params'] = array();
	
		$stmt = $db->query('
			SELECT
				`payment_id`,
				`createdDate`,
				`paidDate`,
				`exportedDate`,
				`status`,
				`startAmount`,
				`paidAmount`,
				`refundAmount`,
				`order_id`,
				`session_id`,
				`ip`
			FROM
				`minbank_payments`
			WHERE
				`payment_id`=:payment_id
		', array(':payment_id' => $id));
		
		$result['params'] = $stmt->fetch();
	}
	
	if ( !empty($options) )
	{
		$result['options'] = array();
		
		$sql = '
			SELECT
				`key`,
				`value`
			FROM
				`minbank_payments_options`
			WHERE
				`payment_id`=:payment_id
					AND
		';
		$replace = array(':payment_id' => $id);
		
		$i = 0;
		foreach ( $options as &$key )
		{
			$sql .= ( $i == 0 ) ? '( `key`=:key' . $i : ' OR `key`=:key' . $i;
			$replace[':key' . $i] = $key;
			$i++;
		}
		
		$sql .= ' )';
		$stmt = $db->query($sql, $replace);
		
		if ( $stmt->rowCount() > 0 )
			while ( $row = $stmt->fetch() )
				$result['options'][$row['key']] = $row['value'];
	}
	
	if ( $twpg !== false )
	{
		$result['twpg_answer'] = array();
		
		$data = ( $params !== false && isset($result) ) ? $result : MInB_getPayment($id);
		
		if ( !empty($data['params']['order_id']) && !empty($data['params']['session_id']) )
		{	
			// Установить клиента по id 
			if (!MInB_SetClientById($id)) return false;
		
			$twpg = MInB_TWPG::Instance();
			$answer = $twpg->client()->getAnswer(
			$twpg->payment()
				->setOrderID($data['params']['order_id'])
				->setSessionID($data['params']['session_id'])
				->setOperation('GetOrderInformation')
			);
			
			if ( !isset($answer[0]) )
			{
				MInB_log(
					$id,
					'В ответ от TWPG не получено данных',
					'MInB_getPayment'
				);
			
				return false;
			}
			
			$result['twpg_answer'] = $answer;
		}
	}
	
	return $result;
}

// Получение логов по платежу
function MInB_getPaymentLogs($id, $limit = false)
{
//	if ( !MInB_existsPayment($id) )
//		return false;
		
	$db = MInB_Database::Instance();
	$result = array();
	
	$sql = '
		SELECT
			`payment_id`,
			`date`,
			`location`,
			`text`,
			`ip`,
			`browser`
		FROM
			`minbank_payments_logs`
		WHERE
			`payment_id`=:payment_id
		ORDER BY
			`date`
		DESC
	';
	$replace = array(':payment_id' => $id);
	
	if ( MInB_isInteger($limit) )
		$sql .= '
			LIMIT ' . $limit
		;
	
	$stmt = $db->query($sql, $replace);
	
	if ( $stmt->rowCount() > 0 )
		while ( $row = $stmt->fetch() )
			$result[] = $row;
		
	return $result;
}

// Получение платежей по опции
function MInB_getPaymentsByOption($key, $value)
{
	$db = MInB_Database::Instance();
	$result = array();
	
	$stmt = $db->query('
		SELECT
			`payment_id`
		FROM
			`minbank_payments_options`
		WHERE
			`key`=:key
				AND
			`value`=:value
	', array(':key' => $key, ':value' => $value));
	
	if ( $stmt->rowCount() > 0 )
		while ( $row = $stmt->fetch() )
			$result[] = $row;
	
	return $result;
}

// Получение данных о платежах. Форсировка запросов
function MInB_getPayments($params=true, $options=array(),$first=false, $size=false, $status=false)
{
	$db = MInB_Database::Instance();

	// ********************	
	$start = microtime(true);
	$m = memory_get_usage(); 
	// ********************	

	$result = array();
	
	$mod_where=$mod_lim='';

	if ($status!==false && $status>=0 && $status<=9) $mod_where=' WHERE `status`='.(int)$status;  

	if ($first!==false && $size!==false) $mod_lim=' LIMIT '.(int)$first.','.(int)$size;
	
	
	if ($params==true)
		{
		$stmt = $db->query('
			SELECT
				*
			FROM
				`minbank_payments`
			'.$mod_where.'
			ORDER BY
				`paidDate` ASC
			'.$mod_lim.'
		', array());
			
		while ($row = $stmt->fetch())
			$result[$row['payment_id']]['params']=$row;
		}
		else
		{
		$stmt = $db->query('
			SELECT
				count(*)
			FROM
				`minbank_payments`
			'.$mod_where.'
		', array());
		
		$tmp = $stmt->fetch();
		
		return $tmp['count(*)'];
		}
		
	if (!empty($options))
		{
		foreach ($options as &$val)
			$val='"'.$val.'"';
			
		$opt_list=implode($options,',');
		
		$stmt = $db->query('
			SELECT
				*
			FROM
				`minbank_payments_options` AS `t`
			JOIN
				(SELECT
					`payment_id`
				FROM
					`minbank_payments`
				'.$mod_where.'
				ORDER BY
					`paidDate` ASC				
				'.$mod_lim.'
				)
				AS `tt`
			ON 
				`t`.`payment_id`=`tt`.`payment_id`
			WHERE
				`key` IN ('.$opt_list.')
		', array());
		
		while ($row = $stmt->fetch())
			$result[$row['payment_id']]['options'][$row['key']]=$row['value'];
		}

	// ********************
	$finish = microtime(true);
	$delta = $finish - $start;
	//echo ' >> '.$delta . ' сек. << ';
	$m = memory_get_usage() - $m;
	//echo 'Занято памяти '.$m.' байт<br>';
	// ********************	

	return $result;
}


// Получение логов по всей системе
function MInB_getPaymentsLogs($limit = false)
{
	$db = MInB_Database::Instance();
	$result = array();
	
	$sql = '
		SELECT
			`payment_id`,
			`date`,
			`location`,
			`text`,
			`ip`,
			`browser`
		FROM
			`minbank_payments_logs`
		ORDER BY
			`date`
		DESC
	';
	
	if ( MInB_isInteger($limit) )
		$sql .= 'LIMIT ' . $limit;
		
	$stmt = $db->query($sql);
	
	if ( $stmt->rowCount() > 0 )
		while ( $row = $stmt->fetch() )
			$result[] = $row;
		
	return $result;
}

// Проверка, является ли строка суммой
function MInB_isAmount($str)
{
	return is_numeric($str);
}

function MInB_isInteger($val)
{
	return preg_match('/\d/', $val);
}

// Проверка ID платежа
function MInB_isPaymentID($id)
{
	return preg_match('/^[0-9A-Za-z\-]+$/u', $id);
}

// Логирование информации
function MInB_log($payment_id, $text, $location = 'unknown')
{
	$db = MInB_Database::Instance();
	$db->query('
		INSERT INTO
			`minbank_payments_logs`
		(
			`payment_id`,
			`date`,
			`location`,
			`text`,
			`ip`,
			`browser`
		)
		VALUES
		(
			:payment_id,
			NOW(),
			:location,
			:text,
			:ip,
			:browser
		)
	', array(':payment_id' => $payment_id, ':location' => $location, ':text' => $text, ':ip' => MInB_IP(), ':browser' => MInB_browser()));
}

// Для работы с $_POST
function MInB_post($key, $default = false)
{
	return ( isset($_POST[$key]) ) ? $_POST[$key] : $default;
}

// Оплата платежа
function MInB_processPayment($id, $params, $options = array())
{
	if ( !MInB_existsPayment($id) )
		return false;
		
	$mustBeArray = array('description', 'orderType', 'approveUrl', 'declineUrl', 'cancelUrl');
	foreach ( $mustBeArray as &$mustBeValue )
		if ( !isset($params[$mustBeValue]) )
			return false;
		
	$paymentCredits = MInB_getPayment($id,true,array('LICSH'));
	if ( $paymentCredits['params']['status'] != 0 )
	{
		MInB_log(
			$id,
			'Попытка оплатить платеж с некорректным статусом: ' . $paymentCredits['params']['status'],
			'MInB_processPayment'
		);
	
		return false;
	}
	
	$db = MInB_Database::Instance();
	
	MInB_log(
		$id,
		'Оплата платежа, сумма: ' . intval($paymentCredits['params']['startAmount']) .
		', описание: ' . $params['description'] .
		', тип платежа: ' . $params['orderType'] .
		', ссылки(approve, decline, cancel): ' . $params['approveUrl'] .
		', ' . $params['declineUrl'] .
		', ' . $params['cancelUrl'],
		'MInB_processPayment'
	);
	
	// Установить клиента по id 
	if (!MInB_SetClientById($id)) return false;
	
	$twpg = MInB_TWPG::Instance();
	$answer = $twpg->client()->getAnswer(
		$twpg->payment()
			->setAmount(intval($paymentCredits['params']['startAmount']))
			->setDescription($params['description'])
			->setUrl('APPROVE', $params['approveUrl'])
			->setUrl('DECLINE', $params['declineUrl'])
			->setUrl('CANCEL',  $params['cancelUrl'])
			->setOrderType($params['orderType'])
			->setOperation('CreateOrder')
	);
	
	if ( !isset($answer['Status']) )
	{
		MInB_log(
			$id,
			'В ответ от TWPG не получено поле Status',
			'MInB_processPayment'
		);
	
		return false;
	}
	
	if ( $answer['Status'] != '00' )
	{
		MInB_log(
			$id,
			'В ответ от TWPG получен Status не равный 00: ' . $answer['Status'],
			'MInB_processPayment'
		);
		
		return false;
	}
	
	if ( !isset($answer['OrderID']) )
	{
		MInB_log(
			$id,
			'В ответ от TWPG не получено поле OrderID',
			'MInB_processPayment'
		);
		
		return false;
	}
	
	if ( !isset($answer['SessionID']) )
	{
		MInB_log(
			$id,
			'В ответ от TWPG не получено поле SessionID',
			'MInB_processPayment'
		);
		
		return false;
	}
	
	MInB_log(
		$id,
		'Получены OrderID: ' . $answer['OrderID'] . ', SessionID: ' . $answer['SessionID'],
		'MInB_processPayment'
	);
	
	$stmt = $db->query('
		UPDATE
			`minbank_payments`
		SET
			`order_id`=:order_id,
			`session_id`=:session_id,
			`checkedDate` = NOW()
		WHERE
			`payment_id`=:payment_id
	', array(':order_id' => $answer['OrderID'], ':session_id' => $answer['SessionID'], ':payment_id' => $id));
	
	if ( !empty($options) )
		MInB_updatePaymentOptions($id, $options);
		
	$url = $answer['URL'] . 'index.jsp?ORDERID=' . $answer['OrderID'] . '&SESSIONID=' . $answer['SessionID'];
	
	MInB_log(
		$id,
		'Сгенерирован payment_url: ' . $url,
		'MInB_processPayment'
	);
		
	return array(
		'payment_id'  => $id,
		'payment_url' => $url
	);
}

// Возращение средств по платежу
function MInB_refundPayment($id, $amount = false)
{
	if ( !MInB_existsPayment($id, 1) && !MInB_existsPayment($id, 6) )
		return false;
		
	$params = MInB_getPayment($id);
	
	// Установить клиента по id 
	if (!MInB_SetClientById($id)) return false;
	
	$twpg = MInB_TWPG::Instance();
	$db = MInB_Database::Instance();
	
	$amount = ( $amount !== false ) ? MInB_Environment::ParseAmount($amount) : intVal($params['params']['startAmount']);
	
	$answer = $twpg->client()->getAnswer(
		$twpg->payment()
			->setOrderID($params['params']['order_id'])
			->setSessionID($params['params']['session_id'])
			->setOperation('Refund')
			->setAmount($amount)
	);
	
	if ( !isset($answer['Status']) )
	{
		MInB_log(
			$id,
			'От TWPG не получено поле Status',
			'MInB_refundPayment'
		);
	
		return false;
	}
	
	if ( $answer['Status'] != '00' )
	{
		MInB_log(
			$id,
			'Поле Status отлично от 00: ' . $answer['Status'],
			'MInB_refundPayment'
		);
	
		return false;
	}
	
	$db->query('
		UPDATE
			`minbank_payments`
		SET
			`status`=:status,
			`refundAmount`=:amount
		WHERE
			`payment_id`=:id
	', array(
		':status' => 8,
		':amount' => $amount,
		':id'     => $id
	));
	
	MInB_log(
		$id,
		'Возращены средства в размере: ' . $amount . ' => ' . $amount / 100,
		'MInB_refundPayment'
	);
	
	return true;
}

// Обновление данных платежа
function MInB_updatePaymentOptions($id, $options = array(), $log = true)
{
	if ( !MInB_existsPayment($id) )
		return false;
		
	$db = MInB_Database::Instance();
	
	if ( !empty($options) )
	{
		foreach ( $options as $key => &$value )
		{
			$db->query('
				INSERT INTO
					`minbank_payments_options`
				(
					`payment_id`,
					`key`,
					`value`
				)
				VALUES
				(
					:id,
					:key,
					:value
				)
				ON DUPLICATE KEY UPDATE `value`=:value
			', array(':id' => $id, ':key' => $key, ':value' => $value));
		}
	if ($log) MInB_log(
		$id,
		'Обновлены опции платежа: ' . substr(print_r($options, true),6),
		'MInB_updatePaymentOptions'
		);
	}
}

// Обновление данных платежа
function MInB_updatePaymentParams($id, $params = array())
{
	//if ( !MInB_existsPayment($id, 0) && !MInB_existsPayment($id, 5) )
		//return false;
		
	$db = MInB_Database::Instance();
	
	if ( !empty($params) )
	{
		$allowed = array('refundAmount','sended');
	
		foreach ( $params as $key => &$value )
		{
			if ( !in_array($key, $allowed) )
			{
				MInB_log(
					$id,
					'Попытка изменения запрещенного параметра: ' . $key . ', значение: ' . $value,
					'MInB_updatePaymentParams'
				);
			
				continue;
			}
		
			if ( $key == 'refundAmount' )
				$value = MInB_Environment::ParseAmount($value);
		
			MInB_log(
				$id,
				'Обновлен параметр платежа: ' . $key . ', значение: ' . $value,
				'MInB_updatePaymentParams'
			);
			
			$db->query('
				UPDATE
					`minbank_payments`
				SET
					`' . $key . '`=:value
				WHERE
					`payment_id`=:payment_id
			', array(':value' => $value, ':payment_id' => $id));
		}
	}
	
	return true;
}

// Установить клиента по id
function MInB_SetClientById($id)
{
	$twpg = MInB_TWPG::Instance();
	
	if (true)
		{
		// Настроим соединение с платежной системой
		$twpg->client()->setSSL(MINB_CERT, MINB_KEY, false, MINB_ROOTCA);
		$twpg->payment()->setMerchantID(MINB_MERCHANTID);		
//		$twpg->payment()->setVendor(MINB_VENDOR_1);		
		return true;
		}
		
	return false;
}

// Получение статуса платежа
function MInB_getPaymentStatus($id)
{
	if ( !MInB_existsPayment($id) )
		return false;
	
	$result=MInB_getPayment($id);
	
	$result['twpg_answer'] = array();
		
	if ( !empty($result['params']['order_id']) && !empty($result['params']['session_id']) && MInB_SetClientById($id))
		{
		$twpg = MInB_TWPG::Instance();
		$answer = $twpg->client()->getAnswer(
		$twpg->payment()
			->setOrderID($result['params']['order_id'])
			->setSessionID($result['params']['session_id'])
			->setOperation('GetOrderStatus')
		);

		MInB_log(
				$id,
				'Запрос статуса платежа. Статус: ' . $answer['OrderStatus'],
				'MInB_getPaymentStatus'
			);
			
		if (isset($answer)) $result['twpg_answer'] = $answer;
		}
	
	return $result;
}

// Проверка существования платежа в архиве
function MInB_existsArchivePayment($id)
{
	if ( !MInB_isPaymentID($id) )
		return false;

	$db = MInB_Database::Instance();
	
	$stmt = $db->query('
		SELECT
			`payment_id`
		FROM
			`minbank_payments_archive`
		WHERE
			`payment_id`=:id
		', array(':id' => $id));
		
	return ( $stmt->rowCount() > 0 ) ? true : false;
}