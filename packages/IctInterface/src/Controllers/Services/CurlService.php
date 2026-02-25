<?php
/**
 * Classe che gestisce le chiatate Curl
 * 
 * @author: Giorgio Mecarelli
 * 
 */
namespace App\Http\Controllers\Services;

use Illuminate\Support\Arr;
use Packages\IctInterface\Controllers\Services\ApplicationService;

class CurlService extends ApplicationService
{
    public $client;
    private $_params;
    public $_opt;

     public function __construct() {
        $this->client = curl_init();
        $this->_opt = false;
        $this->_params = '';
    }

    public function setOptions($method, $headers) {
        if($method == 'GET') {
            curl_setopt($this->client, CURLOPT_POST, 0);
        } else {
            curl_setopt($this->client, CURLOPT_POST, 1);
        } 
        curl_setopt($this->client, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->client, CURLOPT_HTTPHEADER, $headers);
    }
    
    /**
     * call
     * Esegue la chiamata alla url desiderata
     * @param  mixed $url
     * @param  mixed $params
     * @param  mixed $method
     * @param  mixed $headers
     * @return void
     */
    public function call($url, $params = [], $method='GET', $headers = [
                                                                'Content-type: text/plain',
                                                                'Accept: application/json',
                                                                ]){
        $this->setOptions($method, $headers);
        $this->setUrl($url, $params);
        $output = curl_exec($this->client);
 
        @curl_close($this->client);
        return $output;
    }

    public function setParams($params) {
        if(!empty(Arr::first($params))) {
            $queryString = Arr::query($params);
            $this->_params = '?'.$queryString;
        }
    }

    public function setUrl($url, $params = []) {
        $this->setParams($params);
        $url .= $this->_params;
        curl_setopt($this->client, CURLOPT_URL, $url);
    }
}
