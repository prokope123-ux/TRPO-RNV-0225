<?php
declare(strict_types=1);

// Консольная проверка сценариев: php tests/run_cases.php
require_once __DIR__ . '/../src/functions.php';

$dataDir = __DIR__ . '/../data';
$departments = departmentsOf(loadEmployees($dataDir . '/employees.csv')['employees']);
$failed = 0;

foreach (loadFormCases($dataDir . '/form_cases.json') as $case) {
    $r = runCase($case, $departments);
    printf("[%s] %s\n", $r['passed'] ? 'OK  ' : 'FAIL', $case['title']);
    if (!$r['passed']) {
        $failed++;
        printf("       ожидалось: %s; получено: %s\n", implode(',', $r['expected_fields']), implode(',', $r['actual_fields']));
    }
}
echo $failed === 0 ? "\nВсе сценарии пройдены\n" : "\nПровалено: $failed\n";
exit($failed === 0 ? 0 : 1);
