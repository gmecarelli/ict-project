<?php

namespace Packages\IctInterface\View\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Route;

class BtnExport extends Component
{
    public $label;
    public $format;
    public $route;
    public $btn;
    public $report_id;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($label='Esporta', $format = 'xlsx', $route = 'report', $btn = 'light')
    {

        $this->label = $label;
        $this->format = $format == 'xlsx' ? 'xls' : $format;
        $this->route = Route::has('export.'.$route) ? route('export.'.$route) : url()->current();
        $this->btn = $btn;

        if(empty(request()->input('report'))) {
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
        return view('ict::components.btn-export', [
                            'label' => $this->label,
                            'format' => $this->format,
                            'route' => $this->route,
                            'btn' => $this->btn,
                            'report_id' => $this->report_id,
                                ]);
    }
}
