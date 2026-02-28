<?php

namespace Packages\IctInterface\Providers;

use Livewire\Livewire;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\ServiceProvider;
use Packages\IctInterface\Livewire\FilterFormComponent;
use Packages\IctInterface\Livewire\SearchFormComponent;
use Packages\IctInterface\Livewire\EditableFormComponent;
use Packages\IctInterface\Livewire\ChildFormComponent;
use Packages\IctInterface\Livewire\ModalFormComponent;
use Packages\IctInterface\Livewire\DeleteConfirmComponent;
use Packages\IctInterface\Livewire\UserProfileManagerComponent;
use Packages\IctInterface\Livewire\MulticheckManagerComponent;
use Packages\IctInterface\Livewire\BoolSwitchComponent;
use Packages\IctInterface\View\Components\BtnEdit;
use Packages\IctInterface\View\Components\BtnCreate;
use Packages\IctInterface\View\Components\BtnDelete;
use Packages\IctInterface\View\Components\BtnExport;
use Packages\IctInterface\View\Components\TitleForm;
use Packages\IctInterface\View\Components\TitlePage;
use Packages\IctInterface\View\Components\NavSidebar;
use Packages\IctInterface\View\Components\Pagination;
use Packages\IctInterface\View\Components\DynamicField;

class IctServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->mergeConfigFrom(
            __DIR__.'/../../config/ict.php', 'ict'
        );

        $this->app->singleton(
            \Packages\IctInterface\Controllers\Services\FormService::class
        );
        $this->app->singleton(
            \Packages\IctInterface\Controllers\Services\ReportService::class
        );
        $this->app->singleton(
            \Packages\IctInterface\Controllers\Services\MenuService::class
        );
        $this->app->singleton(
            \Packages\IctInterface\Services\DynamicFormService::class
        );
        $this->app->singleton(
            \Packages\IctInterface\Services\ActionHandlerResolver::class
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        // include $this->getPackagePath().'/routes.php';
        $this->loadRoutesFrom($this->getPackagePath().'/routes.php');
        $this->loadViewsFrom($this->getPackagePath().'/resources/views', 'ict');
        $this->loadViewComponentsAs('ict', [
            BtnCreate::class,
            BtnDelete::class,
            BtnEdit::class,
            BtnExport::class,
            NavSidebar::class,
            Pagination::class,
            TitleForm::class,
            TitlePage::class,
            DynamicField::class,
        ]);

        // Registra componenti Livewire del package
        Livewire::component('ict-filter-form', FilterFormComponent::class);
        Livewire::component('ict-search-form', SearchFormComponent::class);
        Livewire::component('ict-editable-form', EditableFormComponent::class);
        Livewire::component('ict-child-form', ChildFormComponent::class);
        Livewire::component('ict-modal-form', ModalFormComponent::class);
        Livewire::component('ict-delete-confirm', DeleteConfirmComponent::class);
        Livewire::component('ict-user-profile-manager', UserProfileManagerComponent::class);
        Livewire::component('ict-multicheck-manager', MulticheckManagerComponent::class);
        Livewire::component('ict-bool-switch', BoolSwitchComponent::class);

        if ($this->app->runningInConsole()) {
            // Publish assets
            $this->publishes([
                $this->getPackagePath().'/resources/assets' => public_path('ict-assets'),
            ], 'assets');
        }

        Paginator::useBootstrap();
        
    }

    private function getPackagePath() {
        $currentDir = basename(__DIR__) == 'Providers' ? __DIR__.'/..' : __DIR__;
        return $currentDir;
    }

}
