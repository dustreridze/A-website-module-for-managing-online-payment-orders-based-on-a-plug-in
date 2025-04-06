<?php
interface IDAO{
    public function create(array $data): bool;
    public function read(string $payment_id): ?array;
    public function update(array $data, ?array $responceDate = null): bool;
    public function delete(string $payment_id): bool;
    public function findByCriteria(array $criteria): array;
}