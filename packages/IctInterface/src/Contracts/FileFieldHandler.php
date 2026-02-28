<?php

namespace Packages\IctInterface\Contracts;

interface FileFieldHandler
{
    /**
     * Viene invocato dopo il salvataggio del file su filesystem.
     *
     * @param string $fullPath    Path completo del file salvato (relativo a storage/app)
     * @param array  $formData    Tutti i dati del form (inclusi altri campi)
     * @param int    $recordId    ID del record appena salvato/aggiornato
     * @param string $tableName   Tabella di destinazione
     * @param string $fieldName   Nome del campo file nel form
     * @return void
     */
    public function handle(
        string $fullPath,
        array $formData,
        int $recordId,
        string $tableName,
        string $fieldName
    ): void;
}
