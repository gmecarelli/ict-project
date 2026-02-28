<?php

namespace Packages\IctInterface\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Packages\IctInterface\Contracts\FileFieldHandler;
use Packages\IctInterface\Models\Attachment;

class AttachmentService
{
    /**
     * Upload e registrazione allegato (Caso A)
     *
     * @param UploadedFile $file         File caricato
     * @param string       $attachableType  Classe del model (es. 'App\Models\Book')
     * @param int          $attachableId    ID del record padre
     * @param string|null  $description     Descrizione opzionale
     * @param string|null  $subDir          Sottocartella opzionale (es. 'books')
     * @return Attachment
     */
    public function store(
        UploadedFile $file,
        string $attachableType,
        int $attachableId,
        ?string $description = null,
        ?string $subDir = null
    ): Attachment {
        $baseDir = config('ict.upload_dir', 'uploads');
        $path = $subDir ? "{$baseDir}/{$subDir}" : $baseDir;

        $originalName = $file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension();
        $serverName = $this->generateServerName($attachableId, $ext);

        // Upload fisico
        $file->storeAs("public/{$path}", $serverName);

        // Registrazione DB
        return Attachment::create([
            'file_name_server'  => $serverName,
            'file_name_original' => $originalName,
            'description'       => $description,
            'path'              => $path,
            'ext'               => $ext,
            'attachable_type'   => $attachableType,
            'attachable_id'     => $attachableId,
        ]);
    }

    /**
     * Upload senza registrazione su attachments (Caso B — importazione)
     *
     * Salva il file su filesystem e restituisce i metadati.
     *
     * @return array{server_name: string, original_name: string, path: string, full_path: string, ext: string}
     */
    public function storeForImport(
        UploadedFile $file,
        ?string $subDir = null
    ): array {
        $baseDir = config('ict.upload_dir', 'uploads');
        $path = $subDir ? "{$baseDir}/{$subDir}" : $baseDir;

        $originalName = $file->getClientOriginalName();
        $ext = $file->getClientOriginalExtension();
        $serverName = time() . '_' . $originalName;

        $file->storeAs("public/{$path}", $serverName);

        return [
            'server_name'   => $serverName,
            'original_name' => $originalName,
            'path'          => $path,
            'full_path'     => "{$path}/{$serverName}",
            'ext'           => $ext,
        ];
    }

    /**
     * Elimina allegato: record DB + file fisico
     */
    public function delete(Attachment $attachment): bool
    {
        $filePath = "public/{$attachment->full_path}";

        // Elimina file fisico
        if (Storage::exists($filePath)) {
            Storage::delete($filePath);
        }

        // Elimina record DB
        return $attachment->delete();
    }

    /**
     * Elimina tutti gli allegati di un'entita'
     */
    public function deleteAllFor(string $attachableType, int $attachableId): int
    {
        $attachments = Attachment::where('attachable_type', $attachableType)
            ->where('attachable_id', $attachableId)
            ->get();

        $count = 0;
        foreach ($attachments as $attachment) {
            if ($this->delete($attachment)) {
                $count++;
            }
        }
        return $count;
    }

    /**
     * Risolve il handler per un campo file.
     * Cerca: config esplicita -> convention App\Actions\{Studly(table)}{Studly(field)}Handler
     */
    public function resolveFileHandler(string $tableName, string $fieldName): ?FileFieldHandler
    {
        $handlers = config('ict.file_handlers', []);

        // 1. Mapping esplicito tabella.campo
        if (isset($handlers["{$tableName}.{$fieldName}"])) {
            return app($handlers["{$tableName}.{$fieldName}"]);
        }

        // 2. Mapping esplicito solo campo
        if (isset($handlers[$fieldName])) {
            return app($handlers[$fieldName]);
        }

        // 3. Convention: App\Actions\{Table}{Field}Handler
        $class = 'App\\Actions\\' . Str::studly($tableName) . Str::studly($fieldName) . 'Handler';
        if (class_exists($class)) {
            return app($class);
        }

        return null;
    }

    /**
     * Genera nome file su disco: {id}_{timestamp}.{ext}
     */
    protected function generateServerName(int $id, string $ext): string
    {
        return "{$id}_" . date('YmdHis') . ".{$ext}";
    }
}
