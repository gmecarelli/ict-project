<?php
/**
 * CLASSE DI SERVIZIO DI SUPPORTO ALLA CREAZIONE E VISUALIZZAZIONE DEL MENU' DI NAVIGAZIONE
 * @author: Giorgio Mecarelli
 */
namespace Packages\IctInterface\Controllers\Services;

use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Packages\IctInterface\Models\Menu;
use Packages\IctInterface\Support\BaseService;
use Packages\IctInterface\Controllers\Services\Logger;

class MenuService extends BaseService
{
    public $verticalMenu;

    public function __construct()
    {
        parent::__construct();
    }
    /**
     * getNavSidebar
     * Restituisce un dato con la struttura del menu
     * @return array
     */
    public function getNavSidebar() 
    {
        $this->log->info("*Recupero i menu*",__FILE__, __LINE__);

        $menus = Menu::select('id', 
                'title AS title_menu', 
                'icon', 
                'tooltip')
                    ->orderBy('order')
                    ->get();
        $this->log->sql(DB::getQueryLog(),__FILE__,__LINE__);
        
        // $menus = $this->_loadMenuRecords();
        if(empty($menus)) {
            $this->log->error("*I MENU NON SONO STATi RECUPERATI*",__FILE__, __LINE__);
            return false;
        }
        $this->log->info("*MENU CARICATO*",__FILE__, __LINE__);
        $this->log->info("*Recupero i report per il menu*",__FILE__, __LINE__);

        $menu_reports = $this->_loadReportRecords();

        if(empty($menu_reports)) {
            $this->log->error("*I REPORTS NON SONO STATI CARICATI*",__FILE__, __LINE__);
            return false;
        }
        $this->log->info("*REPORT CARICATI*",__FILE__, __LINE__);
        $menuComposer = [];
        
        foreach($menus as $menu) {
            // $menu_reports = $menuModel->find($menu->id)->reports()->orderBy('order')->get()->toArray();
            // dd($menu_reports);
            $submenu = [];
            $voice = $menu->title_menu;
            
            foreach($menu_reports as $menu_report) {
                if($menu_report->menu_id == $menu->id) {
                    $menu_report->href_url = $this->hookQueryString($menu_report);
                    $submenu[]=$menu_report;
                }
            }
            if(count($submenu) > 0) {
                $menuComposer[$voice]['label'] = $voice;
                $menuComposer[$voice]['tooltip'] = $menu->tooltip;
                $menuComposer[$voice]['icon'] = $menu->icon;
                $menuComposer[$voice]['id'] = $menu->id;
                $menuComposer[$voice]['submenu'] = $submenu;
            }
            
        }
        $this->verticalMenu = $menuComposer;
        return $menuComposer;
    }
    
    /**
     * _loadMenuRecords
     * Carica i records dei report che compongono il menu laterale
     * @return array
     */
    private function _loadReportRecords() {
        $res = DB::table('reports', 'r')
                ->select(DB::raw("r.id, 
                r.menu_id, 
                r.id AS report_id, 
                r.title AS report_title, 
                r.route, 
                r.href_url, 
                r.href_target"));
                if($this->isAdmin() == false) {
                    // $res = $res->join('profile_roles', 'report_id', '=', 'r.id')
                    $res = $res->join(DB::raw('profile_roles pr'), 'report_id', '=', 'r.id');
                              
                }
                $res = $res->where('is_show_menu', 1)
                ->where('r.is_enabled', 1);

                if($this->isAdmin() == false) {
                    $res = $res->whereIn('profile_id', session('profiles'));
                }
                $res = $res->orderBy('order')
                            ->get();
        $this->log->sql(DB::getQueryLog(),__FILE__,__LINE__);

        return $res;
    }

    private function hookQueryString($menu_report) {
        return Str::contains($menu_report->href_url, '?') ? $menu_report->href_url."&report=".$menu_report->id : $menu_report->href_url."?report=".$menu_report->id;
    }
}
