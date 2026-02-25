<?php

namespace Packages\IctInterface\View\Components;

use Packages\IctInterface\Controllers\Services\Logger;
use Illuminate\View\Component;
use Illuminate\Support\Facades\DB;
use Packages\IctInterface\Controllers\Services\MenuService;


class NavSidebar extends Component
{
    private $log;
    /**
     * Create a new component instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->log = new Logger();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return \Illuminate\Contracts\View\View|\Closure|string
     */
    public function render()
    {

        $menu = new MenuService();
        if(request('report')) {
            $rep = DB::table('reports')
                ->select('id','menu_id')
                ->whereIn('id',[request('report')])
                // ->groupBy('id')
                ->get();
            $openMenu = $rep[0]->menu_id;
            $activeReport = $rep[0]->id;
        } else {
            $openMenu = null;
            $activeReport = null;
        }
        $this->log->info("*SELECTEDMENU* openMenu[{$openMenu}] activeReport[{$activeReport}]",__FILE__,__LINE__);

        return view('ict::components.nav-sidebar', [
            'navSidebar' => $menu->getNavSidebar(),
            'openMenu' => $openMenu,
            'activeReport' => $activeReport
            ]);
    }
    
}
