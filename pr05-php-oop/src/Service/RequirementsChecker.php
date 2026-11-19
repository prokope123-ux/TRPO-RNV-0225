<?php
declare(strict_types=1);

/**
 * Сверяет реальные классы с требованиями из class_requirements.csv
 * (колонки: class, member, kind, type, required, description).
 *
 * Проверяется: существует ли свойство/метод, совпадает ли тип, и для обязательных
 * свойств — что параметр конструктора не имеет значения по умолчанию (для необязательных — имеет).
 */
final class RequirementsChecker
{
    public function __construct(private string $csvPath)
    {
    }

    /** @return array<int, array<string, string>> строки CSV как ассоциативные массивы */
    public function requirements(): array
    {
        if (!is_readable($this->csvPath)) {
            throw new RuntimeException('Файл class_requirements.csv не найден или недоступен');
        }
        $handle = fopen($this->csvPath, 'rb');
        $header = fgetcsv($handle, null, ',', '"', '');
        if (!is_array($header)) {
            fclose($handle);
            throw new RuntimeException('Файл class_requirements.csv пуст');
        }
        $header[0] = ltrim((string) $header[0], "\xEF\xBB\xBF");

        $rows = [];
        while (($row = fgetcsv($handle, null, ',', '"', '')) !== false) {
            if ($row === [null] || count($row) !== count($header)) {
                continue; // пустые и повреждённые строки пропускаем
            }
            $rows[] = array_combine($header, array_map('trim', $row));
        }
        fclose($handle);
        return $rows;
    }

    /**
     * Результат проверки по каждому требованию.
     *
     * @return array<int, array{class:string, member:string, kind:string, type:string, required:string, description:string, ok:bool, note:string}>
     */
    public function check(): array
    {
        $results = [];
        foreach ($this->requirements() as $req) {
            [$ok, $note] = $this->checkOne($req);
            $results[] = $req + ['ok' => $ok, 'note' => $note];
        }
        return $results;
    }

    /** @return array{0: bool, 1: string} */
    private function checkOne(array $req): array
    {
        if (!class_exists($req['class'])) {
            return [false, 'класс не найден'];
        }
        $ref = new ReflectionClass($req['class']);

        if ($req['kind'] === 'property') {
            if (!$ref->hasProperty($req['member'])) {
                return [false, 'свойство не найдено'];
            }
            $actualType = (string) $ref->getProperty($req['member'])->getType();
            if ($actualType !== $req['type']) {
                return [false, "тип {$actualType}, ожидался {$req['type']}"];
            }
            $ctor = $ref->getConstructor();
            foreach ($ctor?->getParameters() ?? [] as $param) {
                if ($param->getName() === $req['member']) {
                    $isOptional = $param->isDefaultValueAvailable();
                    if ($req['required'] === 'yes' && $isOptional) {
                        return [false, 'обязательное свойство имеет значение по умолчанию'];
                    }
                    if ($req['required'] === 'no' && !$isOptional) {
                        return [false, 'необязательное свойство должно иметь значение по умолчанию'];
                    }
                }
            }
            return [true, 'ок'];
        }

        if ($req['kind'] === 'method') {
            if (!$ref->hasMethod($req['member'])) {
                return [false, 'метод не найден'];
            }
            $actualType = (string) $ref->getMethod($req['member'])->getReturnType();
            if ($actualType !== $req['type']) {
                return [false, "возвращает {$actualType}, ожидался {$req['type']}"];
            }
            return [true, 'ок'];
        }

        return [false, 'неизвестный вид члена: ' . $req['kind']];
    }
}
