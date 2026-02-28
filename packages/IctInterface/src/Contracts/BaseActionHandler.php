<?php

namespace Packages\IctInterface\Contracts;

abstract class BaseActionHandler implements FormActionHandler
{
    public function beforeStore(string $tableName, array $data, int $formId): ?array
    {
        return $data;
    }

    public function beforeUpdate(string $tableName, array $data, int $formId, int $recordId): ?array
    {
        return $data;
    }

    public function beforeDelete(string $tableName, int $recordId, string $action): bool
    {
        return true;
    }

    public function store(string $tableName, array $data, int $formId): ?int
    {
        return null;
    }

    public function update(string $tableName, array $data, int $formId, int $recordId): ?bool
    {
        return null;
    }

    public function delete(string $tableName, int $recordId, string $action): ?bool
    {
        return null;
    }

    public function afterStore(string $tableName, array $data, int $newRecordId, int $formId): void {}
    public function afterUpdate(string $tableName, array $data, int $recordId, int $formId): void {}
    public function afterDelete(string $tableName, int $recordId, string $action): void {}
}
