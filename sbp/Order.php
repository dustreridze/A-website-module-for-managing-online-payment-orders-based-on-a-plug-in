<?php
class Order implements IOrder
//Order.php
{
    private ?int $sector = SECTOR_NUMBER;
    private ?float $amount = null;
    private ?int $currency = null;
    private ?string $description = null;
    private ?string $signature = null;
    private ?string $reference = null;
    private ?string $lang = null;
    private ?int $life_period = null;
    private ?string $url = null;
    private ?string $failurl = null;
    private ?string $email = null;
    private ?string $phone = null;
    private ?string $fio = null;
    private ?int $ps = null;
    private ?string $id = null;
    private ?string $json = null;

    public function __construct(){
    }

    public static function create(): self{
        return new self();
    }

    public function createForCreate($orderArray): static{

        foreach ($this as $key => $value) {
            if (key_exists($key,$orderArray)){
                if ($orderArray[$key]!== null) {
                $this->$key = $orderArray[$key];
                }
            }
        }
        $this->generateSignature(["amount", "currency"]);
        //echo "\n НОВЫЙ ЗАКАЗ:".print_r($this,true)."\n";
        return $this;
    }

    public function createForGet($orderArray): static{
        $this->id = $orderArray["id"];
        $this->reference = $orderArray["reference"];
        $this->generateSignature(["id", "reference"]);
        return $this;
    }

    public function createForRefund($orderArray): static{
        $this->id = $orderArray["id"];
        $this->amount = $orderArray["amount"];
        $this->currency = $orderArray["currency"];
        $this->generateSignature(["id", "amount", "currency"]);
        return $this;
    }

    public function createForPay($orderArray): static{
        $this->id = $orderArray["id"];
        $this->generateSignature(["id"]);
        return $this;
    }

    private function getPaygineSecret() {
        return SECTOR_SECTRET;
    }

    private function generateSignature($fields) {
        $str = $this->sector;
        foreach($fields as $field)
            $str .= $this->$field;
        $str .= $this->getPaygineSecret();
    
        if($str) {
            $hash = md5($str);
            $signature = base64_encode($hash);
        }
        $this->signature = $signature;
    }

    public function toArray(): array
    {
        $result = [];

            foreach ($this as $key => $value) {
                if ($value !== null) {
                    $result[$key] = $value;
                }
            }

        return $result;
    }

}
    