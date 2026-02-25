<?php

namespace Packages\IctInterface\View\Components;

use Illuminate\View\Component;

class BtnDelete extends Component
{
    public $label;
    public $route;
    public $has;
    public $id;
    public $class;
    public $report_id;

    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($label='', $route='/', $has = 1, $id=0, $class='cancel')
    {
        $this->label = $label;
        $this->has = $has;
        $this->route = $route;
        $this->id = $id;
        $this->class = $class;

        if(empty(request()->input('report')) || $this->id == 0) {
            $this->has=0;
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
        // dd($this->id);
        return view('ict::components.btn-delete', [
                'has' => $this->has,
                'label' => $this->label,
                'route' => $this->route,
                'report_id' => $this->report_id,
                'class' => $this->class,
                'id'=> $this->id
            ]);
    }
}
