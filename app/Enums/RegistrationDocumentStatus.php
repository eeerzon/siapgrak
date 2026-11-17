<?php

namespace App\Enums;

enum RegistrationDocumentStatus: string
{
    case Uploaded = 'uploaded';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
