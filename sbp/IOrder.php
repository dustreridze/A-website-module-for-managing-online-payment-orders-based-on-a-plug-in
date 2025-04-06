<?php
//IOrder.php
interface IOrder{
    public static function create(): self;
    public function createForCreate($orderArray): static;
    public function createForGet($orderArray): static;
    public function createForRefund($orderArray): static;
    public function toArray(): array;
    public function createForPay($orderArray): static;
}