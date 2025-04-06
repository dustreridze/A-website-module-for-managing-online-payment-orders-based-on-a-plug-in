<?php
//IReqest.php
interface IReqest
{
    public function doRequest(): array;
    public function __construct(string $url, array $data);
}