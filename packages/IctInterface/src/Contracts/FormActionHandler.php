<?php

namespace Packages\IctInterface\Contracts;

interface FormActionHandler
{
    // Hook: modifica $data prima dell'insert. Return null = abort.
    public function beforeStore(string $tableName, array $data, int $formId): ?array;
    // Hook: modifica $data prima dell'update. Return null = abort.
    public function beforeUpdate(string $tableName, array $data, int $formId, int $recordId): ?array;
    // Hook: return false = abort delete.
    public function beforeDelete(string $tableName, int $recordId, string $action): bool;

    // Replacement: return int (new ID) = handled. Return null = usa default DB::table().
    public function store(string $tableName, array $data, int $formId): ?int;
    // Replacement: return true = handled. Return null = usa default.
    public function update(string $tableName, array $data, int $formId, int $recordId): ?bool;
    // Replacement: $action = 'delete'|'disable'. Return true = handled. Return null = usa default.
    public function delete(string $tableName, int $recordId, string $action): ?bool;

    // Hook post-operazione
    public function afterStore(string $tableName, array $data, int $newRecordId, int $formId): void;
    public function afterUpdate(string $tableName, array $data, int $recordId, int $formId): void;
    public function afterDelete(string $tableName, int $recordId, string $action): void;
}
