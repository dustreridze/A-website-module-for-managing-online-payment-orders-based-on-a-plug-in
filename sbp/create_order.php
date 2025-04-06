<?php

// Подключаем файл MinB.php, который находится в protected/library
require_once 'operate.php';

error_reporting(E_ALL); // Показывать все ошибки
ini_set('display_errors', 1); // Включить отображение ошибок

// Проверка, что запрос использует POST метод
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Получаем параметры из GET запроса
    $summa = isset($_GET['SUMMA']) ? $_GET['SUMMA'] : null;
    $pay_id = isset($_GET['PAY_ID']) ? $_GET['PAY_ID'] : null;
    $product_description = isset($_GET['PRODUCT_DESCRIPTION']) ? $_GET['PRODUCT_DESCRIPTION'] : null;
    $user_email = $_GET['USER_EMAIL'];
    // Проверка на обязательные параметры
    if ($summa && $pay_id && $product_description) {
        $GUID = uniqid('order_', false);
        
        
         
    
        $url="http://oplata-test.ru/konteineroff/sbp/oplata.php";
        $data = [
        'SUMMA' => $summa,
        'GUID' => $GUID,
        ];
        
        // Инициализация cURL
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 1); // Установите тайм-аут, чтобы не ждать ответа
        curl_setopt($ch, CURLOPT_HEADER, false);
        
        // Выполнение запроса
        curl_exec($ch);
        curl_close($ch);
    
    
    
        
        
        
        $booking = MInB_createPayment($summa, array(
            'bookingWord'        => $pay_id,
            'bookingName'        => $user_email,
            'bookingDate'        => date('d.m.Y'),
            'bookingDescription' => $product_description,
            'bookingID'          => $GUID,
            'email'              => $user_email,
            'bookingMailSent'    =>'0',
            'paymentmethod'      =>'SBP',
        ),$pay_id);
                // Формируем JSON объект, который будет использоваться для дальнейшей обработки
        $json_data = [
            "СтрокаGUID" => $GUID, // Генерация уникального идентификатора
            "Номер" => $pay_id, // Номер заказа (PAY_ID)
            "Операция" => "Реализация", // Операция, может быть указана по вашему усмотрению
            "СрокДействия" => "01.01.0001 00:00:00", // Дата действия (по умолчанию)
            "ЦенаВключаетНДС" => true, // Цена включает НДС
            "СуммаДокумента" => (float) $summa, // Сумма заказа
            "Товары" => []
        ];
        // Разбираем описание товаров (предположим, что оно передается в формате строки)
        // Применяем регулярное выражение для разбора каждого товара с его параметрами
        $items = [];
        if (preg_match_all('/(.+?) \(Количество:\s*(\d+),\s*Цена:\s*(\d+(\.\d+)?)\)/', $product_description, $matches, PREG_SET_ORDER)) {

            // Перебираем найденные товары и добавляем их в JSON
            foreach ($matches as $match) {
                $item_name = $match[1]; // Название товара
                $quantity = (int) $match[2]; // Количество
                $price = (float) $match[3]; // Цена

                // Добавляем товар в массив Товары
                $json_data['Товары'][] = [
                    "Номенклатура" => $item_name, // Название товара
                    "КоличествоУпаковок" => $quantity, // Количество упаковок
                    "Цена" => $price, // Цена товара
                    "Сумма" => $price * $quantity, // Сумма товара
                    "СтавкаНДС" => "20%", // Ставка НДС
                    "СуммаНДС" => round(($price * $quantity) * 0.2, 2), // Сумма НДС (пример)
                    "СуммаСНДС" => $price, // Сумма с НДС
                    "СуммаСкидки" => 0.00 // Сумма скидки, если есть (по умолчанию 0)
                ];
            }
        } else {
            // Если товары не были найдены
            echo "Ошибка: Товары не найдены или неправильный формат!";
            exit;
        }
        // Преобразуем массив в JSON строку
        $json_string = json_encode($json_data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        file_put_contents('received_data.txt', $product_description . PHP_EOL, FILE_APPEND);
        file_put_contents('received_data.txt', $json_string . PHP_EOL, FILE_APPEND);
        // Подключаем необходимые файлы для обработки заказа
        require "OrderOrchestration.php";
        require "Order.php";
        require "Reqest.php";
        require "MinbankPaymentsDAO.php";
        require "MinbankPaymentsOptionsDAO.php";

        // Создаем объект оркестрации заказа с данными
        $orchestr = new OrderOrchestration(
            OperationType::Create, // Тип операции
            $json_string, // JSON данные
            Order::class, // Класс для обработки заказа
            Reqest::class, // Класс запроса
            MinbankPaymentsDAO::class, // Класс для доступа к данным о платежах
            MinbankPaymentsOptionsDAO::class // Класс для опций платежей
        );
        
        // Выполняем обработку заказа
        $result = $orchestr->processOrder();
        MInB_operate_sendmail($GUID,$result);
        // Преобразуем объект Order в массив с помощью toArray()
        echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
        <script type="text/javascript">
            $(function() {
                $("#dialog").html(\'<br/><h4>Выполняется перенаправление, если Вас не переадресовало автоматически, нажмите на эту <a href="' . $result . '"><span style="text-decoration:underline">ссылку</span></a></h4>\');
                $("#dialog").show(); // Убедитесь, что элемент с ID dialog существует
                window.location = "' . $result . '";
            });
        </script>';
    } else {
        // Если обязательные параметры не получены
        echo "Ошибка: Не все параметры переданы!";
    }

} else {
    // Если запрос не POST
    echo "Ошибка: Неправильный метод запроса!";
}
?>
