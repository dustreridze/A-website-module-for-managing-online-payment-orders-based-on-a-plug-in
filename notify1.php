<?php
require "MInB_booking/operate.php";
// Подключим функционал коробочного решения
MInB_booking_boxInit();
$id = MInB_get('id');

if (isset($_POST['P_SIGN']) && isset($id) && MInB_existsPayment($id)) {
	foreach ($_POST as $key => $param){
		MInB_log(
				$id,
				$key . ' : '. $param,
				'notify.php'
			);
	}
	// MInB_approvePaymentFromNotification($id, array_change_key_case($_POST, CASE_LOWER));
}