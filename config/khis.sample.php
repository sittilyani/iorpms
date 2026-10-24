<?php
// Copy to config/khis.php and fill in. It is git-ignored.
// It holds NO username or password: whoever submits logs in to KHIS in the submit window with their own account
// (it must have data-entry rights for this facility), and that login is never saved.
return [
    'url'     => 'https://hiskenya.dha.go.ke',   // KHIS (DHIS2) address
    'orgUnit' => '',                              // this facility's organisation unit UID in KHIS
    'dataSets' => [                               // data set UID for each report that is sent
        'moh731' => '',                           // MOH 731 Plus (the MAT section)
        'p7'     => '',                           // Form P7
        'p8'     => '',                           // Form P8
    ],
];