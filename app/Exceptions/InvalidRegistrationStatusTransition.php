<?php

namespace App\Exceptions;

use App\Enums\RegistrationStatus;
use RuntimeException;

/**
 * Thrown when RegistrationService::changeStatus() is asked for a transition
 * that is not in the allowed map (fr-registration.md Keputusan B). Caught in
 * EventRegistrationController::updateStatus and re-thrown as a validation
 * error on the `status` field.
 */
class InvalidRegistrationStatusTransition extends RuntimeException
{
    public function __construct(RegistrationStatus $from, RegistrationStatus $to)
    {
        parent::__construct("Tidak bisa mengubah status pendaftaran dari \"{$from->value}\" ke \"{$to->value}\".");
    }
}
