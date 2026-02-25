<?php

namespace Packages\IctInterface\View\Components;

use Illuminate\View\Component;

class TitlePage extends Component
{
    public $count;
    public $titlePage;
    public $subTitle;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($count = [], $titlePage = '')
    {
        $this->count = $count;
        $str = explode("|", $titlePage);
        $this->titlePage = $str[0];
        $this->subTitle = isset($str[1]) ? $str[1] : '';
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        return view('ict::components.title-page', ['count' => $this->count, 'titlePage' => $this->titlePage, 'subTitle' => $this->subTitle]);
    }
}
