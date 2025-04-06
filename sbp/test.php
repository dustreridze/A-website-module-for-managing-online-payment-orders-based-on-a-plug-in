<?php
require "OrderOrchestration.php";
require "Order.php";
require "Reqest.php";
require "MinbankPaymentsDAO.php";
require "MinbankPaymentsOptionsDAO.php";

// Пример класса с функцией generateSignature1
class PaymentProcessor {
    public $sector = 7106;  // Установлено конкретное значение для $sector
    private $secret = '8X26dRQ1';  // Ваш секретный ключ для подписи
    public $order_id = "97164267";  // Сделано public для доступа
    public $qrc_id = 'BD10003ETT7TJDNU9EN9B139GUA2HGBC';
    public $case_id = 150;

    // Метод для получения секретного ключа
    public function getPaygineSecret() {
        return $this->secret;
    }

    // Метод для генерации подписи
    public function generateSignature1() {
        // Формируем строку str для подписи
        $str = $this->sector . $this->case_id . $this->qrc_id . $this->order_id . $this->getPaygineSecret();  // Используем секретный ключ

        // Генерируем хэш MD5 из строки
        $hash = md5($str);

        // Кодируем хэш в формате Base64
        $signature = base64_encode($hash);

        return $signature;
    }
}

// Инициализация экземпляра класса
$paymentProcessor = new PaymentProcessor();

// Генерация подписи
$signature = $paymentProcessor->generateSignature1();  // Генерация подписи

// Параметры для запроса
$data = [
    'sector' => $paymentProcessor->sector,  // Ваш сектор
    'case_id' => $paymentProcessor->case_id, // case_id
    'qrc_id' => $paymentProcessor->qrc_id,   // qrc_id
    'order_id' => $paymentProcessor->order_id, // order_id
    'signature' => $signature,  // Подпись
];

// Логирование данных запроса
$logData = "POST запрос:\n" . print_r($data, true);
error_log($logData, 3, 'payment_processor.log'); // Путь к лог-файлу

// URL для отправки запроса
$URL = "https://test.paygine.com/test/SBPTestCase";

// Инициализация cURL
$ch = curl_init();

// Установка параметров cURL
curl_setopt($ch, CURLOPT_URL, $URL);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));

// Выполнение запроса
$response = curl_exec($ch);

// Проверка на ошибки
if (curl_errno($ch)) {
    echo "Ошибка cURL: " . curl_error($ch);
} else {
    // Логирование ответа от сервера
    $logResponse = "Ответ от сервера:\n" . $response;
    error_log($logResponse, 3, 'payment_processor.log'); // Путь к лог-файлу

    // Выводим ответ от сервера
    echo "Ответ от сервера: " . $response;
    
    // Разбираем ответ XML
    $xml = simplexml_load_string($response);
    if ($xml && isset($xml->data->nspkLink)) {
        // Получаем qrc_id из ссылки
        preg_match('/\/([^\/]+)$/', $xml->data->nspkLink, $matches);
        $qrc_id = $matches[1];
        echo "qrc_id: " . $qrc_id;
    } else {
        echo "Не удалось извлечь qrc_id.";
    }
}

// Закрытие cURL
curl_close($ch);
?>






   


