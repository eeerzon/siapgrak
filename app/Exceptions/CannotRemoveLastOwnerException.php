<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an action (remove, demote, deactivate) would leave an
 * organization with zero owners. See fr-organization-tenancy.md RULE-2.
 */
class CannotRemoveLastOwnerException extends RuntimeException
{
    public function __construct()
    {
        parent::__construct('Organisasi harus memiliki minimal satu owner.');
    }
}
