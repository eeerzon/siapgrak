<?php

namespace App\Enums;

/**
 * Per-document review state. The approve/reject-per-document workflow is
 * deferred (fr-registration.md Keputusan F) — in Sprint 4 every document
 * stays 'uploaded'; registration-level review is the gate.
 */
enum RegistrationDocumentStatus: string
{
    case Uploaded = 'uploaded';
    case Approved = 'approved';
    case Rejected = 'rejected';
}
