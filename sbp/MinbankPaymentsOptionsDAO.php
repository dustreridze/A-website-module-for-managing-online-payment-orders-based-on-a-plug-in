<?php

class MinbankPaymentsOptionsDAO implements IDAO
{
    private $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }
    private function existPaymentOption($paymentId, $key): bool{
        $sql = 'SELECT COUNT(*) FROM minbank_payments_options WHERE (payment_id = :payment_id) AND (`key` = :key)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':payment_id' => $paymentId, ':key'=> $key]);
        $exists = $stmt->fetchColumn() > 0;
        return $exists;
    }

    public function create(array $data): bool
    {
        if(!$this->existPaymentOption($data["payment_id"], $data["key"])){
            //echo "\n НОВЫЙ ТОВАР В DAO:".print_r($data,true)."\n";
            $sql = "INSERT INTO minbank_payments_options (
                        payment_id, `key`, `value`
                    ) VALUES (
                        :payment_id, :key, :value
                    )";

            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([
                ':payment_id' => $data['payment_id'],
                ':key' => $data['key'],
                ':value' => $data['value']
            ]);
        }
        else return $this->update($data);
    }

    public function read(string $payment_id): ?array
    {
        $sql = "SELECT * FROM minbank_payments_options WHERE payment_id = :payment_id";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':payment_id' => $payment_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function update(array $data, ?array $responceDate = null): bool
    {
        $fields = [];
        $params = [];

        foreach ($data as $key => $value) {
            if ($value){
                $fields[] = "`$key` = :$key";
                $params[":$key"] = $value;
            }
        }
        $sql = "UPDATE minbank_payments_options SET " . implode(', ', $fields) . " WHERE payment_id = :payment_id AND `key` = :key";
        $stmt = $this->pdo->prepare($sql);

        return $stmt->execute($params);
    }

    public function delete(string $payment_id): bool
    {
        $sql = "DELETE FROM minbank_payments_options WHERE payment_id = :payment_id";
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

        $sql = "SELECT * FROM minbank_payments_options";
        if ($conditions) {
            $sql .= " WHERE " . implode(' AND ', $conditions);
        }

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
