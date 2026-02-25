<?php

/**
 * CLASSE DI SERVIZIO DI SUPPORTO CON METODI E PROPRIETA' REALIVE AI REPORT
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Controllers\Services;

use stdClass;
use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Packages\IctInterface\Models\Report;
use Packages\IctInterface\Models\ReportColumn;
use Packages\IctInterface\Models\ReportFilter;
use Packages\IctInterface\Controllers\IctController;
use Packages\IctInterface\Controllers\Services\Logger;
use Packages\IctInterface\Controllers\Services\ApplicationService;

class ReportService extends ApplicationService
{


    public $linkPages;
    public $pagesLinks = [];
    public $paginate = 20;
    public $countRecords;
    public $whereFunction;
    public $groupBy;
    public $reportProp;

    public $countRows;

    public function __construct()
    {
        parent::__construct();
        $this->countRows = [
            'Totale righe' => null
        ];
    }

    /**
     * loadReportData
     * Restituisce l'oggetto con le proprietà del report
     * @return void
     */
    public function loadReportProperties($id)
    {
        $this->log->info("*Carico le proprietà del report id[{$id}]*", __FILE__, __LINE__);
        if (request()->input('report')) {
            $_report = Report::where([
                'id' => $id,
                'is_enabled' => 1
            ])
                ->first();
            $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);

            if (empty($_report->id)) {
                $this->log->error("*REPORT id[{$id}] NON TROVATO*", __FILE__, __LINE__);
                redirect('dashboard');
            }
            //TODO: controllo permessi
            if (session()->get('is_admin') == 1) {
                return $_report;
            }

            $roles = session('roles_checker')[$_report->id];
            foreach ($roles as $key => $value) {
                $_report->$key = $value;
            }

            $this->log->info("*PROPRIETA' REPORT CARICATE*", __FILE__, __LINE__);

            return $_report;
        }
    }

    public function setPaginate($value)
    {
        $this->paginate = $value;
    }

    /**
     * setCounterRows
     * Imposta il numero dei record restituiti ed eventuali somme impostate per il report 
     * @param  mixed $strSum
     * @param  mixed $Model
     * @param  mixed $whereFilters
     * @param  mixed $group_by
     * @return void
     */
    public function setCounterRows($strSum, $Model, $whereFilters, $group_by = null)
    {

        if (Str::length($strSum) == 0) {
            return;
        }
        //imposto il group by che farà in queryBuilderWithFilters
        $this->groupBy = $group_by;

        $pairSum = $this->stringToArray($strSum);

        foreach ($pairSum as $field => $label) {
            $helper = Str::before($field, '|');

            if ($helper != $field) {
                // E' stato indicato un helper nelle somme
                $field = Str::after($field, '|');
            }
            $this->countRows = Arr::add($this->countRows, $label, 0);

            // $countModel = $Model->select(DB::raw('SUM('.$field.') AS '.$field));
            $records = $this->queryBuilderWithFilters($Model, $whereFilters, false, [DB::raw('SUM(' . $field . ') AS ' . $field)], null);
            $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);

            foreach ($records as $record) {
                $this->countRows[$label] = $record[$field];
            }

            if ($helper != $field) {
                $this->countRows[$label] = $helper($this->countRows[$label]);
            }

            // dd($this->countRows);
        }
        $this->log->debug(print_r($this->countRows, true), __FILE__, __LINE__);
    }

    /**
     * tableData
     *  Restituisce i dati da visualizzare in tabella nella view report.blade.php
     * @param  mixed $objModel
     * @param  mixed $objCols
     * @return array (matrix)
     */
    public function tableData($objModel, $objCols, $whereFilters = null)
    {

        try {


            $this->log->info("#Elaboro e restituisco i dati da visualizzare#", __FILE__, __LINE__);
            $fields = [];

            foreach ($objCols as $objCol) {

                $fields[] = $this->getFieldName($objCol);
            }


            if (!is_null($whereFilters)) {

                //QueryBuilder con filtri attivati
                $records = $this->queryBuilderWithFilters($objModel, $whereFilters, true, $fields);

                $this->linkPages = $records;
            } else {

                //queryBuilder normale senza filtri
                $rec = $objModel->select($fields)
                    ->where(function ($query) use ($objModel) {
                        $report = $this->loadReportProperties(request()->input('report'));

                        if (!is_null($report['where_condition'])) {

                            $query->whereRaw($report['where_condition']);
                        }

                        if (Schema::hasColumn($objModel->getTable(), 'is_enabled') == true) {

                            //se la tabella che si sta interrogando ha il campo is_enabled 
                            $query->where('is_enabled', 1);
                        }

                        if (!is_null($report['group_by'])) {
                            $this->groupBy = explode(",", $report['group_by']);
                        }
                    });
                if (!is_null($this->groupBy)) {

                    $rec = $rec->groupBy($this->groupBy);
                }

                $this->countRecords = $rec->get()->count(); //imposto il contatore delle righe trovate
                $this->setHeaderOrderBy($rec);

                $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);

                $records = $rec->paginate($this->paginate);
                $this->linkPages = $records;
            }

            $this->countRows['Totale righe'] = _number($this->countRecords); //imposto il contatore delle righe trovate

            //imposto l'oggetto che mi servirà per i link della paginazione
            $route = url()->current(); //.'?'.$_SERVER['QUERY_STRING'];//report='.request('report');
            $this->pagesLinks = $records->onEachSide(1)->links()->elements[0] ? $records->onEachSide(1)->links()->elements[0] : [1 => $route];

            $queryArr = request()->query();
            $queryString = Arr::query($queryArr);
            foreach ($this->pagesLinks as $key => $link) {

                preg_match("/page/", $queryString) ? $link .= "&" . substr(preg_replace("/page=[0-9]+/", "", $queryString), 1) : $link .= "&" . preg_replace("/page=[0-9]+/", "", $queryString);

                $this->pagesLinks[$key] = $link;
            }

            $this->log->info("*QUERY CARICAMENTO RIGHE DEL REPORT*", __FILE__, __LINE__);
            $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);
            //carico la lista dei record dal model
            return $this->cleanDataReport($records);
        } catch (Exception $e) {
            dd($e->getMessage(), $e->getLine(), $e->getFile());
        }
    }

    /**
     * setHeaderOrderBy
     * Imposta l'ordinamento al click sull'intestazione di colonna del report
     * @param  mixed $resultset
     * @param  mixed $order_by
     * @return void
     */
    protected function setHeaderOrderBy(&$resultset, $order_by = null)
    {
        $ob = request('ob'); // Può essere una stringa o un array
        $ot = request('ot'); // Può essere una stringa o un array

        if (request()->filled('ob') && request()->filled('ot')) {
            if (is_array($ob) && is_array($ot)) {
                foreach ($ob as $key => $field) {
                    $direction = $ot[$key] ?? 'asc'; // Ordine di default se manca l'indice
                    $resultset->orderBy($field, $direction);
                }
            } elseif (!is_array($ob) && !is_array($ot)) {
                $hasfield = Str::contains($resultset->toSql(), "`{$ob}`");
                if ($hasfield) {
                    $resultset->orderBy($ob, $ot);
                }
            } else {
                // Gestione in caso di discrepanza tra array e singolo valore
                throw new \Exception('I parametri ob e ot devono essere entrambi array o entrambi stringhe.');
            }
        } else {
            if (is_null($order_by)) {

                $hasId = Str::contains($resultset->toSql(), '`id`');
                if ($hasId) {
                    $resultset->orderBy('id', 'desc');
                }
            } else {
                $hasfield = Str::contains($resultset->toSql(), "`{$order_by[0]}`");
                if ($hasfield) {
                    $resultset->orderBy($order_by[0], $order_by[1]);
                }
            }
        }
    }


    /**
     * queryBuilderWithFilters
     * Costruisce la query quando i filtri sono attivati
     * @param  mixed $objModel
     * @param  mixed $whereFilters
     * @param  mixed $fields
     * @return mixed
     */
    public function queryBuilderWithFilters($objModel, $whereFilters, $paginate, $fields = ['*'], $orderBy = ['id', 'desc'])
    {
        $resultset = $this->setQueryFilters($objModel, $whereFilters, $fields);

        $this->reportProp = $this->loadReportProperties(request()->input('report'));

        $resultset = $this->setWhereReport($resultset);
        $resultset = $this->setGroupByReport($resultset);

        //imposto il contatore delle righe trovate
        $this->countRecords = $resultset->get()->count();

        if (request('report') != 12) {
            $this->setHeaderOrderBy($resultset, $orderBy);
        }

        if ($paginate == false) {

            return $resultset->get()->toArray();
        }

        $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);
        return $resultset->paginate($this->paginate);
    }

    /**
     * setQueryFilters
     * Imposta la query risultante dai filtri attivati
     * @param  mixed $objModel
     * @param  mixed $whereFilters
     * @param  mixed $fields
     * @return void
     */
    public function setQueryFilters($objModel, $whereFilters, $fields = ['*'])
    {
        $objModel->where = $whereFilters;

        $resultset = $objModel->select($fields)
            ->where(function ($query) {
                $i = 0;
                if (isset($query->getModel()->where)) {
                    if (isset($query->getModel()->where['filter'])) {
                        unset($query->getModel()->where['filter']);
                    }
                    foreach ($query->getModel()->where as $func => $arr) {

                        if (preg_match("/^where[A-Za-z\-]+/", $func) || preg_match("/^or[A-Za-z\-]+/", $func)) {
                            if ($func == 'whereBetween' || $func == 'whereNotBetween') {
                                $dates = explode(' - ', $arr[0][2]);
                                $dates[0] = _convertDateItToDb($dates[0]);
                                $dates[1] = _convertDateItToDb($dates[1]);
                                $query->$func($arr[0][0], $dates);
                                continue;
                            }

                            if ($func == 'whereIn' || $func == 'whereNotIn') {
                                $inData = $arr[0][2];
                                if ($inData[0] === '' || $inData[0] == '%5B%5D') {

                                    continue;
                                }

                                $query->$func($arr[0][0], $inData);

                                continue;
                            }
                            if ($func == 'orWhere') {

                                // Raggruppa i filtri "orWhere" in una closure per evitare conflitti con altre condizioni
                                $query->where(function ($subQuery) use ($arr) {
                                    foreach ($arr as $value) {
                                        // Aggiungi ogni filtro "orWhere"
                                        $subQuery->orWhere($value[0], '=', $value[2]);
                                    }
                                });
                            }

                            // QueryBuilder con una funzione custom (whereMonth, whereYear, etc)
                            foreach ($arr as $data) {
                                if ($data[2] === '') {
                                    $query->$func($data[0], $data[1], $data[2]);
                                }
                            }
                        } else {
                            foreach ($arr as $i => $key) {
                                if (in_array('is_enabled', $key)) {
                                    if (Arr::get($key, 2) == '%%') {
                                        $arr[$i][1] = '=';
                                        $arr[$i][2] = 1;
                                    }
                                }
                            }

                            // Gestisco i filtri con collation per "tipo_entrata"
                            foreach ($arr as $filter) {
                                // Controllo se il filtro è definito correttamente
                                if (isset($filter[0], $filter[1], $filter[2])) {
                                    // Controllo se il valore è un numero

                                    if ($filter[0] === 'tipo_entrata') {
                                        // Applicare la conversione solo se il filtro è per 'tipo_entrata'
                                        $query->whereRaw("CONVERT(`{$filter[0]}` USING utf8mb4) COLLATE utf8mb4_unicode_ci {$filter[1]} ?", [$filter[2]]);
                                    } else {
                                        // Per gli altri filtri, utilizzare la condizione normale
                                        $query->$func($filter[0], $filter[1], $filter[2]);
                                    }
                                }
                            }

                            // Logging delle query

                        }
                    }
                }
            });
        //USA QUESTO DUMP PER VEDERE LA QUERY
        //    dump($resultset->toSql());
        return $resultset;
    }


    public function setWhereReport($resultset)
    {

        if (!is_null($this->reportProp['where_condition'])) {
            //personalizzazione per commesse. Se attivo il filtro data_curdate sostituisce la stringa CURDATE() nella whereCondition del report 
            if (request()->has('__data_curdate')) {
                $this->reportProp['where_condition'] = str_replace("CURDATE()", "'" . request('__data_curdate') . "'", $this->reportProp['where_condition']);
            }
            $resultset = $resultset->whereRaw($this->reportProp['where_condition']);
        }

        return $resultset;
    }

    public function setGroupByReport($resultset)
    {
        if (!is_null($this->reportProp['group_by'])) {
            $groupBy = explode(",", $this->reportProp['group_by']);
            $resultset = $resultset->groupBy($groupBy);
        }
        return $resultset;
    }

    /**
     * _getOperator
     * Restituisce l'operatore per la where condition del queryBuilder
     * Il nome del campo deve essere scritto con questa sintassi
     * [whereFunction]-[operator]_[nomecampo] Es: whereMonth-ue_billing_date
     * Le sigle degli operatori possibili nell'array $operators
     * @param  mixed $str
     * @return string
     */
    public function _getOperator($str)
    {

        $operators = [
            'eq' => '=', //equal
            'ge' => '>=', //up to - equal
            'le' => '<=', //down to - equal
            'gt' => '>', //maggiore
            'lt' => '<', //minore
            'ne' => '<>', //diverso
        ];
        if (strpos($str, "-") == false) {
            $this->whereFunction = $str;

            return $operators['eq'];
        }

        $this->whereFunction = substr($str, 0, -3);
        $op = substr($str, strpos($str, "-") + 1);

        if (in_array($op, $operators) == false) {
            $op = Str::between($str, '-', '_');
            if (in_array($op, $operators) == false) {
                $op = Str::before($op, '_');
            }
        }

        if (isset($operators[$op])) {
            return $operators[$op];
        }

        $this->log->debug("La sigla [{$op}] è un operatore non definito. Restituisco eq", __FILE__, __LINE__);
        return $operators['eq'];
    }

    /**
     * getFieldName
     * Restituisce il valore del dato da visualizzare nel report
     * @param  mixed $objCol
     * @return string
     */
    public function getFieldName($objCol)
    {
        // $this->log->info("#restituisco il valore del dato da visualizzare nel report# [{$objCol->getAttributes()['field']}]",__FILE__,__LINE__);
        //il campo field è la colonna della tabella report_columns che corrisponde al nome della colonna della tabella della visualizzazione (o report) corrente
        return $objCol->field;
    }

    /**
     * cleanDataReport
     *  Restituisce una matrice che contiene i valori del contenuto della tabella visualizzata nel report
     * @param  model $obj
     * @return array
     */
    public function cleanDataReport($obj)
    {
        $data = [];

        if ($obj->count() > 0) {
            foreach ($obj as $modelData) {
                $data[] = $modelData->toArray();
            }
        }

        //$this->log->debug("*MATRICE DEI DATI* ".print_r($data,true),__FILE__,__LINE__);
        return $data;
    }

    /**
     * loadReportColumns
     * Carica le colonne del report
     * @param  mixed $id
     * @return void
     */
    public function loadReportColumns($id)
    {

        $this->log->info("*Carico colonne del report* report_id[{$id}]", __FILE__, __LINE__);
        // $cols = ReportColumn::where('report_id','=',$id)->get();
        $cols = Report::find(request('report'))
            ->columns()
            ->orderBy('position')
            ->get();

        $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);
        $this->log->debug("*Record caricati* [" . count($cols) . "]", __FILE__, __LINE__);

        $cols = $this->addOrderLink($cols);

        return $cols;
    }

    /**
     * addOrderLink
     * Aggiunge ed imposta il link per l'ordinamento
     * @param  mixed $cols
     * @return void
     */
    protected function addOrderLink($cols)
    {

        $columns = $cols;
        $str_orderby = '';
        $req_ob = '';
        $req_ot = '';

        if (request()->has('ob') && request()->has('ot')) {
            $req_ob = request('ob');
            $req_ot = request('ot');
        }
        $this->purgeRequestEmpty();
        foreach ($columns as $i => $col) {
            $ot = 'ASC';
            if ($req_ob == $col->field) {
                if ($req_ot == 'ASC') {
                    $ot = 'DESC';
                }
            }

            $str_orderby = '&ob=' . $col->field . '&ot=' . $ot;
            $columns[$i]['order_link'] = url()->current() . '?' . Arr::query(request()->all()) . $str_orderby;
        }
        request()->request->add(['ob' => $req_ob]);
        request()->request->add(['ot' => $req_ot]);
        return $columns;
    }

    /**
     * purgeRequestEmpty
     * Elimina tutte le variabili request vuote e le variabili per l'ordinamento ob e ot
     * @return void
     */
    public function purgeRequestEmpty()
    {
        request()->request->remove('ob');
        request()->request->remove('ot');
        foreach (request()->all() as $key => $value) {
            if (!filled($value)) {
                request()->request->remove($key);
            }
        }
    }

    /**
     * makeWhereFilter
     * Compone la stringa della query con i filtri applicati
     * @param  mixed $form_id
     * @return void
     */
    public function makeWhereFilter($form_id)
    {
        try {


            $whereFilters = null;
            $fields = $this->loadFormFieldsData($form_id);


            if (request()->input('filter') == 'Y' || $fields->isNotEmpty()) {

                $this->log->info("#CREO ARRAY WHERE PER I FILTRI#", __FILE__, __LINE__);

                foreach ($fields as $field) {
                    if ($field->is_guarded == 1 || !request()->filled($field->name)) {
                        continue;
                    }
                    preg_match("/^filter_/", $field->name) ? $trueFieldName = str_replace("filter_", "", $field->name) : $trueFieldName = $field->name;

                    /**
                     * Viene impostata una matrice che avrà come chiave primaria il nome della funzione
                     * del queryBuilder (where, whereMonth, whereYear, etc)
                     * Nel momento in cui un campo del filtro inizia per "where", questo viene trattato come
                     * nome di una funzione QueryBuilder
                     * 
                     */
                    if (preg_match("/^where[A-Za-z\-]+_/", $field->name)) {
                        $nameFunc = substr($field->name, 0, strpos($field->name, "_"));

                        $op = $this->_getOperator($nameFunc);

                        $trueFieldName = str_replace($nameFunc . "_", "", $field->name);
                        //levo dal nomeFunc l'eventuale operatore
                        $nameFunc = $this->whereFunction; //substr($field->name,0, strpos($nameFunc,"-"));

                        if (Str::contains($nameFunc, 'Null')) {
                            if (request()->input($field->name) == 1) {
                                $whereFilters['whereNotNull'][] = [$trueFieldName];
                            } elseif (!request()->filled($field->name)) {
                                // 
                            } elseif (request()->input($field->name) == 0) {
                                $whereFilters[$nameFunc][] = [$trueFieldName];
                            }
                        }
                        // elseif(Str::contains($nameFunc, 'In')) {
                        //     $inData = explode(',', request()->input($field->name));
                        //     $whereFilters[$nameFunc][] = [$trueFieldName, $inData];

                        // } 
                        else {
                            $whereFilters[$nameFunc][] = [$trueFieldName, $op, request()->input($field->name)];
                        }
                    } elseif (is_numeric(request()->input($field->name)) && (Str::contains($field->name, '_id') || $field->name == 'id' || $field->type == 'select')) {
                        if (request()->filled($field->name)) {
                            $whereFilters['where'][] = [$trueFieldName, '=', request()->input($field->name)];
                        }
                    } else {
                        if (request()->filled($field->name)) {
                            $values = request()->input($field->name);  // Recupera l'array
                            if (is_array($values)) {
                                $first = true; // Flag per identificare il primo valore

                                foreach ($values as $value) {
                                    if (is_array($value)) {
                                        foreach ($value as $item) {

                                            $whereFilters['orWhere'][] = [$trueFieldName, '=', $item]; // Qui usiamo 'or'

                                            // Se è il primo filtro, usa "where", altrimenti usa "orWhere"
                                            // if ($first) {
                                            //     $whereFilters['where'][] = [$trueFieldName, '=', $item];
                                            //     $first = false;
                                            // } else {

                                            // }
                                        }
                                    } else {
                                        $whereFilters['orWhere'][] = [$trueFieldName, '=', $values]; // Qui usiamo 'or'

                                    }
                                }
                            } else {
                                $whereFilters['where'][] = [$trueFieldName, 'like', '%' .   $values  . '%'];
                            }
                        }
                    }
                    //  elseif (is_numeric(request()->input($field->name))) {
                    //     if (request()->filled($field->name)) {
                    //         $whereFilters['where'][] = [$trueFieldName, '=', request()->input($field->name)];
                    //     }
                    // } 

                }
                // if(Schema::hasColumn($Model->getTable(), 'is_enabled') == true) {
                //     $whereFilters['where'][] = ['is_enabled','=',1];
                // }
                $this->log->debug("*ARRAY FILTERS* " . print_r($whereFilters, true), __FILE__, __LINE__);
                // session()->flash('whereFilters', $whereFilters);
            } elseif (session()->has('whereFilters')) {
                $whereFilters = session()->get('whereFilters');
            } else {
                session()->pull('whereFilters');
            }

            return $whereFilters;
        } catch (Exception $e) {
            dd($e->getMessage(), $e->getLine());
        }
    }

    /**
     * loadTableData
     * Carica i dati da visualizzare nella tabella del report
     * @param  mixed $objModel
     * @param  mixed $objCols
     * @return void
     */
    public function loadTableData($objModel, $objCols, $form_id, $whereFilters = null)
    {

        $records = $this->tableData($objModel, $objCols, $whereFilters);
        $this->log->debug("## QUERY DATI REPORT ##", __FILE__, __LINE__);
        $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);

        $columns = $this->cleanDataReport($this->loadReportColumns(request('report')));

        $this->log->info("*ELABORO I TIPO DI DATO PER UNA VISUALIZZAZIONE LEGGIBILE (TIPO_DATO)* ", __FILE__, __LINE__);

        // $defaultArrOptions = [
        //     'table' => 'options',
        //     'code' => 'code',
        //     'label' => 'label',
        // ];
        /**
         * Faccio iterazione dei dati letti da presentare sul report
         * Per ogni record itero tutti i campi del form con le loro proprietà
         * con il dato type_attr ottengo il dato leggibile dal codice o id scritto nel record
         */

        foreach ($records as $key => $values) {

            // foreach($formFields as $field) {
            //     if(!empty($field->type_attr)) {
            foreach ($columns as $col) {
                $fieldName = $col['field'];
                if (!isset($values[$fieldName])) {
                    //se per il campo in esame non è configurata la colonna corrispondente
                    continue;
                }

                $fieldName = $this->strip($fieldName);
                $records[$key][$fieldName] = $this->setValueByDataType($records[$key][$fieldName], (object)$col, (object)$values);
                // $this->log->info("*NEW VALUE TIPO DATO* type [{$col['type']}] fieldName [{$fieldName}] value [{$records[$key][$fieldName]}]",__FILE__,__LINE__);
            }
        }
        return $records;
    }
}
