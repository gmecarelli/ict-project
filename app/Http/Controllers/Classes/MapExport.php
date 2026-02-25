<?php

namespace App\Http\Controllers\Classes;

use Exception;
use App\Models\Concorso;
use App\Models\Supplier;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Packages\ictInterface\Models\ReportColumn;

class MapExport extends Controller
{
    public $mapLabelReference = [];
    public function getMappedRow($row) {
        $mappedRow = [];
        $this->setMapLabelReference();
        // Verifica se $row è un array o un oggetto stdClass, accedendo di conseguenza
        $attributes = is_array($row) ? $row : (array)$row;

        foreach ($attributes as $key => $value) {

            // Applica la mappatura solo se la colonna è `tipo` o `tipo_prodotto`
            if (isset($this->mapLabelReference[request('report') . '-' . $key])) {
                $mappedRow[] = $this->mapLabelByReference($value, $this->mapLabelReference[request('report') . '-' . $key]);
            }
            elseif(request('report') . '-' . $key === request('report') . '-' . 'concorso_id_tipo' && isset($attributes['concorso_id_tipo'])){
                $mappedRow[] = $this->mapTipoConcorso($value);
             }
             else {
                // Usa il valore senza modifiche
                $mappedRow[] = $value;
            }
        }
    
        return $mappedRow;
    }

     /**
     * setMapLabelReference
     * Imposta la mappatura campo_tabella/reference da options in un array
     * @return void
     */
    public function setMapLabelReference() {
        
        $cols = ReportColumn::where('type_params', 'like', '%reference%')->get();
        
        foreach($cols as $col) {
            $reference = Str::afterLast($col->type_params, 'reference:');
            $this->mapLabelReference = Arr::add($this->mapLabelReference,$col->report_id . "-" . $col->field, $reference);
        }
    }
    
    /**
     * mapLabelByReference
     * Assegna la label human readable ad un codice nella tabella options
     * @param  mixed $value
     * @param  mixed $reference
     * @return void
     */
    public function mapLabelByReference($value, $reference) {
        $map = [];
        $records = _option(null, $reference);
        foreach($records as $record) {
            $map = Arr::add($map, $record->code, $record->label);
        }

        return $map[$value] ?? 'N/A';
    }
}