<?php
declare(strict_types=1);

// Issues a signed timestamp for public forms (anti-spam). Used by assets/js/kuyaely-nav.js.
require __DIR__ . '/ke-antispam.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');
echo json_encode(['t' => ke_as_make_token()]);
