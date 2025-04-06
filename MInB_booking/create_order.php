<?php
// Подключаем файл с необходимыми функциями
require_once 'operate.php';
// Отключаем отображение ошибок на экране
ini_set('display_errors', 'Off'); 

// Устанавливаем уровень отчетности об ошибках, исключая предупреждения и устаревшие функции
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);
// Получаем данные из GET запроса
$bookingAmount = isset($_GET['SUMMA']) ? $_GET['SUMMA'] : null;
$bookingWord = isset($_GET['PAY_ID']) ? $_GET['PAY_ID'] : null;
$product_description = isset($_GET['PRODUCT_DESCRIPTION']) ? $_GET['PRODUCT_DESCRIPTION'] : null;
$user_email = $_GET['USER_EMAIL'] ?? null;
// Проверяем, что все необходимые параметры были переданы
if ($bookingAmount && $bookingWord) {
    // Преобразуем сумму в целое число (например, в копейки)
    $bookingAmount = floatval($bookingAmount); // Преобразуем в копейки (например, 103500.00 -> 10350000)
    
    // Проверяем, не существует ли уже заказ с таким номером
    $existingOrder = MInB_getPaymentsByOption('bookingWord', $bookingWord);
    
    if (empty($existingOrder)) {
        // Если заказ с таким номером не существует, создаем новый заказ
        $booking = MInB_createPayment($bookingAmount, array(
            'bookingWord'        => $bookingWord,
            'bookingName'        => $user_email,
            'bookingDate'        => date('d.m.Y'),
            'bookingDescription' => $product_description,
            'email'              => $user_email,
            'bookingMailSent'    => '0',
            'paymentmethod'      => 'CARD'
        ),$bookingWord);

        // Проверяем, успешно ли создан заказ
        if (isset($booking['payment_id'])) {
            // Получаем ID заказа и описание
            $bookingId = $booking['payment_id'];
            $description = $booking['bookingDescription'];
            $paymentName = $booking['bookingName'];
            $paymentDate = $booking['bookingDate'];

            // Получаем статус платежа
            $answer = MInB_getPaymentStatus($bookingId);
            if ($answer && isset($answer['params']['createdDate'])) {
                // Создаем URL для подтверждения
                $url = "https://".$_SERVER['SERVER_NAME']. '/konteineroff/index.php' .'?bookingWord=' . $bookingWord ;
                $notifyUrl = 'https://' . $_SERVER['SERVER_NAME'] . '/konteineroff/notify.php?id=' . $bookingId .'&createdDate=' . $answer['params']['createdDate'];
                
                // Процессируем платеж
                $payment = MInB_processPayment($bookingId, array(
                    'description' => 'Оплата заказа ' . $bookingId,
                    'orderType'   => 'Purchase',
                    'notifyUrl'   => $notifyUrl,
                    'approveUrl'  => $url,
                    'declineUrl'  => $url,
                    'cancelUrl'   => $url,
                    'email'       => $user_email // Пример email, замените на актуальное значение
                ));
                
                // Проверяем, существует ли ссылка на оплату
                if (isset($payment['payment_url'])) {
                    MInB_operate_sendmail($bookingId,$payment['payment_url']);
                    // Выводим скрипт для перенаправления
                    echo '<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
                    <script type="text/javascript">
                        $(function() {
                            $("#dialog").html(\'<br/><h4>Выполняется перенаправление, если Вас не переадресовало автоматически, нажмите на эту <a href="' . $payment['payment_url'] . '"><span style="text-decoration:underline">ссылку</span></a></h4>\');
                            $("#dialog").show(); // Убедитесь, что элемент с ID dialog существует
                            window.location = "' . $payment['payment_url'] . '";
                        });
                    </script>';
                    exit(); // Завершаем выполнение скрипта
                } else {
                    echo json_encode(["status" => "error_url", "message" => "Не существует ссылка на оплату"]);
                }
            } else {
                echo json_encode(["status" => "error", "message" => "Не удалось получить информацию о платеже."]);
            }
        } else {
            echo json_encode(["status" => "error", "message" => "Ошибка при создании заказа."]);
        }
    } else {
        echo json_encode(["status" => "error", "message" => "Заказ с номером PAY_ID {$bookingWord} уже существует."]);
    }
} else {    
    echo json_encode(["status" => "error_CREATE_ORDER.PHP", "message" => "Недостаточно данных для создания заказа."]);
}
?>





