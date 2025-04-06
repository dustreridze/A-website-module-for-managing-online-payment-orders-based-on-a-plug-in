<?php
// OrderOrchestration.php
require "IOrder.php";
require "IReqest.php";
require "IDAO.php";
require "OperationType.php";
require "OrderFields.php";

date_default_timezone_set('Europe/Moscow');

define ("SECTOR_NUMBER", value: 7106);
define ("SECTOR_SECTRET", "8X26dRQ1");

define ('BASE_URL',"https://test.paygine.com/");
define ("CREATE_URL", "webapi/Register");
define ("GET_URL", "webapi/Order");
define ("REFUND_URL", "webapi/Reverse");
define ("PAY_URL", value: "webapi/Purchase");

define("HOST_NAME", 'localhost');
define("DB_NAME", 'u2921074_konteineroff');
define ("USER_NAME", "u2921074_root");
define ("PASSWORD", "200348)))");

define('DB_HOST', 'localhost');
define('DB_NAME', 'u2921074_konteineroff');
define('DB_USER', 'u2921074_root');
define('DB_PASSWORD', '200348)))');
class OrderOrchestration {
    private PDO $pdo;
    private string $classOrder;
    private string $classReqest;
    private string $classDAO;
    private string $classDAOOptiopns;
    private string $url;
    private IOrder $order;
    private OperationType $operation;

    public function __construct(OperationType $type, string $json, string $classOrder, string $classReqest, string $classDAO, string $classDAOOptiopns) {
        if (class_exists($classOrder) && in_array(IOrder::class, class_implements($classOrder))) {
            if (class_exists($classReqest) && in_array(IReqest::class, class_implements($classReqest))) {
                if (class_exists($classDAO) && in_array(IDAO::class, class_implements($classDAO))) {
                    if (class_exists($classDAOOptiopns) && in_array(IDAO::class, class_implements($classDAOOptiopns))) {
                        $this->pdo = new PDO(
                            'mysql:host=' . HOST_NAME . ';dbname=' . DB_NAME, 
                            USER_NAME, 
                            PASSWORD, 
                            array(PDO::ATTR_PERSISTENT => true)
                        );
                        $this->classOrder = $classOrder;
                        $this->classReqest = $classReqest;
                        $this->classDAO = $classDAO;
                        $this->operation = $type;
                        $this->classDAOOptiopns = $classDAOOptiopns;
                        $orderArray = OrderOrchestration::parseDataToArrayJson($json);
                        switch ($type) {
                            case OperationType::Create:
                                $this->url = BASE_URL . CREATE_URL;
                                $this->order = $classOrder::create()->createForCreate($orderArray);
                                break;
                            case OperationType::GetOrder:
                                $this->url = BASE_URL . GET_URL;
                                $this->order = $classOrder::create()->createForGet($orderArray);
                                break;
                            case OperationType::Refund:
                                $this->url = BASE_URL . REFUND_URL;
                                $this->order = $classOrder::create()->createForRefund($orderArray);
                                break;
                            default:
                                die("Неверно указана операция!");
                        }
                    } else {
                        throw new InvalidArgumentException("Класс $classDAOOptiopns не реализует интерфейс IDAO");
                    }
                } else {
                    throw new InvalidArgumentException("Класс $classDAO не реализует интерфейс IDAO");
                }
            } else {
                throw new InvalidArgumentException("Класс $classReqest не реализует интерфейс IReqest");
            }
        } else {
            throw new InvalidArgumentException("Класс $classOrder не реализует интерфейс IOrder");
        }
    }

    private function getDataForPayLink(array $responce) {
        $data = $this->classOrder::create()->createForPay($responce)->toArray();
        return $data;
    }

    public function processOrder(): mixed {
        try {
            $data = $this->order->toArray();
            $json = $data["json"];
            unset($data["json"]);

            // Создаем объекты DAO
            $dbhOp = new $this->classDAOOptiopns($this->pdo);
            $dbh = new $this->classDAO($this->pdo);
            $request = new $this->classReqest($this->url, $data);

            // Выполняем запрос
            $responce = $request->doRequest();
            //echo print_r($responce, true);

            if (array_key_exists("error", $responce)) {
                throw new Exception($responce["error"]);
            }

            // Обработка операций
            switch ($this->operation) {
                case OperationType::Create:
                    if (!$dbh->create($responce)) {
                        $dbh->update($responce);
                        $dbhOp->delete($data["reference"]);
                    }
                    OrderOrchestration::writeJsonInBD($json, $dbhOp);
                    $qrLink = BASE_URL . PAY_URL . '?' . http_build_query($this->getDataForPayLink($responce));
                    MInB_log(
                        $data["reference"],
                        'Сгенерирован payment_url: ' . $qrLink,
                        'MInB_processPayment'
                    );
                    return $qrLink;
                case OperationType::GetOrder:
                    $dbh->update($responce);
                    return $responce;
                case OperationType::Refund:
                    $dbh->update($responce);
                    return $responce;
                default:
                    throw new Exception("Действие не определено");
            }
        } catch (Exception $e) {
            echo $e->getMessage();
        }
    }

    // Функция для обработки ответа от банка
    
    private static function writeJsonInBD(string $json, $dbhOp): void {
        $data = json_decode($json, true);
        foreach (OrderFields::cases() as $key) {
            $res = [];
            $res["payment_id"] = $data["СтрокаGUID"];
            $res["key"] = $key->value;
            $res["value"] = $data[$key->name];

            $dbhOp->create($res);
        }
        $res["payment_id"] = $data["СтрокаGUID"];
        //$res["key"] = "TOVAR";
        //$res["value"] = serialize($data["Товары"]);

        $dbhOp->create($res);
    }

    private static function parseDataToArrayJson(string $json): array {
        $data = json_decode($json, true);
        $orderArray["json"] = $json;
        $orderArray["amount"] = $data["СуммаДокумента"] * 100;
        $orderArray["currency"] = 643;
        $orderArray["description"] = "";
        foreach ($data["Товары"] as $order) {
            $orderArray["description"] = $orderArray["description"] . $order["Номенклатура"] . '; ';
        }
        $orderArray["ps"] = 11;
        $orderArray["reference"] = $data["СтрокаGUID"];
        $orderArray["fio"] = "Индивидуальный предприниматель Лавриненко Иван Евгеньевич";
        $orderArray["phone"] = "+71234567890";
        $dateTime = new DateTime($data["СрокДействия"]);
        $dateNow = new DateTime();
        $life_period = 24 * 60 * 60; // Это можно рассчитать, если нужно
        $orderArray["life_period"] = $life_period;
        $orderArray["lang"] = "RU";
        $orderArray["url"] = "https://".$_SERVER['SERVER_NAME']. '/konteineroff/index.php' .'?bookingWord=' .$data['Номер'] ;
        $orderArray["failurl"] = "https://".$_SERVER['SERVER_NAME']. '/konteineroff/index.php' .'?bookingWord=' .$data['Номер'] ;
        $orderArray["email"] = "rtg.r04@mail.ru";  //ИЗМЕНИТЬ!!!!!!!!!!!!!!!!!!!!!!!!!!!
        return $orderArray;
    }

    public function getOrder(): Order {
        return $this->order;
    }
}
?>




