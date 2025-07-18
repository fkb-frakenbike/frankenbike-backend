<?php

namespace App\Enum;

enum ComponentCategory:string
{
    case FRAME = 'frame';
    case BRAKES = 'brakes';
    case FORK_AND_DIRECTION = 'fork_and_direction';
    case SEAT_PARTS = 'seat_parts';
    case DRIVETRAIN = 'drivetrain';
    case WHEELS = 'wheels';
    case ACCESSORIES = 'accessories';
    case OTHER = 'other';
}
