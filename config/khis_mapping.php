<?php
/**
 * Maps each EasyFlow indicator to its KHIS data element + category option combo UIDs.
 * Key format for MOH 731 Plus: <indicator>.<Male|Female>.<band>   e.g. 'newly_inducted.Male.15-19' (band is 'all' for totals).
 * Key format for Form P7 / P8: p7.<field name> or p8.<field name> as named on the form, e.g. 'p7.clients_methadone'.
 * Find the UIDs in KHIS: Data Set Report for the MOH 731 Plus data set, or Maintenance > Data element.
 * Only mapped indicators are sent. Example:
 *   'newly_inducted.Male.15-19' => ['de' => 'AbCdEfGh123', 'coc' => 'XyZ123abc45'],
 */
return [
];
