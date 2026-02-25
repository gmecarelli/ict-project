<?php

namespace Packages\IctInterface\View\Components;

use Illuminate\View\Component;

class BtnCreate extends Component
{
    public $has;
    public $label;
    public $route;
    public $report_id;
    public $querystring;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($label = '', $has=1, $route='/', $querystring = '')
    {
        $this->label = $label;
        $this->has = $has;
        $this->route = $route;
        $this->querystring = $querystring;
       
        if(empty(request()->input('report'))) {
            $this->has=0;
        } else {
            $this->report_id = request()->input('report');
        } 
        $this->report_id = request()->input('report');
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        if($this->has==0) {
            return '';
        }
        return view('ict::components.btn-create', ['has' => $this->has, 'label' => $this->label, 'route' => $this->route, 'report_id' => $this->report_id, 'querystring' => $this->querystring]);
    }
}
