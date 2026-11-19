<?php
declare(strict_types=1);

// Сверка классов с class_requirements.csv: php tests/requirements_check.php
require_once __DIR__ . '/../src/Service/CatalogService.php';
require_once __DIR__ . '/../src/Service/RequirementsChecker.php';

$failed = 0;
foreach ((new RequirementsChecker(__DIR__ . '/../data/class_requirements.csv'))->check() as $r) {
    printf("[%s] %s::%s (%s) %s\n", $r['ok'] ? 'OK  ' : 'FAIL', $r['class'], $r['member'], $r['kind'], $r['ok'] ? '' : $r['note']);
    $failed += $r['ok'] ? 0 : 1;
}
exit($failed === 0 ? 0 : 1);
