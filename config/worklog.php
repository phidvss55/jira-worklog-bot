<?php

return [

    'auth' => [
        'totp_secret' => env('WORKLOG_TOTP_SECRET'),
        'totp_period' => 30,
        'totp_digits' => 6,
        'totp_digest' => 'sha1',
        'totp_drift_periods' => 1,
        'max_attempts' => 5,
        'decay_seconds' => 60,
    ],

];
