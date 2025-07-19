<?php

namespace App\Exceptions;

/**
 * Thrown when fingerprint decryption fails for any reason.
 * Extends BrowserFingerprintException to leverage its reporting and rendering capabilities.
 */
class DecryptionFailedException extends BrowserFingerprintException
{
    // We can add more specific context here in the future if needed.
}
