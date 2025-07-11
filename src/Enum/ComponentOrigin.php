<?php

namespace App\Enum;

enum ComponentOrigin:string
{
    case HOMEMADE = 'homemade';
    case BOUGHT_NEW = 'bought_new';
    case BOUGHT_USED = 'bought_used';
    case RECYCLED = 'recycled';
    case GIFTED = 'gifted';
    case TRADED = 'traded';
    case RESTORED = 'restored';
    case UPCYCLED = 'upcycled';
}
