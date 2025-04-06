<?php
//OperationType.php
enum OrderFields: string
{
    case Номер = "PAY_ID";
    case СрокДействия = "DATE_TILL";
    case СуммаДокумента = "SUMMA";
}