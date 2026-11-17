<?php

namespace App\Enums;

enum ResultStatus: string
{
    case Draft = 'draft';
    case Finalized = 'finalized';
    case Published = 'published';
}
