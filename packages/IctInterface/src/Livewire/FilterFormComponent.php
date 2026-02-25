<?php

/**
 * FilterFormComponent
 *
 * Componente Livewire che sostituisce FilterForm extends Kris\LaravelFormBuilder\Form.
 * I FilterForm sono form GET che filtrano i dati di un report.
 * Sono i più semplici: nessun salvataggio DB, solo redirect con query string.
 *
 * Uso: @livewire('ict-filter-form', ['reportId' => $report['id']])
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\Livewire;

use Packages\IctInterface\Services\DynamicFormService;

class FilterFormComponent extends DynamicForm
{
    public int $reportId;
    public string $pageUrl = '';

    public function mount(int $reportId): void
    {
        $this->reportId = $reportId;

        // Salva l'URL della pagina originale durante mount()
        // (mount viene eseguito nella request della pagina, non da /livewire/update)
        $this->pageUrl = url()->current();

        $formService = app(DynamicFormService::class);

        $filterForm = $formService->getFilterForm($reportId);

        if ($filterForm) {
            $this->mountForm($filterForm->id);
            $this->submitLabel = 'Filtra';

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
        // Rimuovi i valori vuoti
        $params = array_filter($this->formData, function ($value) {
            return !is_null($value) && $value !== '';
        });

        $params['report'] = $this->reportId;
        $params['filter'] = 'Y';

        $this->redirect($this->pageUrl . '?' . http_build_query($params));
    }

    public function resetFilters(): void
    {
        // Reset di tutti i campi e redirect senza filtri
        foreach ($this->fields as $field) {
            $this->formData[$field['name']] = null;
        }

        $this->redirect($this->pageUrl . '?report=' . $this->reportId);
    }

    public function render()
    {
        return view('ict::livewire.filter-form');
    }
}
