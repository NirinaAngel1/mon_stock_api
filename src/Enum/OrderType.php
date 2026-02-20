<?php

namespace App\Enum;

enum OrderType: string
{
    case PURCHASES = 'PURCHASES';
    case SALES = 'SALES';
}