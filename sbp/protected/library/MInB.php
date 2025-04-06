<?php
// Собрано: UNDEFINED
function getPAY_ID($paymentId) {
    // Создаем подключение к базе данных
    $db = MInB_Database::Instance();
    // Выполняем запрос для получения PAY_ID по payment_id
    $result = $db->query('
        SELECT
            `value`
        FROM
            `minbank_payments_options`
        WHERE
            `payment_id` = :payment_id
            AND `key` = "PAY_ID"
        LIMIT 1
    ', [
        ':payment_id' => $paymentId
    ]);

    // Используем fetch для получения результата
    $row = $result->fetch(PDO::FETCH_ASSOC);

    // Проверяем, найден ли результат
    if ($row) {
        echo "pay_id = >";
        echo $row['value'];  // Вместо 'PAY_ID' выводим значение из столбца 'value'
        return $row['value']; // Возвращаем значение столбца 'value'
    } else {
        // Если результат пуст, возвращаем null или выбрасываем исключение
        return null;
    }
}


function getCreateDate($paymentId) {
    // Создаем подключение к базе данных
    $db = MInB_Database::Instance();

    // Выполняем запрос для получения createdDate по payment_id
    $result = $db->query('
        SELECT
            `createdDate`
        FROM
            `minbank_payments`
        WHERE
            `payment_id` = :payment_id
        LIMIT 1
    ', [
        ':payment_id' => $paymentId
    ]);

    // Используем fetch для получения результата
    $row = $result->fetch(PDO::FETCH_ASSOC);

    // Проверяем, найден ли результат
    if ($row) {
        echo "createdDate = >";
        echo $row['createdDate'];
        return $row['createdDate'];
    } else {
        // Если результат пуст, возвращаем null или выбрасываем исключение
        return null;
    }
}

/*
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
        'Обработан ответ от банкаMinB: ' . json_encode($resultData),
        'processBankResponse'
    );

    // Обновляем данные в базе, если статус платежа подтвержден
    if ($resultData['status'] === 1) {
        $db = MInB_Database::Instance();
        echo '12312313';
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
            ':paidDate' => date('Y-m-d H:i:s', strtotime('+4 hours', strtotime($resultData['paidDate']))),
            ':status' => $resultData['status'],
            ':paidAmount' => $resultData['paidAmount'],
            ':ip' => MInB_IP(),
            ':reference' => $resultData['reference'],
            
        ]);
    }

}
*/
// Клиент ОАО МИнБ для TWPG
class MInB_Client
{
	// Тексты ошибок
	const
		EXCEPTION_ADDRESS = 'Неправильно указан адрес сервера: ',
		EXCEPTION_MODE    = 'Неизвестный режим соединения: ';
		
	// Значения
	const
		NOT_ISSET = 'не указан(а)';
	
	// Настройки сервера
	protected $server = array(
		'address' => false,
		'type'    => 'TEST'
	);
	
	
	
	// Конструктор
	public function __construct($server = array())
	{
		if ( is_array($server) && isset($server['address']) )
			$this->setServer($server['address'], ( isset($server['type']) ? $server['type'] : 'TEST' ));
			
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
	
	
	// Set Api urls for operations:
	public function getOperationApi($op){
		$operationsAPI = array (
			'CreateOrder' => 'payment_ref/generate_payment_ref',
			'GetOrderInformation' => 'check_operation/ecomm_check',
			'Refund' => 'cgi_link',
			'GetOrderStatus' => 'check_operation/ecomm_check',
			'Reverse' => 'cgi_link',
			'Completion' => 'cgi_link'
		);
		return $operationsAPI[$op];
	}
	
	// Получение ответа
	public function getAnswer($payment)
	{
		if ( $this->server['address'] === false )
			throw new MInB_Exception(self::EXCEPTION_ADDRESS . self::NOT_ISSET, 17);
	    $logData = [
        'timestamp' => date('Y-m-d H:i:s'),
        'post_data' => $payment->get()
        ];
		$curl = curl_init();
		
		$op = $this->getOperationApi($payment->getOperation());
		
		$host = ( $this->server['type'] == 'PROD' ) ? "3ds.payment.ru" : "test.3ds.payment.ru";

		$headers = [
			"Host: " . $host,
			"User-Agent: " . $_SERVER['HTTP_USER_AGENT'],
			"Accept: */*",
			"Content-Type: application/x-www-form-urlencoded; charset=utf-8"
		];
		
		// Логирование URL в файл post_log.txt
        $logUrl = $this->server['address'] . $op;
        file_put_contents('post_log.txt', date('Y-m-d H:i:s') . ' - ' . $logUrl . PHP_EOL, FILE_APPEND);
        /*
        curl_setopt_array($curl, array(
            CURLOPT_URL            => $logUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payment->get(),
            CURLOPT_VERBOSE        => true,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_TIMEOUT        => 60,
            CURLOPT_SSL_VERIFYPEER => false
        ));

	    
	    
		$answer = curl_exec($curl);
		
		$error  = curl_error($curl);
		
		if ( !empty($error) )
			//throw new MInB_Exception($error, 10);
			
		
		curl_close($curl);
		
		return $payment->parse($answer);
		*/
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
		DEFAULT_CURRENCY = 'RUB', //643
		DEFAULT_LANGUAGE = 'RU';
		
	protected $psbTerminal;
	protected $psbMerchant;
	protected $psbMerchName;
	protected $psbComp_1;
	protected $psbComp_2;
	
	// Значения
	const
		NOT_ISSET = 'не указан(а)';
		
	// Операция CreateOrder, GetOrderStatus и т. д.
	protected $operation;
	
	// Сумма платежа
	protected $amount;
	
	// Сумма Возврат
	protected $amountRefund;
	
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
	
	// PSB only 
	// OrderNumber = payment_id = id
	protected $orderNumber;
	
	// PSB only
	protected $clientEmail;
	
	// Добавочные параметры
	protected $addParams = array();
	
	// ON/OFF ShowOperations
	protected $showOperations = true;
	
	// ON/OFF ShowParams
	protected $showParams = true;
	
	// Конструктор
	public function __construct()
	{
		
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
	
	// OrderNumber = payment_id = id
	public function setOrderNumber($id)
	{
		$this->orderNumber = $id;
		
		return $this;
	}
	

	public function getOrderNumber()
	{
		return $this->orderNumber;
	}
	
	public function setClientEmail($email)
	{
		$this->clientEmail = $email;
		
		return $this;
	}
	
	public function getClientEmail()
	{
		return $this->clientEmail;
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
	
		public function getPsbTerminal(){
		return $this->psbTerminal;
	}

	public function setPsbTerminal($psbTerminal){
		$this->psbTerminal = $psbTerminal;
		return $this;
	}

	public function getPsbMerchant(){
		return $this->psbMerchant;
	}

	public function setPsbMerchant($psbMerchant){
		$this->psbMerchant = $psbMerchant;
		return $this;
	}

	public function getPsbMerchName(){
		return $this->psbMerchName;
	}

	public function setPsbMerchName($psbMerchName){
		$this->psbMerchName = $psbMerchName;
		return $this;
	}

	public function getPsbComp_1(){
		return $this->psbComp_1;
	}

	public function setPsbComp_1($psbComp_1){
		$this->psbComp_1 = $psbComp_1;
		return $this;
	}

	public function getPsbComp_2(){
		return $this->psbComp_2;
	}

	public function setPsbComp_2($psbComp_2){
		$this->psbComp_2 = $psbComp_2;
		return $this;
	}
	
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
	
	public function setAmountRefund($amount)
	{
		if ( !$this->isAmount($amount) )
			throw new MInB_Exception(self::EXCEPTION_AMOUNT . $amount, 12);
		
		$this->amountRefund = $amount;
		
		return $this;
	}
	
	
	public function getAmountRefund ()
	{
		return $this->amountRefund;
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
		if ( $type != 'APPROVE' && $type != 'CANCEL' && $type != 'DECLINE' && $type != 'NOTIFY' )
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
		if ( $orderType != 'Purchase' && $orderType != 'PreAuth' )
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
	
	public function logData($data) {
		$logFile = 'log.txt'; // Укажите путь к файлу лога
		$logContent = '[ ' . date('Y-m-d H:i:s') . ' ] ' . var_export($data, true) . PHP_EOL;
		file_put_contents($logFile, $logContent, FILE_APPEND);
	}
		// Возвращает готовый результат запроса
	public function get()
	{
		if ( !$this->isOperation($this->getOperation()) )
			throw new MInB_Exception(self::EXCEPTION_OPERATION . ( ( $this->getOperation() == null ) ? self::NOT_ISSET : $this->getOperation() ), 18);
		
		
		$data = array(
				'terminal' => $this->getPsbTerminal()
		);
		
		switch ( $this->getOperation() )
		{
			case 'CreateOrder':
				if ( !$this->isAmount($this->getAmount()) )
					throw new MInB_Exception(self::EXCEPTION_AMOUNT . self::NOT_ISSET, 20);
				
				$data['trtype'] = '1';
				$data['amount'] = number_format($this->getAmount()/100, 2, '.','');
				$data['currency'] = self::DEFAULT_CURRENCY;
				
				if ( $this->getDescription() == null )
					throw new MInB_Exception(self::EXCEPTION_DESCRIPTION . self::NOT_ISSET, 21);
				
				$data['desc'] = $this->getDescription();
				
				$urlsType = array_keys($this->urls);

				if ( $this->getUrl('APPROVE') == null )
					throw new MInB_Exception(self::EXCEPTION_URLVAL . 'APPROVE' . ' ' . self::NOT_ISSET, 22);
				if ( $this->getUrl('NOTIFY') == null )
					throw new MInB_Exception(self::EXCEPTION_URLVAL . 'NOTIFY' . ' ' . self::NOT_ISSET, 22);
					
				$data['backref'] = $this->getUrl('APPROVE');
				$data['notify_url'] =  $this->getUrl('NOTIFY');
				
				$data['language'] = self::DEFAULT_LANGUAGE;
				
				$data['order'] = hexdec($this->getOrderNumber());
				$data['email'] = $this->getClientEmail();
				if($data['email']) $data['cardholder_notify'] = $data['email'];
				
				
				$data['date_till'] = date("d.m.Y H:i:s", strtotime('+10 minutes'));
				
				$vars = ["amount","currency","terminal","trtype","backref","order"];
				
				
				$this->addPSign($data, $vars);
				//print_r($data);
				$data = array_change_key_case($data,CASE_UPPER);
				// Логируем переменную $data
                $this->logData($data);
				break;
			
			case 'Recurring':
				break;
			case 'Reverse':
				if ( !$this->isAmount($this->getAmount()) )
					throw new MInB_Exception(self::EXCEPTION_AMOUNT . self::NOT_ISSET, 20);
				$data['trtype'] = '22';
				$data['order'] = hexdec($this->getOrderNumber());
				$data['amount'] = number_format($this->getAmount()/100, 2, '.','');
				//$data['notify_url'] =  $this->getUrl('NOTIFY');
				
				$data['currency'] = self::DEFAULT_CURRENCY;
				$data['timestamp'] = gmdate("YmdHis");
				$data['nonce'] = bin2hex(random_bytes(16));
				
				$vars = ["order","amount","currency","org_amount","rrn","int_ref","trtype","terminal","backref","email","timestamp","nonce"];
				$this->addPSign($data, $vars);
				$data = array_change_key_case($data,CASE_UPPER);
				
				break;
			case 'Refund':
			
				$data['trtype'] = '14';
				$data['order'] = hexdec($this->getOrderNumber());
				if ( !$this->isAmount($this->getAmount()) )
					throw new MInB_Exception(self::EXCEPTION_AMOUNT . self::NOT_ISSET, 20);
				
				$data['amount'] = number_format($this->getAmountRefund()/100, 2, '.','');
				$data['currency'] = self::DEFAULT_CURRENCY;
				$data['org_amount'] = number_format($this->getAmount()/100, 2, '.','');
				
				$data['rrn'] = $this->getOrderID();
				$data['int_ref'] = $this->getSessionID();
				$data['notify_url'] =  $this->getUrl('NOTIFY');
				
				$data['timestamp'] = gmdate("YmdHis");
				$data['nonce'] = bin2hex(random_bytes(16));
				
				$vars = ["order","amount","currency","org_amount","rrn","int_ref","trtype","terminal","backref","email","timestamp","nonce"];
				
				$this->addPSign($data, $vars);
				$data = array_change_key_case($data,CASE_UPPER);
				
				break;
			case 'Completion':
				$data['trtype'] = '21';
				$data['order'] = hexdec($this->getOrderNumber());
				if ( !$this->isAmount($this->getAmount()) )
					throw new MInB_Exception(self::EXCEPTION_AMOUNT . self::NOT_ISSET, 20);
				
				$data['amount'] = number_format($this->getAmount()/100, 2, '.','');
				$data['currency'] = self::DEFAULT_CURRENCY;
				$data['org_amount'] = number_format($this->getAmount()/100, 2, '.','');
				
				$data['rrn'] = $this->getOrderID();
				$data['int_ref'] = $this->getSessionID();
				$data['notify_url'] =  $this->getUrl('NOTIFY');
				
				$data['timestamp'] = gmdate("YmdHis");
				$data['nonce'] = bin2hex(random_bytes(16));
				
				$vars = ["order","amount","currency","org_amount","rrn","int_ref","trtype","terminal","backref","email","timestamp","nonce"];
				$this->addPSign($data, $vars);
				$data = array_change_key_case($data,CASE_UPPER);
				
				break;
			
			case 'GetOrderStatus':
			case 'GetOrderInformation':
				/*
				if ( $this->getOrderNumber() == null )
					throw new MInB_Exception(self::EXCEPTION_ORDERID . self::NOT_ISSET, 24);
				*/
				$data['trtype'] = '1';
				$data['order'] = hexdec($this->getOrderNumber());
				if($this->getPsbMerchName()) $data['merch_name'] = $this->getPsbMerchName();
				if($this->getPsbMerchant()) $data['merchant'] = $this->getPsbMerchant();
				$data['timestamp'] = gmdate("YmdHis");
				$data['nonce'] = bin2hex(random_bytes(16));
				$vars = ["amount","currency","order","merch_name","merchant","terminal","email","trtype","timestamp","nonce","backref"];
				$this->addPSign($data, $vars);
				$data = array_change_key_case($data,CASE_UPPER);
				
			break;
		}
		return http_build_query($data);
	}
	
	public function addPSign(&$data, $vars){
		$comp1 = $this->getPsbComp_1();
		$comp2 = $this->getPsbComp_2();
		$string = '';
		foreach ($vars as $param) {
			 if(isset($data[$param]) && strlen($data[$param]) != 0){
			 $string .= strlen($data[$param]) . $data[$param];
			 } else {
				 $string .= "-";
			 }
		}
		$key = strtoupper(implode(unpack("H32",pack("H32",$comp1) ^ pack("H32",$comp2))));
		$data['p_sign'] = strtoupper(hash_hmac('sha256', $string, pack('H*', $key)));
	}
	
	// Возвращает готовый результат ответа
	public function parse($answer)
	{
		$answer = json_decode($answer, true);
		if($this->getOperation() == 'CreateOrder')
			$result = array(
				'OrderNumber'   => $this->getOrderNumber(),
				'URL'       => $answer['REF']
			);

		else
			$result = $answer;

		
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
	$twpg = MInB_TWPG::Instance();

	if ( $params['params']['status'] != 0 )
	{
		MInB_log(
			$id,
			'Попытка подтвердить платеж с некорректным статусом: ' . $params['params']['status'],
			'MInB_approvePayment'
		);
	
		return false;
	}
	
	$answer = $twpg->client()->getAnswer(
		$twpg->payment()
			->setOperation('GetOrderStatus')
			->setOrderNumber($id)
	);
	
	if ( !isset($answer) )
	{
		MInB_log(
			$id,
			'В ответ от TWPG не получено данных',
			'MInB_approvePayment'
		);
	
		return false;
	}

	if ((int) $answer['RESULT'] != 0) {
		MInB_log(
			$id,
			'В ответ от банка получен RESULT не равный "0", RESULT: ' . $answer['RESULT'],
			'MInB_approvePayment'
		);
		
		return false;	
	}

	if (strcasecmp($answer['RC'],'00') != 0) {
		MInB_log(
			$id,
			'В ответ от банка получен RC не равный "00", RC: ' . $answer['RC'].', RCTEXT: ' . $answer['RCTEXT'],
			'MInB_approvePayment'
		);
		
		return false;	
	}


	if ( $params['params']['status'] == 1 ||  $params['params']['status'] == 5 ||  $params['params']['status'] == 6)
	{
		MInB_log(
			$id,
			'Повторный запрос статуса платежа: ' . $answer['RESULT'],
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
		'Статус платежа подтвержден: ' . $params['params']['status'] . ' => ' . 1,
		'MInB_approvePayment'
	);
	
	$db = MInB_Database::Instance();
	
	$db->query('
		UPDATE
			`minbank_payments`
		SET
			`paidDate`=:payDate,
			`status`=:status,
			`paidAmount`=:amount,
			`ip`=:ip,
			`order_id`=:order_id,
			`session_id`=:session_id
		WHERE
			`payment_id`=:id
	', array(':payDate'=>date( 'Y-m-d H:i:s', strtotime('+4 hours', strtotime($answer['TIMESTAMP']))),  ':status' => 1, ':id' => $id, ':amount' => $params['params']['startAmount'], ':ip'=>MInB_IP(), ':order_id' => $answer['RRN'], ':session_id' => $answer['INT_REF'])
	);
		
		
	return array(
		'twpg_answer' => $answer
	);
}

// Подтверждение платежа
function MInB_approvePaymentFromNotification($id, $answer)
{
	if ( !checkPSign($id, $answer) ) {
		MInB_log(
			$id,
			'В ответ от банка получен PSign неверный',
			'MInB_approvePaymentFromNotification'
		);
		
		return false;
	}
	
	if ((int) $answer['result'] != 0) {
		MInB_log(
			$id,
			'В ответ от банка получен RESULT не равный "0", RESULT: ' . $answer['result'],
			'MInB_approvePaymentFromNotification'
		);
		
		// return false;	
	}

	if (strcasecmp($answer['rc'],'00') != 0) {
		MInB_log(
			$id,
			'В ответ от банка получен RC не равный "00", RC: ' . $answer['result'].', RCTEXT: ' . $answer['rctext'],
			'MInB_approvePaymentFromNotification'
		);
		
		// return false;	
	}
	$params = MInB_getPayment($id, true, ['ORDER']);
	if ((int)$answer['result'] == 0 && strcasecmp($answer['rc'],'00') == 0) {
		MInB_log(
			$id,
			'Статус платежа подтвержден: ' . $params['params']['status'] . ' => ' . 1,
			'MInB_approvePaymentFromNotification'
		);

		$db = MInB_Database::Instance();
		$db->query('
			UPDATE
				`minbank_payments`
			SET
				`paidDate`=:payDate,
				`status`=:status,
				`paidAmount`=:amount,
				`order_id`=:order_id,
				`session_id`=:session_id
			WHERE
				`payment_id`=:id
		', array(':payDate'=>date( 'Y-m-d H:i:s', strtotime('+3 hours', strtotime($answer['timestamp']))),  ':status' => 1, ':id' => $id, ':amount' => $answer['amount'] * 100, ':order_id' => $answer['rrn'], ':session_id' => $answer['int_ref'])
		);
		MInB_updatePaymentOptions($id,array('procDate'=>date( 'Y-m-d', strtotime('+3 hours', strtotime($answer['timestamp']))), 'bookingMailSent' => 'ready'), false);
		
	}
	/*
	elseif ((int)$answer['result'] == 2 || (int)$answer['result'] == 21) {
		MInB_log(
			$id,
			'Статус платежа подтвержден: ' . $params['params']['status'] . ' => ' . 4,
			'MInB_approvePaymentFromNotification'
		);

		$db = MInB_Database::Instance();
		$db->query('
			UPDATE
				`minbank_payments`
			SET
				`status`=:status,
				`order_id`=:order_id,
				`session_id`=:session_id
			WHERE
				`payment_id`=:id
		', array(':status' => 4, ':id' => $id, ':order_id' => $answer['rrn'], ':session_id' => $answer['int_ref'])
		);
	}
	*/
	
}


function checkPSign($id, $params){
	$payment = MInB_getPayment($id, true, array('type'));
	
	$type = $payment['options']['vidObuchSt'];

	$comp1 = PSB_COMP_1;
	$comp2 = PSB_COMP_2;
		
	$vars = ["amount","currency","order","merch_name","merchant","terminal","email","trtype","timestamp","nonce","backref" ,"result","rc","rctext","authcode","rrn","int_ref"];
	$string = '';
	foreach ($vars as $param){
		if(isset($params[$param]) && strlen($params[$param]) != 0){
			$string .= strlen($params[$param]) . $params[$param];
		} else {
			$string .= "-";
		}
	}
	$key = strtoupper(implode(unpack("H32",pack("H32",$comp1) ^ pack("H32",$comp2))));
	$sign = strtoupper(hash_hmac('sha256', $string, pack('H*', $key)));
	return (strcasecmp($params['p_sign'],$sign) == 0);
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
		'MInB_cancelPayment',
		'Платеж отменен'
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
		'MInB_completionPayment',
		'Платеж подтвержден банком. Статус: ' . MInB_convertPaymentStatus(6)
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

// Создание платежа
function MInB_createPayment($amount, $options = array())
{
    // Преобразуем сумму
    $parsedAmount = MInB_Environment::ParseAmount($amount);

    // Используем bookingID, переданный в опциях
    $id = $options['bookingID'];

    // Работа с базой данных
    $db = MInB_Database::Instance();
    $db->query('
        UPDATE
            `minbank_payments`
        SET
            `checkedDate` = NOW()
        WHERE
            `payment_id` = :payment_id
    ', array(':payment_id' => $id));

    // Логируем создание платежа
    MInB_log(
        $id,
        'Создан платеж на сумму: ' . $amount . ' => ' . $parsedAmount,
        'MInB_createPayment',
        'Создан заказ на сумму: ' . $amount . ' руб. '
    );

    // Если переданы дополнительные опции, обновляем платеж
    if (!empty($options)) {
        MInB_updatePaymentOptions($id, $options);
    }

    // Возвращаем массив с payment_id
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
		'MInB_declinePayment',
		'Платеж отказан банком'
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
		'MInB_deletePayment',
		'Неоплаченный заказ удален'
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
				`ip`,
				`checkedDate`
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
		
		if ( MInB_SetClientById($id) )
		{
			$twpg = MInB_TWPG::Instance();
			$answer = $twpg->client()->getAnswer(
			$twpg->payment()
				->setOperation('GetOrderStatus')
				->setOrderNumber($id)
			);
			
			if ( !isset($answer) )
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
	if ( !MInB_existsPayment($id) )
		return false;
		
	$db = MInB_Database::Instance();
	$result = array();
	
	$sql = '
		SELECT
			`payment_id`,
			`date`,
			`location`,
			`text`,
			`easy_text`,
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
// Здесь упорядочивание по номеру заказа а не по дате оплаты
function MInB_getPayments($params=true, $options=array(),$first=false, $size=false, $status=false, $dateBegin = false, $dateEnd = false)
{
	$db = MInB_Database::Instance();

	// ********************	
	$start = microtime(true);
	$m = memory_get_usage(); 
	// ********************	

	$result = array();
	
	$mod_where=$mod_lim='';

	if (!is_array($status)) $status = array ($status);

	foreach ($status as $sta)
		if ($sta!==false && is_numeric($sta) && $sta>=0 && $sta<=9)
			$mod_where .= (!empty($mod_where))? " OR `status`=$sta":" WHERE (`status`=$sta";
	$mod_where .= (!empty($mod_where))? ')':'';

	if ($dateBegin != false) 
		{
		$mod_where .= (!empty($mod_where))? " AND":" WHERE";	
		$mod_where .= " DATE(`createdDate`)>='".date('Y-m-d',strtotime($dateBegin))."'";
		}
		
	if ($dateEnd != false) 
		{
		$mod_where .= (!empty($mod_where))? " AND":" WHERE";	
		$mod_where .= " DATE(`createdDate`)<='".date('Y-m-d',strtotime($dateEnd))."'";
		}	
		
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
				`payment_id` ASC
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
		
	if ($options === false || !is_array($options)) {
		$options = []; // Преобразуем false или некорректный ввод в пустой массив
	}
	
	foreach ($options as &$val) {
		$val = '"' . $val . '"';
	}
	
	$opt_list = implode(',', $options);
	
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
			' . $mod_where . '
			ORDER BY
				`payment_id` ASC				
			' . $mod_lim . '
			)
			AS `tt`
		ON 
			`t`.`payment_id` = `tt`.`payment_id`
		WHERE
			`key` IN (' . $opt_list . ')
	', array());
	
	while ($row = $stmt->fetch()) {
		$result[$row['payment_id']]['options'][$row['key']] = $row['value'];
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
			`easy_text`,
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
  return preg_match('/^[0-9A-Za-z\-\. _]+$/u', $id);

}

// Логирование информации
function MInB_log($payment_id, $text, $location = 'unknown', $easy_text='')
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
			`easy_text`,
			`ip`,
			`browser`
		)
		VALUES
		(
			:payment_id,
			NOW(),
			:location,
			:text,
			:easy_text,
			:ip,
			:browser
		)
	', array(':payment_id' => $payment_id, ':location' => $location, ':text' => $text, ':easy_text' => $easy_text, ':ip' => MInB_IP(), ':browser' => MInB_browser()));
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
		
	$paymentCredits = MInB_getPayment($id);
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
			->setUrl('NOTIFY', $params['notifyUrl'])
			->setOrderType($params['orderType'])
			->setOperation('CreateOrder')
			->setClientEmail($params['email'])
			->setOrderNumber($id)
	);
	
	if ( !isset($answer['URL']) )
	{
		MInB_log(
			$id,
			'В ответ от TWPG не получено поле REF',
			'MInB_processPayment'
		);
	
		return false;
	}
	
	MInB_log(
		$id,
		'Получены URL: ' . $answer['URL'],
		'MInB_processPayment'
	);
	
	if ( !empty($options) )
		MInB_updatePaymentOptions($id, $options);
		
	$url = $answer['URL'];
	
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
function MInB_refundPayment($id, $amount = false, $notifyUrl)
{
	if ( !MInB_existsPayment($id, 1) && !MInB_existsPayment($id, 6) )
		return false;
	
	// Установить клиента по id 
	if (!MInB_SetClientById($id)) return false;
		
	$params = MInB_getPayment($id);
	$twpg = MInB_TWPG::Instance();
	$db = MInB_Database::Instance();
	
	$amount = ( $amount !== false ) ? MInB_Environment::ParseAmount($amount) : intVal($params['params']['startAmount']);
	
	$answer = $twpg->client()->getAnswer(
		$twpg->payment()
			->setOrderNumber($id)
			->setOrderID($params['params']['order_id'])
			->setSessionID($params['params']['session_id'])
			->setOperation('Refund')
			->setAmountRefund((int) $amount)
			->setUrl('NOTIFY', $notifyUrl)
			->setAmount((int) $params['params']['paidAmount'])
	);
	
	if ( !isset($answer['RESULT']) )
	{
		MInB_log(
			$id,
			'От TWPG не получено поле RESULT',
			'MInB_refundPayment'
		);
	
		return false;
	}
	
	if ( (int)$answer['RESULT']  != 0 )
	{
		MInB_log(
			$id,
			'Поле RESULT отлично от 0: ' . $answer['RESULT'],
			'MInB_refundPayment'
		);
		
		MInB_log(
			$id,
			'Ответ: ' . json_encode($answer),
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
		'MInB_refundPayment',
		'Возращены средства в размере: ' . $amount/100 . ' руб. '
	);
	
	return true;
}

// Возращение средств по платежу
function MInB_reversePayment($id)
{
	if ( !MInB_existsPayment($id, 1) && !MInB_existsPayment($id, 6) )
		return false;
		
	$params = MInB_getPayment($id);
	$twpg = MInB_TWPG::Instance();
	$db = MInB_Database::Instance();

	$amount = intVal($params['params']['startAmount']);
	
	$answer = $twpg->client()->getAnswer(
		$twpg->payment()
			->setOrderID($params['params']['order_id'])
			->setSessionID($params['params']['session_id'])
			->setOperation('Reverse')
	);
	
	if ( !isset($answer['Status']) )
	{
		MInB_log(
			$id,
			'От TWPG не получено поле Status',
			'MInB_reversePayment'
		);
	
		return false;
	}
	
	if ( $answer['Status'] != '00' )
	{
		MInB_log(
			$id,
			'Поле Status отлично от 00: ' . $answer['Status'],
			'MInB_reversePayment'
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
		'MInB_reversePayment',
		'Возращены средства в размере: ' . $amount/100 . ' руб. '
	);
	
	return true;
}

// Обновление данных платежа
function MInB_updatePaymentOptions($id, $options = array(), $log = true)
{
	
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
		'MInB_updatePaymentOptions',
		'Записаны новые параметры заказа'
		);
	}
}

// Обновление данных платежа
function MInB_updatePaymentParams($id, $params = array())
{
	if ( !MInB_existsPayment($id, 0) && !MInB_existsPayment($id, 5) )
		return false;
		
	$db = MInB_Database::Instance();
	
	if ( !empty($params) )
	{
		$allowed = array('refundAmount', 'startAmount');
	
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
		
			if ( $key == 'refundAmount' ||  $key == 'startAmount')
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

// Получение статуса платежа
function MInB_getPaymentStatus($id)
{
    if (!MInB_existsPayment($id)) {
        return false;
    }

    $result = MInB_getPayment($id, true, ['ORDER']);
    $result['twpg_answer'] = array();

    if (MInB_SetClientById($id)) {
        $twpg = MInB_TWPG::Instance();
        $answer = $twpg->client()->getAnswer(
            $twpg->payment()
                ->setOperation('GetOrderStatus')
                ->setOrderNumber($id)
        );

        // Проверяем, существует ли RCTEXT и не является ли оно пустым
        if (isset($answer['RCTEXT']) && !empty($answer['RCTEXT'])) {
            MInB_log(
                $id,
                'Запрос статуса платежа. Статус: ' . $answer['RCTEXT'],
                'MInB_getPaymentStatus'
            );
        }

        if (isset($answer)) {
            $result['twpg_answer'] = $answer;
        }
    }

    return $result;
}


// Установить клиента по id
function MInB_SetClientById($id)
{
	$twpg = MInB_TWPG::Instance();
	
	if (true)
		{
		// Настроим соединение с платежной системой
		$twpg->payment()->setPsbTerminal(PSB_TERMINAL_1)
						->setPsbMerchant(PSB_MERCHANT_1)	
						->setPsbMerchName(PSB_MERCH_NAME_1)	
						->setPsbComp_1(PSB_COMP_1_1)
						->setPsbComp_2(PSB_COMP_2_1);		
		return true;
		}
		
	return false;
}
if( !function_exists('random_bytes') )
{
    function random_bytes($length = 6)
    {
        $characters = '0123456789';
        $characters_length = strlen($characters);
        $output = '';
        for ($i = 0; $i < $length; $i++)
            $output .= $characters[rand(0, $characters_length - 1)];

        return $output;
    }
}