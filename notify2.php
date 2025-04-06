<?php
require "MInB_booking/operate.php";

// Подключим функционал коробочного решения
MInB_booking_boxInit();
$id = MInB_get('id');
file_put_contents('log_post.txt', '');
// Логирование в файл
if (isset($_POST['P_SIGN']) && isset($id) && MInB_existsPayment($id)) {
    // Открываем файл для записи (добавляем данные в конец файла)
    $logFile = 'log_post.txt';
    
    // Формируем строку для логирования
    $logData = "[" . date('Y-m-d H:i:s') . "] Запрос для ID: $id\n";
    foreach ($_POST as $key => $param) {
        $logData .= "$key : $param\n";
        
        // Логируем в систему (если нужно)
        MInB_log(
            $id,
            $key . ' : ' . $param,
            'notify.php'
        );
    }
    $logData .= "-------------------------\n";
    
    // Записываем данные в файл
    file_put_contents($logFile, $logData, FILE_APPEND);
    
    // Одобрение платежа (если нужно)
    // MInB_approvePaymentFromNotification($id, array_change_key_case($_POST, CASE_LOWER));
}