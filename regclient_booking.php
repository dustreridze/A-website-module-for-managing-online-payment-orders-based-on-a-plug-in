<?php
ob_start();
require_once 'MInB_booking/booking.php';
	
// ��������� ���������� ����������� �������
MInB_booking_boxInit();
$db = MInB_Database::Instance();

$beginDate   = MInB_post('beginDate', '2000-01-01 00:00:00');
$endDate   = MInB_post('endDate', '2100-01-01 00:00:00');

// ������� ������
$stmt = $db->query('
	SELECT
		`payment_id`,
		`paidDate`,
		`paidAmount`,
		`refundAmount`,
		`order_id`,
		`session_id`,
		`ip`
	FROM
		`minbank_payments`
	WHERE
		(`status`=1 OR `status`=6 OR `status`=8)
		AND
		`paidDate` >= :beginDate
		AND
		`paidDate` <= :endDate
', array (':beginDate'=>$beginDate, ':endDate'=>$endDate));

if ( $stmt->rowCount() > 0 )
	while ( $row = $stmt->fetch() )
		$result[$row['payment_id']]['params'] = $row + array('merchant_id' => MINB_BOOKING_MERCHANTID);

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
		WHERE	
			(`status`=1 OR `status`=6 OR `status`=8)
			AND
			`paidDate` >= :beginDate
			AND
			`paidDate` <= :endDate
		)
		AS `tt`
	ON 
		`t`.`payment_id`=`tt`.`payment_id`
', array (':beginDate'=>$beginDate, ':endDate'=>$endDate));

while ($row = $stmt->fetch())
	$result[$row['payment_id']]['options'][$row['key']]=$row['value'];
		
		
// ������� ������
$stmt = $db->query('
	SELECT
		SUM(`paidAmount`),
		SUM(`refundAmount`),
		COUNT(`paidAmount`)
	FROM
		`minbank_payments`
	WHERE
		(`status`=1 OR `status`=6 OR `status`=8)
	');

$row = $stmt->fetch();

// $ssl= MInB_TWPG::Instance()->client()->getSSL();
// $arr = openssl_x509_parse (file_get_contents ($ssl['cert']));

$result[''] =array ('summa' => $row['SUM(`paidAmount`)'], 'refund' => $row['SUM(`refundAmount`)'], 'count' => $row['COUNT(`paidAmount`)'], 'validTo'=>$arr['validTo']);

$cryptkey ='u50aYTmjPv208IHFP3fZ3ede';
//var_dump($result);
$ANS=json_encode($result);
$ANS=base64_encode($ANS ^ str_repeat($cryptkey, ceil(strlen($ANS)/strlen($cryptkey))));
			
ob_end_clean();
echo $ANS;