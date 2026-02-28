<?php

namespace Packages\IctInterface\Exports;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Packages\IctInterface\Models\ReportColumn;

class MapExport
{
    public $mapLabelReference = [];
    protected $colTypeCache = [];
    protected $reportId;

    public function __construct()
    {
        $this->reportId = request('report');
        $this->setMapLabelReference();
        $this->cacheColTypes();
    }

    public function getMappedRow($row)
    {
        $mappedRow = [];
        $attributes = is_array($row) ? $row : (array) $row;

        foreach ($attributes as $key => $value) {
            $refKey = $this->reportId . '-' . $key;

            if (isset($this->mapLabelReference[$refKey])) {
                $mappedRow[] = $this->mapLabelByReference($value, $this->mapLabelReference[$refKey]);
            } else {
                $mappedRow[] = $this->_setValue($key, $value);
            }
        }

        return $mappedRow;
    }

    /**
     * _setValue
     * Restituisce il valore dopo aver controllato se è cryptato o meno
     */
    protected function _setValue($field, $value)
    {
        $type = $this->colTypeCache[$field] ?? null;
        return $type == 'crypted' ? _decrypt($value) : $value;
    }

    /**
     * cacheColTypes
     * Carica i tipi delle colonne una sola volta
     */
    protected function cacheColTypes()
    {
        $cols = ReportColumn::where('report_id', $this->reportId)->get();
        foreach ($cols as $col) {
            $this->colTypeCache[$col->field] = $col->type;
        }
    }

    /**
     * setMapLabelReference
     * Imposta la mappatura campo_tabella/reference da options in un array
     */
    public function setMapLabelReference()
    {
        $cols = ReportColumn::where('type_params', 'like', '%reference%')->get();

        foreach ($cols as $col) {
            $reference = Str::afterLast($col->type_params, 'reference:');
            $this->mapLabelReference = Arr::add(
                $this->mapLabelReference,
                $col->report_id . '-' . $col->field,
                $reference
            );
        }
    }

    /**
     * mapLabelByReference
     * Assegna la label human readable ad un codice nella tabella options
     */
    public function mapLabelByReference($value, $reference)
    {
        $map = [];
        $records = _option(null, $reference);
        foreach ($records as $record) {
            $map = Arr::add($map, $record->code, $record->label);
        }

        return $map[$value] ?? 'N/A';
    }
}
