<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Browser Fingerprint Analysis Settings
    |--------------------------------------------------------------------------
    |
    | These settings control the behavior of the browser fingerprint analysis
    | service, helping to identify and block suspicious requests.
    |
    */

    'fingerprint' => [

        /**
         * The suspicion score threshold.
         * A request with a score equal to or greater than this value will be
         * considered suspicious and will be blocked.
         */
        'suspicion_threshold' => env('FINGERPRINT_SUSPICION_THRESHOLD', 100),

        /**
         * The validity period of a request in seconds.
         * This is used to prevent replay attacks by ensuring that a request
         * is not too old.
         */
        'request_validity_seconds' => env('FINGERPRINT_REQUEST_VALIDITY_SECONDS', 600),

    ],

];
