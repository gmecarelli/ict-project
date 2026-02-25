<?php

namespace Packages\IctInterface\View\Components;

use Illuminate\View\Component;

class ChildForm extends Component
{
    public $id_child;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($id_child = null)
    {
        $this->id_child = $id_child;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('ict::components.child-form', ['id_child' => $this->id_child]);
    }
}
