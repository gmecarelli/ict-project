<?php

namespace App\Http\Controllers\Classes;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class FinderController extends Controller
{
    public $response = [
        'result' => 'success',
        'message' => '',
        'html' => '',
    ];

    public $dbCommesse;

    public function __construct()
    {
        $this->dbCommesse = DB::connection('commesse');
    }

    /**
     * getFinderRoute
     * Restituisce la route della funzione di caricamento della ricerca
     * @return array
     */
    public function getFinderRoute()
    {
        $this->response = Arr::add($this->response, 'route', route(request('route')));
        return $this->response;
    }

    /**
     * findSupplierByLabel
     *
     * Funzione di esempio per vedere come funziona la ricerca
     *
     * @return array
     */
    public function findExample()
    {
        $results = [];
        // $lines = Supplier::where('ragione_sociale', 'like', '%' . request('query') . '%')
        //     ->where('is_enabled', 1)
        //     ->get();
        // foreach ($lines as $value) {
        //     $temp = "<i class=\"fas fa-caret-right\"></i> <a class=\"text-success\" href=\"javascript:fillSupplierId('" .
        //         addslashes($value->ragione_sociale) . "|" . $value->id . "|" . $value->code_pagamento .
        //         "')\" id=\"row_" . $value->id . "\">" . $value->ragione_sociale . "</a>";
        //     $results[] = $temp;
        // }
        $this->response['html'] = $results;
        return $this->response;
    }
    


    /**
     * advanced
     * Permette la ricerca su più campi contemporaneamente
     * @param  mixed $model
     * @return object
     */
    public function advanced($model)
    {
        preg_match("/\[([a-z_]+)\]/", request('fieldId'), $match);

        $fieldName = isset($match[1]) ? $match[1] : request('fieldId');
        dump(session()->get('find_more_fields'));
        if (session()->has('find_more_fields')) {
            $find = session('find_more_fields');
            session()->put('find_more_fields', Arr::has($find, $fieldName) ? Arr::set($find, $fieldName, request('query')) : Arr::add($find, $fieldName, request('query')));
        } else {
            session()->put('find_more_fields', [
                $fieldName => request('query')
            ]);
        }
        dump(session()->get('find_more_fields'));
        $find = session('find_more_fields');
        dd($find);
        $rows = $model->where(function ($query) use ($find) {
            $i = 0;
            foreach ($find as $field => $key) {
                if ($i == 0) {
                    $query = $query->where($field, 'like', '%' . $key . '%');
                    $i++;
                    continue;
                }
                $query = $query->where($field, 'like', '%' . $key . '%');
            }
            return $query;
        });

        return $rows;
    }
    
    
    
}
