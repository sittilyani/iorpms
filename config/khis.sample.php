<?php
// Copy to config/khis.php and fill in. config/khis.php is git-ignored: it holds the clinic's own KHIS login.
return [
    'url'      => 'https://hiskenya.dha.go.ke',   // KHIS (DHIS2) base URL
    'username' => '',                              // the clinic's KHIS user (needs data entry rights on the MOH 731 data set)
    'password' => '',
    'orgUnit'  => '',                              // this facility's organisation unit UID in KHIS
    'dataSet'  => '',                              // UID of the MOH 731 Plus data set that holds the MAT section
];
