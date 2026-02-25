<?php

/**
 * Classe parent di tutte le classi di servizio (ReportService e FormService)
 * 
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Controllers\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Packages\IctInterface\Support\BaseService;
use Packages\IctInterface\Models\FormField;
use Packages\IctInterface\Models\Option;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Crypt;

class ApplicationService extends BaseService
{

    public $log;            //istanza con proprietà Logger
    public $disabledJsSelect;
    public $multiple;
    public function __construct()
    {
        parent::__construct();
        $this->multiple = [];
    }

    public function getDisabledJsSelect()
    {
        return $this->disabledJsSelect;
    }

    /**
     * loadFormFieldsData
     * Carica dal db l'array con i dati (le proprietà) dei campi del form
     * @param  mixed $form_id
     * @return object
     */
    public function loadFormFieldsData($form_id, $fieldName = 'form_id')
    {
        $this->log->info("*Carico il record del form id[{$form_id}]*", __FILE__, __LINE__);
        $arr = FormField::where($fieldName, '=', $form_id)
            ->where('is_enabled', 1)
            ->orderBy('position')
            ->get();

        $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);
        $this->log->debug("*NUM ROWS* [" . count($arr) . "]", __FILE__, __LINE__);
        return $arr;
    }

    /**
     * parseReferenceTypeAttr
     * Resituisco l'array con 
     * @param  mixed $arrAttributes
     * @return mixed
     */
    public function getArrayOptions($arrAttributes, $val = null)
    {
        $defaultArrOptions = [
            'table' => 'options',
            'code' => 'code',
            'label' => 'label',
            'orderBy' => 'id',
            'order' => 'ASC',
        ];
        $arrAttributes = array_merge($defaultArrOptions, $arrAttributes);
        $reference = $arrAttributes;
        Arr::forget($reference, ['table', 'code', 'label', 'orderBy', 'order']);

        $sql_append = '';
        $sql_where_append = '';
        if (count($reference) > 0) {
            $key_reference = key($reference);
            $value_reference = current($reference);
            if ($arrAttributes['table'] == 'options') {
                $sql_append = "icon, class, ";
            } else {
                // $sql_where_append = ' AND is_enabled = 1';
            }

            foreach ($reference as $key_reference => $value_reference) {
                //anteponedo @ alla variabile reference si fa riferimento ad una variabile request()
                //ESEMPIO: table:charges,code:id,label:charges_number,activity_id:@id

                if (preg_match("/^@/", $value_reference)) {
                    $var = substr($value_reference, 1);
                    $reference[$key_reference] = request()->has($var) ? request()->get($var) : null;
                }

                //anteponedo & alla variabile reference si discrimina il campo per un valore preciso
                //ESEMPIO: table:charges,code:id,label:charges_number,activity_id:&123

                if (preg_match("/^&/", $value_reference)) {
                    $var = substr($value_reference, 1);
                    $reference[$key_reference] = $var;
                }

                //reference:# il valore discriminante diventa il valore scritto nel db nel campo field del form
                //ESEMPIO: campo field del form = a_id - table:charges,code:id,label:charges_number,activity_id:# (nella where scriverà activity_id = '[valore di a_id]')
                if ($value_reference == '#') {
                    $reference[$key_reference] = $val;
                }

                //reference:EDIT il valore diventa l'id scritto nella url (es. /strutture/1/edit) e sarà assegnato al nome del campo insiecato
                //ESEMPIO: ...structure_id:EDIT -> /strutture/1/edit -> $reference['structure_id'] = 1
                if ($value_reference == 'EDIT') {
                    if (Str::contains(url()->current(), 'edit')) {
                        $reference[$key_reference] = request()->segment(2);
                    } else {
                        Arr::forget($reference, $key_reference);
                    }
                }
            }
        }

        $sql = "SELECT {$sql_append} {$arrAttributes['code']} AS code, {$arrAttributes['label']} AS label FROM {$arrAttributes['table']}";

        if (count($reference) > 0) {
            $sql .= " WHERE ";
            $k = 1;
            foreach ($reference as $key_reference => $value_reference) {

                if (Schema::hasColumn($arrAttributes['table'], 'is_enabled') == true) {
                    $sql .= "is_enabled = 1 AND ";
                }
                $sql .= " {$key_reference}='{$value_reference}'";
                $sql .= $k < count($reference) ? " AND" : "";
                $k++;
            }
            $sql .= " {$sql_where_append}";
        } else {
            if (Schema::hasColumn($arrAttributes['table'], 'is_enabled') == true) {
                $sql .= " WHERE is_enabled = 1";
            }
        }
        $sql .= " ORDER BY {$arrAttributes['orderBy']} {$arrAttributes['order']}";
        $this->log->info("*Query recupero options field [{$sql}]", __FILE__, __LINE__);
        $arr = DB::select($sql);

        $this->log->info("*NUM ROWS* [" . count($arr) . "]", __FILE__, __LINE__);
        $choices = [];
        foreach ($arr as $choice) {
            if (!empty($sql_append)) {
                if (!is_null($choice->icon)) {
                    $choice->icon = "<i class=\"{$choice->icon}\"></i>";
                }
                if (!is_null($choice->class)) {
                    $choice->label = "<span class=\"{$choice->class}\">{$choice->label} {$choice->icon}</span>";
                } else {
                    $choice->label = $choice->icon . " " . $choice->label;
                }
            }

            $choices[$choice->code] =  $choice->label;
        }

        // $this->log->info("*choices* [".print_r($choices)."]",__FILE__, __LINE__);
        return $choices;
    }

    /**
     * setAlertMessage
     * Imposta i messaggi di alter per le azioni di insert/update/delete
     * @param  mixed $res
     * @param  mixed $id
     * @param  mixed $method
     * @return void
     */
    public function setAlertMessage($res, $id = null, $method = 'P')
    {

        if ($res) {
            $message = "Il dato è stato salvato";

            if ($method == 'P') {
                $alert = 'success';
                $message = is_null($id) ? "Il record è stato salvato con successo" : "Il record [ID: {$id}] è stato salvato con successo";
            } elseif ($method == 'D') {
                $message = "Il record [ID: {$id}] è stato eliminato";
                $alert = 'danger';
            } elseif ($method == 'C') {
                $message = "Il record [ID: {$id}] è stato disabilitato";
                $alert = 'warning';
            }
        } else {
            $message = "Dati non salvati";
            $alert = 'danger';
        }
        $this->setFlashMessages($message, $alert);
    }



    public function getArrOptions($field, $arrOptions = [])
    {
        if (Str::startsWith($field->type_attr, '#')) {
            //se metto # non passo per il db ma passo direttamente l'array dei dati della select
            $arrOptions = $this->stringToArray(substr($field->type_attr, 1));
        } else {
            $arrOptions = $this->stringToArray($field->type_attr, $arrOptions);
        }
    }

    /**
     * getDataSelect
     * Restituisce l'array per le oprions di una select
     * table:[nome valore]
     * field_code[nome campo che andrà in value della select]
     * field_label[nome campo che andrà in label della select]
     * reference[coppia campo dove cercare (key) e valore da ricercare(value)]
     * @param  mixed $paramOptions
     * @return void
     */

    public function getDataSelect($field, $choices, $model)
    {
        //parso la stringa type_attr ed ottengo un array
        return $this->formatterDataSelect($field, $choices, $model);
    }

    public function getDirectDataSelect($field, $choices, $model)
    {
        return $this->formatterDataSelect($field, $choices, $model);
    }

    public function formatterDataSelect($field, $choices, $model)
    {

        $nameField = $field->name;

        if (!empty($model)) {
            //select per un elemento da modificare
            empty($model->getAttributes()[$nameField]) ? $selected = null : $selected = $model->getAttributes()[$nameField];
        } else {
            // select per un nuovo elemento
            $selected = null;
        }

        return [
            'choices' => array_map(function ($var) {
                return $this->strip($var);
            }, $choices),
            'selected' => $selected,
            'empty_value' => Str::contains($field->attr_params, 'multiple') ? '' : '- Seleziona -',
            'multiple' => Str::contains($field->attr_params, 'multiple') ? true : false

        ];
    }

    public function strip($value)
    {
        return trim(strip_tags($value));
    }

    /**
     * loadItemsList
     * Carica gli items di un genitore
     * @param  stdClass $Model
     * @param  int $id
     * @return stdClass
     */
    public function loadItemsList($Model, $id, $report_id, $fieldReference = 'report_id', $sqlFields = ['*'])
    {

        $this->log->info("*CARICO LA LISTA DEGLI ITEMS* (function [" . __FUNCTION__ . "])", __FILE__, __LINE__);
        $this->log->debug("id[{$id}] report_id[{$report_id}] reference[{$fieldReference}]", __FILE__, __LINE__);

        $records = $Model->select($sqlFields)
            ->where($fieldReference, '=', $id);
        if (Schema::hasColumn($Model->getTable(), 'is_enabled') == true) {
            $records = $records->where('is_enabled', '=', 1);
        }
        if (Schema::hasColumn($Model->getTable(), 'position') == true) {
            $records = $records->orderBy('position');
        }
        $records = $records->get()
            ->toArray();
        $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);

        $cols = DB::table('report_columns')
            ->where('report_id', '=', $report_id)
            ->orderBy('position')
            ->get()
            ->toArray();
        $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);

        $data = [
            'cols' => [],
            'records' => [],
        ];

        foreach ($records as $key => $record) {

            foreach ($cols as $col) {
                //popolo subito il nuovo array con il valore originale scritto in tabella
                $fieldName = $col->field;

                if (!isset($record[$fieldName])) {
                    $record = Arr::add($record, $fieldName, null);
                }
                $data['records'][$key][$fieldName] = $record[$fieldName];

                //alla prima iterazione popolo l'array delle intestazioni delle colonne
                if ($key == 0) {
                    $data['cols'][] = $col->label;
                }

                if (!isset($record[$fieldName])) {
                    //se per il campo in esame non è configurata la colonna corrispondente
                    continue;
                }

                $data['records'][$key][$fieldName] = $this->setValueByDataType($data['records'][$key][$fieldName], $col, $Model->find($record['id']));
            }
        }

        return $data;
    }

#################################################################

# FUNZIONI DI GESTIONE DEI TIPI DI DATI

#################################################################

    /**
     * setValueByDataType
     * Imposta e ottimizza il valore visualizzato nel report in base al tipo
     * (campo della tabella report_columns type)
     * @param  mixed $value
     * @param  mixed $col
     * @return void
     */
    public function setValueByDataType($value, $col, $model)
    {
        $func = '_' . $col->type;

        // $this->log->debug("*FUNC TIPO DATO* [{$func}]",__FILE__,__LINE__);
        if (method_exists($this, $func)) {

            if ($col->is_crypted == 1) {

                $value = $this->_decrypt($value);
            }
            return $func == '_switch' || $func == '_match' ? $this->$func($value, $col, $model) : $this->$func($value, $col);
        } else {
            if ($col->is_crypted == 1) {
                dd('qui');
                $value = $this->_decrypt($value);
            }
            return $this->_string($value);
        }
    }

    /**
     * _relstring
     * restituisce in formato stringa i valori di una relazione n:m o 1:n o 1:1. filter e findfield sono opzionali
     * @params['model] = indica il model da usare per la relazione. Questo viene ricavato con la find (se non è indicato findfield), quindi si da per scontato che il campo della colonna è l'id parent
     * @params['findfield] = indica il campo per recuperare il model alternativo al campo id
     * @params['function'] = indica la funzione da usare scritta nel model per recuperare i dati (es: queries ($concorso->queries()) )
     * @params['filter'] (solo per relazioni n:m) = indica il nome del campo sul quale va fatta la whereCondition sulla funzione di relazione del model (es: concorso_id)
     * @params['field'] = indica il campo da stampare sulla stringa di output (es: label)
     * @param  mixed $value (il valore nel db corrispondente al campo di riferimento della colonna)
     * @param  mixed $col (l'oggetto' colonna del report)
     * @return void
     */
    public function _relations($value, $col)
    {
        $params = $this->stringToArray($col->type_params);
        if (!Arr::has($params, ['model', 'function', 'field'])) {
            return '';
        }
        $findfield = $params['findfield'] ?? null;

        $model = is_null($findfield) ? "\\App\\Models\\{$params['model']}"::find($value) : "\\App\\Models\\{$params['model']}"::where($findfield, $value)->first();
        $func = $params['function'];
        $field = $params['field'];
        $str = '';
        if (isset($params['filter'])) {
            //per relazioni n:m
            $arrModel = $model->$func()->where($params['filter'], $value)->get();
            foreach ($arrModel as $m) {
                $str .= $m->$field . ', ';
            }
        } elseif (isset($model->$func[0])) {
            //per relazioni 1:n
            foreach ($model->$func as $element) {
                $str .= isset($element->$field) ? $element->$field . ", " : 'N/A, ';
            }
        } else {
            //per relazioni 1:1
            $str = isset($model->$func->$field) ? $model->$func->$field . ", " : 'N/A, ';
        }

        return Str::beforeLast($str, ', ');
    }
    /**
     * _stoplight
     * Permette di visulizzare il valore rosso o verde dato un limit di riferimento.
     * PARAMETRI DA PASSARE:
     * limit: il valore di diferimento
     * type: il tipo di valore visualizzato (intero, valuta, etc)
     * @param  mixed $value
     * @param  mixed $col
     * @return void
     */
    public function _stoplight($value, $col)
    {
        $params = empty($col->type_params) ? [] : $this->stringToArray($col->type_params);
        if (!Arr::has($params, ['limit', 'type'])) {
            $params = [
                'limit' => 0,
                'type' => '_integer'
            ];
        }

        $limit = $params['limit'];

        if ($value < $limit) {
            return '<span class="text-danger">' . $this->{$params['type']}($value) . '</span>';
        }

        return '<span class="text-success">' . $this->{$params['type']}($value) . '</span>';
    }

    public function _match($value, $col, $model)
    {
        $params = $this->stringToArray($col->type_params);
        if (!Arr::has($params, ['field_match'])) {
            return $value;
        }
        $field = $params['field_match'];

        $down = Arr::has($params, ['down']) ? $params['down'] : 70;
        $medium = Arr::has($params, ['medium']) ? $params['medium'] : 90;
        $high = Arr::has($params, ['high']) ? $params['high'] : 100;

        $divisor = empty($model->$field) ? 1 : $model->$field;
        $perc = ceil(($value * 100) / $divisor);

        if ($perc <= $down) {
            return '<span class="text-danger" title="Valore inferiore al ' . $down . '%">' . $value . '</span>';
        }
        if (($perc <= $medium && $perc > $down)) {
            return '<span class="text-primary" title="Valore tra il ' . $down . '% e il ' . $medium . '%">' . $value . '</span>';
        }
        if (($perc > $medium && $perc <= $high)) {
            return '<span class="text-success" title="Valore tra il ' . $medium . '% e il ' . $high . '%">' . $value . '</span>';
        }
        if (($perc > $high)) {
            return '<span class="text-warning" title="Valore superiore al ' . $high . '%">' . $value . '</span>';
        }
        return $value;
    }

    /**
     * _date
     * Formata il tipo di dato "date"
     * Formata il tipo di dato "date"
     * @param  mixed $value
     * @param  mixed $col
     * @return string
     */
    public function _date($value, &$col = null)
    {
        if ($value == '0000-00-00' || empty($value)) {
            return '';
        }
        $attributes = $this->setAttributesString($value, $col);
        $date = date_create($value);
        return "<span {$attributes}>" . (string)$date->format('d/m/Y') . "</span>";
    }

    public function _dateTime($value, &$col = null)
    {
        if ($value == '0000-00-00 00:00:00' || empty($value)) {
            return '';
        }
        $attributes = $this->setAttributesString($value, $col);
        $date = date_create($value);
        return "<span {$attributes}>" . (string)$date->format('d/m/Y H:i:s') . "</span>";
    }

    /**
     * _switch
     * Inserisce un switch nel report (Si/No). Nella configurazioni in params si deve specificare la tabella ( table:concorsi)
     * Usa Livewire.dispatch('toggle-bool-switch') → BoolSwitchComponent (sostituisce jQuery .boolswitch + $.ajax)
     * @param  mixed $value
     * @param  mixed $col
     * @param  mixed $model
     * @return string
     */
    public function _switch($value, &$col, $model)
    {
        $params = $this->stringToArray($col->type_params);

        $checked = $value == 1 ? 'checked ' : '';
        $field = $col->field;
        $table = $params['table'];
        $id = $model->id;

        $switch = '<div class="form-check form-switch" id="switch-' . $id . '">'
            . '<input type="checkbox" class="form-check-input" '
            . 'id="' . $field . '-' . $id . '" ' . $checked
            . 'onchange="Livewire.dispatch(\'toggle-bool-switch\', {id: ' . $id
            . ', field: \'' . $field
            . '\', table: \'' . $table
            . '\', value: this.checked ? 1 : 0})">'
            . '<label class="form-check-label" for="' . $field . '-' . $id . '"></label>'
            . '</div>';
        return $switch;
    }

    /**
     * _int
     * Formatta il dato numerico da visualizzare nel report
     * @param  mixed $value
     * @param  mixed $col
     * @return string
     */
    public function _int($value, $col = null)
    {
        $attributes = $this->setAttributesString(intval($value), $col);
        return "<span " . $attributes . ">" . intval($value) . "</span>";
    }

    /**
     * _string
     * Tipo di dato di default, ovvero visualizzato come scritto sul db
     * @param  mixed $value
     * @param  mixed $col
     * @return string
     */
    public function _string($value, $col = null)
    {
        $attributes = $this->setAttributesString($value, $col);
        return "<span " . $attributes . ">" . $value . "</span>";
    }

    /**
     * _currency
     * Visualizza il numero in formato valuta
     * @param  mixed $value
     * @param  mixed $col
     * @return void
     */
    public function _currency($value, $col = null)
    {
        $attributes = $this->setAttributesString($value, $col);
        return "<span " . $attributes . ">" . '€ ' . $this->_float($value, 2) . "</span>";
    }

    /**
     * _enum
     * Tipo di dato enum dove il dato visualizzato viene preso da un'altra tabella
     * @param  mixed $value
     * @param  mixed $col
     * @return string
     */
    public function _enum($value, $col)
    {

        if (!empty($col->type_params)) {
            $defaultArrOptions = [
                'table' => 'options',
                'code' => 'code',
                'label' => 'label',
            ];
            //matrice con tutti i valori di tutti i campi del record form_fields
            $attr = $this->stringToArray($col->type_params, $defaultArrOptions);

            //Array di traduzione con la coppia [id_o_codice_del_report]->[label_del_valore_su_tabella_di_relazione]
            $transValues = $this->getArrayOptions($attr, $value);

            //se il dato non è disponibile (caso di dato facoltativo) non mando in errore e visualizzo N/A (not available)
            if (!isset($transValues[$this->strip($value)])) {
                $transValues[$this->strip($value)] = 'N/A';
            }
            // $this->log->info("*RETURN METHOD _enum* [{$transValues[trim(strip_tags($value))]}]",__FILE__,__LINE__); 
            return $transValues[$this->strip($value)];
        }
    }

    public function _array($value, $col = null)
    {
        // $arr = $this->_enum($value, $col);
        $defaultArrOptions = [
            'table' => 'options',
            'code' => 'code',
            'label' => 'label',
        ];
        //matrice con tutti i valori di tutti i campi del record form_fields
        $attr = $this->stringToArray($col->type_params, $defaultArrOptions);

        //Array di traduzione con la coppia [id_o_codice_del_report]->[label_del_valore_su_tabella_di_relazione]
        $transValues = $this->getArrayOptions($attr, $value);

        //se il dato non è disponibile (caso di dato facoltativo) non mando in errore e visualizzo N/A (not available)
        if (!isset($transValues[$this->strip($value)])) {
            $transValues[$this->strip($value)] = 'N/A';
            Arr::forget($transValues, $this->strip($value));
        }

        $value = implode(', ', $transValues);

        return $value;
    }

    /**
     * _directlink
     * Crea e restituisce il link diretto ad una risorsa esterna
     * @param  mixed $value
     * @param  mixed $col
     * @return void
     */
    public function _directlink($value, $col) {
        if(!empty($col->type_params)) {
            $attr = $this->stringToArray($col->type_params);
        }
        if(!isset($attr['title'])) {
            $attr = ['title' => 'Vai al link'];
        }
        return '<a href="'.$value.'" title="'.$attr['title'].'" target="_blank">'.$attr['title'].' <i class="fas fa-external-link-alt"></i></a>';
        
    }

    /**
     * _integer
     * Tipo di dato intero che formatta il numero con i separatori delle migliaia
     * @param  mixed $value
     * @param  mixed $col
     * @return string
     */
    public function _integer($value, $col = null, $decimals = 0)
    {
        return number_format(intval($value), $decimals, ',', '.');
    }

    /**
     * setAttributesString
     * Imposta la stringa degli attributi sul tag del valore in tabella (es: class="text-success" title="test")
     * @param  mixed $value
     * @param  mixed $col
     * @return void
     */
    public function setAttributesString($value, $col)
    {
        $attributes = [];
        if (!empty($col->type_params)) {
            $attr = $this->stringToArray($col->type_params);
            foreach ($attr as $key => $val) {
                $attributes[] = $key . '="' . $val . '"';
            }
        } else {
            $attributes[] = 'title="' . $value . '"';
        }
        return implode(' ', $attributes);
    }

    /**
     * _float
     * Tipo di dato numero decimale che formatta il numero con i separatori decimali e delle migliaia
     * @param  mixed $value
     * @return void
     */
    public function _float($value, $col = null)
    {
        return number_format($value, 2, ',', '.');
        // return $this->_integer($value, null, 2);
    }

    /**
     * _percent
     * Tipo di dato percentuale che formatta il numero con i separatori decimali e delle migliaia
     * @param  mixed $value
     * @param  mixed $col
     * @return void
     */
    public function _percent($value, $col = null)
    {
        // $attr = $this->stringToArray($col->type_params);
        $attributes = $this->setAttributesString($value, $col);
        return "<span " . $attributes . ">" . $this->_integer($value, null, 0) . "%</span>";
    }

    public function _decrypt($value, $col = null)
    {
        if (isset($value)) {
            $value = Crypt::decryptString($value);

            return $value;
        }
    }
    public function _encrypt($value, $col = null)
    {
        return Crypt::encryptString($value);
    }
    /**
     * _link
     *  Crea il dato da visulizzare sotto forma di link che punta ad un altro report
     * default options = [
     *       'route' => null,
     *       'filter' => null,
     *       'report_id' => null,
     *       'form_id' => null,
     *       'target' => '_blank',
     *       'title' => '',
     *       'text' => null,
     *   ]
     * @param  mixed $value
     * @param  mixed $col
     * @return void
     */
    public function _link($value, $col)
    {
        $defaultArrOptions = [
            'route' => null,
            'filter' => null,
            'report_id' => null,
            'form_id' => null,
            'target' => '_self',
            'title' => '',
            'text' => null,
        ];
        // SCRIVERE FUNCTION PER RICAVARE IL REPORT_ID ED IL FORM_ID

        //matrice con tutti i valori di tutti i campi del record form_fields
        $attr = $this->stringToArray($col->type_params);

        $defaultArrOptions = $this->_setFormDataParams($attr['route'], $defaultArrOptions);

        $attr = array_merge($defaultArrOptions, $attr);
        // dd($attr);
        if (
            is_null($attr['route']) ||
            is_null($attr['report_id']) ||
            is_null($attr['filter']) ||
            is_null($attr['form_id'])
        ) {
            return $value;
        }
        $attr['filter'] .= "=" . $value . "&filter=Y";
        return $this->_urlFilterComposer($attr, $value);
    }

    /**
     * _alert
     * Definisce il colore del dato sul report in base ad un confronto di dati su 2 tabelle
     * APPLICATO SOLO SU DATI NUMERICI
     * m_table (tabella master)
     * m_foreign (nome campo di ricerca master)
     * m_raw (espressione sql della select master)
     * s_table (tabella slave)
     * s_foreign (nome campo di ricerca slave)
     * s_raw (espressione sql della select slave)
     * @param  mixed $value
     * @param  mixed $col
     * @return void
     */
    public function _alert($value, $col)
    {
        $attr = $this->stringToArray($col->type_params);
        $master = Arr::get(DB::table($attr['m_table'])
            ->selectRaw($attr['m_raw'] . " AS sum")
            ->where($attr['m_foreign'], '=', $value)
            ->groupBy($attr['m_foreign'])
            ->get()
            ->toArray(), 0);
        $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__, "master[{$master->sum}]");
        $slave = Arr::get(DB::table($attr['s_table'])
            ->selectRaw($attr['s_raw'] . " AS sum")
            ->where($attr['s_foreign'], '=', $value)
            ->groupBy($attr['s_foreign'])
            ->get()
            ->toArray(), 0);

        $totalMaster = is_null($master) ? 0 : $master->sum;
        $totalSlave = is_null($slave) ? 0 : $slave->sum;

        $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__, "slave[{$totalSlave}]");
        // dd($slave);
        //verifico che i dati siano numerici
        if (is_numeric($totalMaster) && is_numeric($totalSlave)) {
            // calcolo la precentuale dello slave (dato di contronto)
            // rispetto al master (dato da visualizzare)
            $percent = $totalSlave == 0 ? 0 : $percent = 100 / ($totalSlave / $totalMaster);

            // $this->log->debug("*PERCENTUALE* [{$percent}]",__FILE__,__LINE__);
            if ($percent >= 50 && $percent <= 70) {
                return '<span class="text-info" title="Rapporto al ' . (int)$percent . '%">' . $totalMaster . '</span>';
            } elseif ($percent >= 70 && $percent < 100) {
                return '<span class="text-warning" title="Rapporto al ' . (int)$percent . '%">' . $totalMaster . '</span>';
            } elseif ($percent >= 100) {
                return '<span class="text-danger" title="Rapporto al ' . (int)$percent . '%">' . $totalMaster . '</span>';
            } else {
                return '<span class="text-success" title="Rapporto al ' . (int)$percent . '%">' . $totalMaster . '</span>';
            }
        } else {
            return $this->_string($totalMaster);
        }
    }

    /**
     * _thumb
     * Display the thumbnail of image attach
     * @param  mixed $value
     * @param  mixed $col
     * @return void
     */
    private function _thumb($value, $col)
    {
        $attr = $this->stringToArray($col->type_params);
        $w = $attr['w'];
        $h = $attr['h'];
        $title = isset($attr['title']) && !empty($attr['title']) ? $attr['title'] : 'Immagine prodotto';
        $style = empty($h) ? "width:{$w}px" : "width:{$w}px;heigth:{$h}px";
        return '<img src="' . $value . '" title="' . $title . '" style="' . $style . '" />';
    }

    /**
     * _urlFilterComposer
     * compone la url del link
     * @param  mixed $attr
     * @return void
     */
    private function _urlFilterComposer($attr, $value)
    {
        is_null($attr['text']) ? $text = $value : $text = $attr['text'];
        $href = url($attr['route']) . "?report={$attr['report_id']}&form_id={$attr['form_id']}&{$attr['filter']}";
        return '<a href="' . $href . '" title="' . $attr['title'] . '" target="' . $attr['target'] . '">' . $text . ' <i class="fas fa-external-link-alt"></i></a>';
    }

    /**
     * _setFormDataParams
     * Imposta report_id e form_id delle colonne link
     * @param  mixed $route
     * @param  mixed $defaultArrOptions
     * @return mixed
     */
    private function _setFormDataParams($route, $defaultArrOptions)
    {
        $formData = Arr::get(
            DB::table('forms')
                ->where('name', $route)
                ->where('type', 'filter')
                ->get()
                ->toArray(),
            0
        );
        // $this->log->sql(DB::getQueryLog(),__FILE__,__LINE__);

        if (!is_null($formData)) {
            if (is_array($formData)) {
                $defaultArrOptions['report_id'] = $formData['report_id'];
                $defaultArrOptions['form_id'] = $formData['id'];
            } else {
                $defaultArrOptions['report_id'] = $formData->report_id;
                $defaultArrOptions['form_id'] = $formData->id;
            }
        }

        return $defaultArrOptions;
    }

    /**
     * loadInfoStatuses
     * Carica le info rispetto al code e reference dato dalla tabella record statuses
     * @param  mixed $codeValue
     * @param  mixed $referenceValue
     * @return stdClass
     */
    public function loadInfoStatuses($codeValue, $referenceValue)
    {
        $obj = Option::where('code', $codeValue)
            ->where('reference', $referenceValue)
            ->first();
        $this->log->sql(DB::getQueryLog(), __FILE__, __LINE__);
        return $obj;
    }

    /**
     * loadDataSupport
     * Carica i dati di una tabella 
     * in base ad un valore passato ed ad un campo passato (default: id)
     * @param  mixed $value
     * @param  mixed $table
     * @param  mixed $fieldName
     * @return stdClass
     */
    public function loadDataSupport($value, $table = 'orders', $fieldName = 'id')
    {
        return Arr::get(
            DB::table($table)
                ->where($fieldName, $value)
                ->get()
                ->toArray(),
            0
        );
    }
}
