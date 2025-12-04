<?php

namespace App\Enum;

enum OrderType: string
{
    case PURCHASE = 'PURCHASE';
    case SALES = 'SALES';
}