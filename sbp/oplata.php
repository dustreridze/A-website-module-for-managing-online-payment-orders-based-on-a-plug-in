<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Получаем данные из POST
    $summa = isset($_POST['SUMMA']) ? $_POST['SUMMA'] : null;
    $guid = isset($_POST['GUID']) ? $_POST['GUID'] : null;
    // URL для отправки запроса
    $url = 'https://oplata-test.ru/konteineroff/sbp/backend_redirect.php';
    file_put_contents('loooooooooooooooog.txt', "SUMMA: $summa, GUID: $guid\n", FILE_APPEND);
    // Задержка в 30 секунд
    sleep(15);
    // Формирование XML-запроса
    $xmlData = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
    <operation>
        <order_id>9814113</order_id>
        <order_state>COMPLETED</order_state>
        <reference>$guid</reference>
        <id>918831</id>
        <date>2025.01.07 00:23:19</date>
        <type>COMPLETE</type>
        <state>APPROVED</state>
        <reason_code>1</reason_code>
        <message>Successful financial transaction</message>
        <name>cardholder name</name>
        <pan>676531******0129</pan>
        <email>mail@somesite.com</email>
        <amount>$summa</amount>
        <currency>643</currency>
        <approval_code>739258</approval_code>
        <signature>OGRiNGEyNGI0ZjkzOGY5ODVjY2Q5ZTE4YzE1MzM4YjQ=</signature>
    </operation>";
    
    // Инициализация cURL
    $ch = curl_init();
    
    // Настройки cURL
    curl_setopt($ch, CURLOPT_URL, $url); // Установка URL
    curl_setopt($ch, CURLOPT_POST, true); // Указываем, что это POST-запрос
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/xml', // Заголовок, указывающий тип данных
        'Content-Length: ' . strlen($xmlData) // Длина содержимого
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $xmlData); // Тело запроса
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Получение ответа как строки
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // Отключение проверки SSL-сертификата (если тестовый сервер)
    
    // Отправка запроса и получение ответа
    $response = curl_exec($ch);
    // Завершение сеанса cURL
    curl_close($ch);
    echo "Сумма: $summa, GUID: $guid";
}
?>