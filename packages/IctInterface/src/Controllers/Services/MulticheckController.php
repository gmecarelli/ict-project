<?php
/**
 * MulticheckController
 *
 * Gestisce le azioni massive (bulk actions) sulla selezione multipla tramite checkbox.
 * Rinominato da MultiselectController.
 *
 * Struttura array dropItems:
 * 'label' => null,  // label sul menu a tendina
 * 'table' => null,  // tabella dove va scritto
 * 'set' => [],      // array con le coppie "nome_campo" => valore della set
 * 'where' => [],    // array con le coppie "nome_campo" => valore della where
 * 'raw' => null,    // stringa query personalizzata (whereRaw)
 */
namespace Packages\IctInterface\Controllers\Services;

use Illuminate\Support\Arr;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Packages\IctInterface\Controllers\Services\Logger;
use Packages\IctInterface\Controllers\Services\ApplicationService;

class MulticheckController extends ApplicationService
{
    public $dropItems;

    public function __construct()
    {
        parent::__construct();
        $this->log = new Logger();
    }

    /**
     * init
     * Inizializza l'array con le azioni disponibili
     * @param  mixed $dropItems
     * @return void
     */
    public function init($dropItems)
    {
        $this->dropItems = $dropItems;
        session()->put('drop_items', $dropItems);
        return $this;
    }

    /**
     * setDropItems
     * Aggiunge una voce al dropdown menu delle action
     * @param  mixed $item
     * @return void
     */
    public function addDropItem($item) {
        return Arr::prepend($this->dropItems, $item);
    }

    /**
     * getSet
     * Restituisce l'elemento SET dell'array
     * @param  mixed $item
     * @return array
     */
    public function getSet($item) {
        return is_null($item) ? null : (array)json_decode(Arr::get($item, 'set'));
    }

    /**
     * getWhere
     * Restituisce l'elemento WHERE dell'array
     * @param  mixed $item
     * @return array|null
     */
    public function getWhere($item) {
        return is_null($item) ? null : (array)json_decode(Arr::get($item, 'where'));
    }

    /**
     * getLabel
     * Restituisce l'elemento LABEL dell'array
     * @param  mixed $item
     * @return string
     */
    public function getLabel($item) {
        return Arr::get($item, 'label');
    }

    /**
     * getTable
     * Restituisce l'elemento TABLE dell'array
     * @param  mixed $item
     * @return string
     */
    public function getTable($item) {
        return is_null($item) ? null : Arr::get($item, 'table');
    }

    public function getRoute($item) {
        return is_null($item) ? null : Arr::get($item, 'route');
    }
}
