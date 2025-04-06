<?
require_once 'OrderOrchestration.php';
require_once "Order.php";
require_once "Reqest.php";
require_once "MinbankPaymentsDAO.php";
require_once "MinbankPaymentsOptionsDAO.php";
require "protected/library/MInB.php";
require_once 'operate.php';
// Основная логика вызова
header('Content-Type: application/json');

$inputXML = file_get_contents('php://input');

libxml_use_internal_errors(true);
$xml = simplexml_load_string($inputXML);

if ($xml === false) {
    echo json_encode(["error" => "Invalid XML format"]);
    http_response_code(400);
    exit;
}

$requestData = json_decode(json_encode($xml), true);

$resultData = array(
    'order_id' => $requestData['order_id'],
    'status' => $requestData['order_state'] == 'COMPLETED'? 1:0,
    'paidDate' => $requestData['date'],
    'reference' =>$requestData['reference'],
    'paidAmount' => $requestData['amount']*100,
    'EMAIL'   		=> $requestData['email'],
    'PAN'   		=> $requestData['pan'],
    'CardHolderName' => $requestData['name'],
    'procDate' => $requestData['date']
);
$logData = "[" . date('Y-m-d H:i:s') . "] " . print_r($resultData, true) . PHP_EOL;
file_put_contents('received_data.txt', $logData, FILE_APPEND);
processBankResponse($resultData);
echo "requedata";
echo $requestData['reference'];
$id = getPAY_ID($requestData['reference']);
echo "id =>       ";
echo $id;
$date = getCreateDate($requestData['reference']);
echo "date = >";
echo $date;
MInB_operate_sendmsg($requestData['reference'],'APPROVED');
//Твой адрес со страницей итогов
echo "https://oplata-test.ru/konteineroff/index.php?bookingWord=" . urlencode($id) . "&createdDate=" . $date ;
/*
echo "<script type=\"text/javascript\">
window.location.replace(\"https://oplata-test.ru/konteineroff/index.php?bookingWord=" . urlencode($id) . "&createdDate=" . urlencode($date) . "\");
</script>";
*/
?> 