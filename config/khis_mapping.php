<?php
/**
 * Maps each EasyFlow indicator to its KHIS data element + category option combo UIDs.
 * Key format: <indicator>.<Male|Female>.<band>   e.g. 'newly_inducted.Male.15-19' (band is 'all' for totals).
 * Find the UIDs in KHIS: Data Set Report for the MOH 731 Plus data set, or Maintenance > Data element.
 * Only mapped indicators are sent. Example:
 *   'newly_inducted.Male.15-19' => ['de' => 'AbCdEfGh123', 'coc' => 'XyZ123abc45'],
 */
return [
];
