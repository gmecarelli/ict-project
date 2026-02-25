<?php

/**
 * DynamicField
 *
 * Componente Blade che mappa il tipo di campo dal DB (form_fields.type)
 * al corrispondente input HTML Bootstrap 5.3 con supporto Livewire wire:model.
 *
 * @author: Giorgio Mecarelli
 */

namespace Packages\IctInterface\View\Components;

use Illuminate\View\Component;

class DynamicField extends Component
{
    public array $field;
    public mixed $value;
    public string $wireModel;

    public function __construct(array $field, mixed $value = null, string $wireModel = '')
    {
        $this->field = $field;
        $this->value = $value;
        $this->wireModel = $wireModel;
    }

    public function isRequired(): bool
    {
        return str_contains($this->field['rules'] ?? '', 'required');
    }

    public function render()
    {
        return view('ict::components.dynamic-field');
    }
}
