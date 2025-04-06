<?php

class MinbankPaymentsDAO implements IDAO
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    private function existPayment($paymentId): bool{
        $sql = "SELECT COUNT(*) FROM minbank_payments WHERE payment_id = :payment_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':payment_id' => $paymentId]);
        $exists = $stmt->fetchColumn() > 0;
        return $exists;
    }
    /*
    Генерация уникального первичного ключа
    private function generatePaymentId(): string
    {
        do {
            $paymentId = uniqid('', true);
            $sql = "SELECT COUNT(*) FROM minbank_payments WHERE payment_id = :payment_id";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':payment_id' => $paymentId]);
            $exists = $stmt->fetchColumn() > 0;
        } while ($exists);

        return $paymentId;
    }*/

    public function create(array $data): bool
    {
        $data = MinbankPaymentsDAO::convertOrderToData($data);
        //echo "\n НОВЫЙ ЗАКАЗ В DAO КОНВЕРТАЦИЯ:".print_r($data,true)."\n";
        if(!$this->existPayment($data["payment_id"])){
            $sql = "INSERT INTO minbank_payments (
                        payment_id, createdDate, paidDate, exportedDate, status,
                        startAmount, paidAmount, refundAmount, order_id, session_id, ip,
                        checkedDate, sended
                    ) VALUES (
                        :payment_id, :createdDate, :paidDate, :exportedDate, :status,
                        :startAmount, :paidAmount, :refundAmount, :order_id, :session_id, :ip,
                        :checkedDate, :sended
                    )";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':payment_id' => $data['payment_id'],
                ':createdDate' => date("Y-m-d H:i:s") ?? null,
                ':paidDate' => $data['paidDate'] ?? null,
                ':exportedDate' => $data['exportedDate'] ?? null,
                ':status' => $data['status'] ?? 0,
                ':startAmount' => $data['startAmount'] ?? null,
                ':paidAmount' => $data['paidAmount'] ?? 0,
                ':refundAmount' => $data['refundAmount'] ?? 0,
                ':order_id' => $data['order_id'] ?? null,
                ':session_id' => $data['session_id'] ?? null,
                ':ip' => $data['ip'] ?? null,
                ':checkedDate' => $data['checkedDate'] ?? null,
                ':sended' => $data['sended'] ?? null
            ]);
        }
        return false;
    }

    public function read(string $payment_id): ?array
    {
        $sql = "SELECT * FROM minbank_payments WHERE payment_id = :payment_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':payment_id' => $payment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update(array $data, ?array $responceDate = null): bool
    {
        $data = MinbankPaymentsDAO::convertOrderToData($data, null);
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            if ($value){
                $fields[] = "$key = :$key";
                $params[":$key"] = $value;
            }
        }

        $sql = "UPDATE minbank_payments SET " . implode(', ', $fields) . " WHERE payment_id = :payment_id";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete(string $payment_id): bool
    {
        $sql = "DELETE FROM minbank_payments WHERE payment_id = :payment_id";
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([':payment_id' => $payment_id]);
    }

    public function findByCriteria(array $criteria): array
    {
        $conditions = [];
        $params = [];

        if (isset($criteria['status'])) {
            $conditions[] = "status = :status";
            $params[':status'] = $criteria['status'];
        }

        if (isset($criteria['startDate']) && isset($criteria['endDate'])) {
            $conditions[] = "createdDate BETWEEN :startDate AND :endDate";
            $params[':startDate'] = $criteria['startDate'];
            $params[':endDate'] = $criteria['endDate'];
        }

        $sql = "SELECT * FROM minbank_payments";
        if ($conditions) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private static function convertOrderToData(array $order, ?array $responceDate = null): array{
        $data = [];
        $data['payment_id'] = $order['reference'];
        $data['paidDate'] =  $responceDate?  $responceDate['paidDate'] : null;
        $data['exportedDate'] =  $responceDate?  $responceDate['exportedDate'] : null;
        $data['status'] =  $responceDate?  $responceDate['status'] : null;
        $data['startAmount'] =   $order['amount'];
        $data['paidAmount'] =  $responceDate?  $responceDate['paidAmount'] : null;
        $data['refundAmount'] =  $responceDate?  $responceDate['refundAmount'] : null;
        $data['order_id'] = $order['id'];
        $data['session_id'] =  $responceDate?  $responceDate['session_id'] : null;
        $data['ip'] =  $responceDate?  $responceDate['ip'] : null;
        $data['checkedDate'] =  $responceDate?  $responceDate['checkedDate'] : null;
        $data['sended'] =  $responceDate?  $responceDate['sended'] : null;
        return $data;
    }
}

