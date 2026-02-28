<?php

namespace App\Actions;

use Illuminate\Support\Str;
use Packages\IctInterface\Contracts\BaseActionHandler;

class BooksActionHandler extends BaseActionHandler
{
    // Esempio: hook before store per aggiungere campo calcolato
    public function beforeStore(string $tableName, array $data, int $formId): ?array
    {
        $data['slug'] = Str::slug($data['title'] ?? '');
        return $data;
    }

    // Esempio: replacement completo dello store con Eloquent
    public function store(string $tableName, array $data, int $formId): ?int
    {
        $book = \App\Models\Book::create($data);
        return $book->id;
    }

    // Esempio: after hook per logging
    public function afterDelete(string $tableName, int $recordId, string $action): void
    {
        _log()->info("Book {$recordId} {$action}d");
    }
}
