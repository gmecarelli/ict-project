<?php

namespace Packages\IctInterface\View\Components;

use Illuminate\View\Component;

class Pagination extends Component
{
    public $pages;
//    public $report_id;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($pages = [])
    {
        $this->pages = $pages;
        // $this->report_id = $report_id;
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('ict::components.pagination', ['pages' => $this->pages]);
    }
}
