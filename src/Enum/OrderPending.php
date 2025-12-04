<?php

namespace App\Enum;

enum OrderStatus : string
{
    case PENDING = 'PENDING';
    case COMPLETED = 'COMPLETED';
    case CANCELLED = 'CANCELLED';
    case DRAFT = 'DRAFT';
}