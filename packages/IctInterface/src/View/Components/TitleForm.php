<?php

namespace Packages\IctInterface\View\Components;

use Illuminate\Support\Str;
use Illuminate\View\Component;
use Illuminate\Support\Facades\DB;

class TitleForm extends Component
{
    public $titleForm;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct($customTitle = null)
    {
        
        // Se viene passato un titolo personalizzato, lo usiamo
        $this->titleForm = $customTitle ?? null;
    }


    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {
        $titleForm = "";
        if(is_null($this->titleForm)){
            if(request()->input('report')) {
                $obj = DB::table('reports')->select('title')->find(request('report'));
                $titleForm = Str::before($obj->title, "|");
    
                if(Str::contains(request()->getPathInfo(),'edit')) {
                    $this->titleForm = "{$titleForm}: modifica record [ID:".Str::between(request()->path(),'/','/')."]";
                } else {
                    $this->titleForm = "{$titleForm}: nuovo record";
                }
            }
        }
      
        return view('ict::components.title-form', ['titleForm' => $this->titleForm]);
    }
}
