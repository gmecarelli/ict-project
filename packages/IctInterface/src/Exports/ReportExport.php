<?php

namespace Packages\IctInterface\Exports;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromQuery;
use Packages\IctInterface\Exports\MapExport;
use Packages\IctInterface\Controllers\IctController;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Packages\IctInterface\Models\ReportColumn;
use PhpOffice\PhpSpreadsheet\Style\NumberFormat;
use Maatwebsite\Excel\Concerns\WithColumnFormatting;

class ReportExport implements FromQuery, WithHeadings, ShouldAutoSize, WithColumnFormatting, WithMapping
{
    public $model;
    public $where;
    public $cols;
    public $fields;
    public $skip;
    public $format;
    public $types;
    public $colTypes;
    protected $mapExport;

    /**
     * __construct
     *
     * @param  mixed $model [Model di riferimento]
     * @param  mixed $where [Array con le discriminanti dei filtri]
     * @param  mixed $cols [colonne visualizzate nelle intestazioni]
     * @param  mixed $skip [colonne da non visualizzare nelle intestazioni]
     * @param  mixed $format [formato delle colonne]
     * @return void
     */
    public function __construct($model, $where = [], $skip = [], $cols = [], $format = [])
    {
        $this->where = $where;
        $this->model = $model;
        $this->cols = $cols;
        $this->skip = $skip;
        $this->format = $format;
        $this->types = [];
        $this->colTypes = [];
    }
    /**
     * @return \Illuminate\Support\Collection
     */
    public function query()
    {
        //questo metodo viene chiamato dopo heading dove, con $this->setCols, ho popolato $this->fields
        $obj = DB::table($this->model->getTable())
            ->select($this->fields)
            ->orderBy('id', 'desc');

        foreach ($this->where as $func => $arr) {
            if (count($arr) == 0) {
                continue;
            }

            foreach ($arr as $data) {
                if ($func == 'whereBetween') {
                    $range = explode(" - ", $data[2]);
                    $range[0] = _convertDateItToDb($range[0]);
                    $range[1] = _convertDateItToDb($range[1]);
                    $obj = $obj->$func($data[0], $range);
                } elseif ($func == 'whereRaw') {
                    $obj = $obj->$func($data);
                } elseif ($func == 'whereIn') {
                    $obj = $obj->$func($data[0], $data[2]);
                } else {
                    $obj = $obj->$func($data[0], $data[1], $data[2]);
                }
              
            }
        }

        return $obj;
    }

    public function headings(): array
    {
        return $this->setCols();
    }

    public function columnFormats(): array
    {
        if(count($this->format) > 0) {return $this->format;}

        foreach($this->types as $i => $type) {
            $l = $this->getExcelColumn($i);
            if($type == 'date') {
                $this->format[$l] = NumberFormat::FORMAT_DATE_DDMMYYYY;
            } elseif($type == 'currency' || $type == 'stoplight_currency') {
                $this->format[$l] = NumberFormat::FORMAT_CURRENCY_EUR;
            } elseif($type == 'integer' || $type == 'int' || $type == 'stoplight_integer') {
                $this->format[$l] = NumberFormat::FORMAT_NUMBER;
            } elseif($type == 'float' || $type == 'stoplight_float') {
                $this->format[$l] = NumberFormat::FORMAT_NUMBER_00;
            } elseif($type == 'percent' || $type == 'stoplight_percent') {
                $this->format[$l] = NumberFormat::FORMAT_PERCENTAGE_00;
            } else {
                $this->format[$l] = NumberFormat::FORMAT_TEXT;
            }
        }
        return $this->format;
    }

    private function getExcelColumn($index) {
        $column = '';
        while ($index >= 0) {
            $column = chr($index % 26 + 65) . $column;
            $index = (int)($index / 26) - 1;
        }
        return $column;
    }

    /**
     * setCols
     * Definisce le intestazioni delle colonne e i rispettivi campi
     * @return array
     */
  
     public function setCols() {
        if(count($this->cols) > 0) {return $this->cols;}
        $cols = [];

        $columns = ReportColumn::where('report_id', request('report'))
                                ->orderBy('position', 'asc')
                                ->get();
               
        foreach($columns as $col) {
            if(in_array($col->field, $this->skip) ) {continue;} // non inserisco le colonne nell'array skip

            if($col->type == 'stoplight') {
                $util = new IctController();
                $params = $util->stringToArray($col->type_params);
                if(isset($params['type'])) {
                    $col->type .= $params['type'];
                }
            }
 
            $this->fields[] = $col->field;
            $this->types[] = $col->type;
            $this->colTypes = Arr::add($this->colTypes, $col->field, $col->type);
        
            $cols[]=strtoupper($col->label);
        }

        return $cols;
    }
    /**
     * resolveMapExport
     * Restituisce l'istanza di MapExport da usare per la mappatura.
     * Override questo metodo nell'app per personalizzazioni.
     */
    protected function resolveMapExport(): MapExport
    {
        return new MapExport();
    }

    /**
     * map
     * Effettua la sostituzione dei valori delle colonne di lookup
     * con i nomi corrispondenti
     */
    public function map($row): array
    {
        if (!$this->mapExport) {
            $this->mapExport = $this->resolveMapExport();
        }
        return $this->mapExport->getMappedRow($row);
    }

}
