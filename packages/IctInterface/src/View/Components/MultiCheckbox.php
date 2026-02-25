<?php

namespace Packages\IctInterface\View\Components;

use Illuminate\View\Component;

class MultiCheckbox extends Component
{

    public $id;
    public $checked;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($id = null) 
    {
        $this->checked = false;
        $this->id = $id;
        if(session()->has('multiselect')) {
            $this->checked = in_array($this->id, session()->get('multiselect')) ? 'checked="true"' : '';
        }
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('ict::components.multi-checkbox', ['id' => $this->id, 'checked' => $this->checked]);
    }
}
