<?php

/**
 * SearchFormComponent
 *
 * Componente Livewire per i SearchForm.
 * Identico ai FilterForm ma con il campo hidden search=on.
 *
 * Uso: @livewire('ict-search-form', ['reportId' => $report['id']])
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Livewire;

use Packages\IctInterface\Services\DynamicFormService;

class SearchFormComponent extends FilterFormComponent
{
    public function mount(int $reportId): void
    {
        $this->reportId = $reportId;

        // Salva l'URL della pagina originale durante mount()
        // (mount viene eseguito nella request della pagina, non da /livewire/update)
        $this->pageUrl = url()->current();

        $formService = app(DynamicFormService::class);

        $searchForm = $formService->getSearchForm($reportId);

        if ($searchForm) {
            $this->mountForm($searchForm->id);
            $this->submitLabel = 'Ricerca';

            // Pre-popola dai parametri GET correnti
            foreach ($this->fields as $field) {
                if (request()->filled($field['name'])) {
                    $this->formData[$field['name']] = request($field['name']);
                }
            }
        }
    }

    public function submit(): void
    {
        $params = array_filter($this->formData, function ($value) {
            return !is_null($value) && $value !== '';
        });

        $params['report'] = $this->reportId;
        $params['filter'] = 'Y';
        $params['search'] = 'on';

        $this->redirect($this->pageUrl . '?' . http_build_query($params));
    }

    public function render()
    {
        return view('ict::livewire.filter-form');
    }
}
