@switch($field['type'])
    @case('text')
        @php
            $isFinder = str_contains($field['class'] ?? '', 'finder');
            $finderRoute = $field['data-route'] ?? null;
            $finderMap = $field['map'] ?? '';
        @endphp
        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
        @if($isFinder && $finderRoute)
            {{-- Campo Finder con Alpine.js (sostituisce finder.blade.php jQuery) --}}
            <div x-data="finderField('{{ $wireModel }}', '{{ route($finderRoute, ['report' => request('report')]) }}', '{{ $finderMap }}', '{{ $field['name'] }}')"
                 @click.outside="isOpen = false"
                 class="position-relative">
                <input type="text"
                    class="{{ $field['class'] ?? 'form-control' }}"
                    x-model="query"
                    x-on:input.debounce.300ms="search()"
                    id="{{ $field['name'] }}"
                    placeholder="{{ $field['default_value'] ?? '' }}"
                    autocomplete="off"
                    {{ $isRequired() ? 'required' : '' }}
                >
                <div x-show="isOpen && results.length > 0" x-transition
                     class="list-group position-absolute w-100 shadow-sm"
                     style="z-index:1050;max-height:250px;overflow-y:auto;">
                    <template x-for="(item, index) in results" :key="index">
                        <a href="#"
                           class="list-group-item list-group-item-action"
                           x-on:mousedown.prevent="selectItem(item)"
                           x-text="item.display">
                        </a>
                    </template>
                </div>
            </div>
        @else
            <input type="text"
                @foreach($field['attr'] ?? [] as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach
                wire:model="{{ $wireModel }}"
                id="{{ $field['name'] }}"
                placeholder="{{ $field['default_value'] ?? '' }}"
                {{ $isRequired() ? 'required' : '' }}
            >
        @endif
        @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
        @break

    @case('select')
        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
        <select class="form-select @error($wireModel) is-invalid @enderror"
            wire:model="{{ $wireModel }}"
            id="{{ $field['name'] }}"
            {{ str_contains($field['attr_params'] ?? '', 'multiple') ? 'multiple' : '' }}
            {{ $isRequired() ? 'required' : '' }}
        >
            @unless(str_contains($field['attr_params'] ?? '', 'multiple'))
                <option value="">- Seleziona -</option>
            @endunless
            @foreach($field['options'] as $code => $optionLabel)
                <option value="{{ $code }}">{{ strip_tags($optionLabel) }}</option>
            @endforeach
        </select>
        @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
        @break

    @case('date')
        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
        <input type="date"
            @foreach($field['attr'] ?? [] as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach
            wire:model="{{ $wireModel }}"
            id="{{ $field['name'] }}"
            {{ $isRequired() ? 'required' : '' }}
        >
        @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
        @break

    @case('daterange')
        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
        <div x-data="dateRangeField('{{ $wireModel }}')"
             @click.outside="isOpen = false"
             class="position-relative">
            <div class="input-group">
                <input type="text"
                    class="{{ $field['class'] ?? 'form-control' }}"
                    x-model="displayValue"
                    @click="toggleCalendar()"
                    id="{{ $field['name'] }}"
                    placeholder="dd/mm/yyyy - dd/mm/yyyy"
                    readonly
                    {{ $isRequired() ? 'required' : '' }}
                >
                <button type="button" class="btn btn-outline-secondary" @click="clear()" x-show="displayValue" title="Pulisci">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            {{-- Dropdown calendario --}}
            <div x-show="isOpen" x-transition.opacity class="daterange-dropdown shadow">
                <div class="daterange-presets">
                    <div class="daterange-presets-title">Range rapidi</div>
                    <a href="#" @click.prevent="setPreset('today')" class="daterange-preset-item">Oggi</a>
                    <a href="#" @click.prevent="setPreset('yesterday')" class="daterange-preset-item">Ieri</a>
                    <a href="#" @click.prevent="setPreset('last7')" class="daterange-preset-item">Ultimi 7 giorni</a>
                    <a href="#" @click.prevent="setPreset('last30')" class="daterange-preset-item">Ultimi 30 giorni</a>
                    <a href="#" @click.prevent="setPreset('thisMonth')" class="daterange-preset-item">Questo mese</a>
                    <a href="#" @click.prevent="setPreset('lastMonth')" class="daterange-preset-item">Mese scorso</a>
                    <a href="#" @click.prevent="setPreset('thisYear')" class="daterange-preset-item">Quest'anno</a>
                </div>
                <div class="daterange-calendar">
                    <div class="daterange-nav">
                        <button type="button" @click="prevMonth()">&laquo;</button>
                        <span class="daterange-month-label" x-text="monthLabel"></span>
                        <button type="button" @click="nextMonth()">&raquo;</button>
                    </div>
                    <div class="daterange-weekdays">
                        <span>Lu</span><span>Ma</span><span>Me</span><span>Gi</span><span>Ve</span><span>Sa</span><span>Do</span>
                    </div>
                    <div class="daterange-days">
                        <template x-for="(dayObj, idx) in calendarDays" :key="idx">
                            <span
                                :class="dayClass(dayObj)"
                                @click="selectDay(dayObj)"
                                @mouseenter="hoverDate = dayObj.date"
                                x-text="dayObj.day"
                            ></span>
                        </template>
                    </div>
                    <div class="daterange-footer">
                        <small class="text-muted" x-show="selectingEnd">Seleziona la data di fine</small>
                        <button type="button" class="btn btn-sm btn-outline-secondary" @click="clear()">Pulisci</button>
                    </div>
                </div>
            </div>
        </div>
        @error($wireModel) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        @break

    @case('textarea')
        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
        <textarea 
            @foreach($field['attr'] ?? [] as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach
            wire:model="{{ $wireModel }}"
            id="{{ $field['name'] }}"
            rows="3"
            {{ $isRequired() ? 'required' : '' }}
        ></textarea>
        @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
        @break

    @case('hidden')
        <input type="hidden" wire:model="{{ $wireModel }}" value="{{ $value }}">
        @break

    @case('number')
        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
        <input type="number"
            @foreach($field['attr'] ?? [] as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach
            wire:model="{{ $wireModel }}"
            id="{{ $field['name'] }}"
            {{ $isRequired() ? 'required' : '' }}
        >
        @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
        @break

    @case('file')
        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
        <input type="file"
            @foreach($field['attr'] ?? [] as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach
            wire:model="{{ $wireModel }}"
            id="{{ $field['name'] }}"
            {{ $isRequired() ? 'required' : '' }}
        >
        @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
        @break
    @case('multiselect')
        <label class="form-label">{{ $field['label'] }}</label>
        <div x-data="multiSelectField(@entangle($wireModel), {{ json_encode(array_map(fn($v) => trim(strip_tags($v)), $field['options'])) }})"
            class="position-relative">
            <!-- Chips delle selezioni + input ricerca -->
            <div class="form-control d-flex flex-wrap gap-1 cursor-pointer" @click="open = !open" style="min-height:38px">
                <template x-for="val in selected" :key="val">
                    <span class="badge bg-primary d-flex align-items-center gap-1">
                        <span x-text="getLabel(val)"></span>
                        <i class="fas fa-times" style="cursor:pointer" @click.stop="toggle(val)"></i>
                    </span>
                </template>
                <input type="text" x-model="search" @click.stop="open = true"
                    class="border-0 flex-grow-1" style="outline:none;min-width:80px"
                    placeholder="Cerca...">
            </div>
            <!-- Dropdown opzioni -->
            <div x-show="open" @click.away="open = false" class="dropdown-menu show w-100" style="max-height:250px;overflow-y:auto;padding-left:10px">
                <div class="dropdown-item" @click="selectAll()">
                    <input type="checkbox" :checked="allSelected" class="form-check-input me-2"> Seleziona tutto
                </div>
                <div class="dropdown-divider"></div>
                <template x-for="(label, code) in filteredOptions" :key="code">
                    <div class="dropdown-item" @click="toggle(code)">
                        <input type="checkbox" :checked="selected.includes(code)" class="form-check-input me-2">
                        <span x-text="label"></span>
                    </div>
                </template>
            </div>
        </div>
        @error($wireModel) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        @break
    @case('checkbox')
        <div class="form-check">
            <input type="checkbox"
                class="form-check-input @error($wireModel) is-invalid @enderror"
                wire:model="{{ $wireModel }}"
                id="{{ $field['name'] }}"
            >
            <label class="form-check-label" for="{{ $field['name'] }}">{{ $field['label'] }}</label>
            @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
        </div>
        @break

    @case('radio')
        <label class="form-label d-block">{{ $field['label'] }}</label>
        @foreach($field['options'] as $code => $optionLabel)
            <div class="form-check form-check-inline">
                <input type="radio"
                    class="form-check-input"
                    wire:model="{{ $wireModel }}"
                    value="{{ $code }}"
                    id="{{ $field['name'] }}_{{ $code }}"
                >
                <label class="form-check-label" for="{{ $field['name'] }}_{{ $code }}">{{ strip_tags($optionLabel) }}</label>
            </div>
        @endforeach
        @error($wireModel) <div class="invalid-feedback d-block">{{ $message }}</div> @enderror
        @break

    @case('email')
        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
        <input type="email"
            @foreach($field['attr'] ?? [] as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach
            wire:model="{{ $wireModel }}"
            id="{{ $field['name'] }}"
            placeholder="{{ $field['default_value'] ?? '' }}"
            {{ $isRequired() ? 'required' : '' }}
        >
        @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
        @break

    @case('password')
        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
        <input type="password"
            @foreach($field['attr'] ?? [] as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach
            wire:model="{{ $wireModel }}"
            id="{{ $field['name'] }}"
            {{ $isRequired() ? 'required' : '' }}
        >
        @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
        @break

    @default
        <label for="{{ $field['name'] }}" class="form-label">{{ $field['label'] }}</label>
        <input type="text"
            @foreach($field['attr'] ?? [] as $attr => $value) {{ $attr }}="{{ $value }}" @endforeach
            wire:model="{{ $wireModel }}"
            id="{{ $field['name'] }}"
            placeholder="{{ $field['default_value'] ?? '' }}"
        >
        @error($wireModel) <div class="invalid-feedback">{{ $message }}</div> @enderror
@endswitch
