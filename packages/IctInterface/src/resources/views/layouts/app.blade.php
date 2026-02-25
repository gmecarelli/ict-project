<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="shortcut icon" href="{{ asset('ict-assets/favicon-ICTlabs.png') }}" type="image/x-icon" />
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>@if(isset($report['title'])){{$report['title']}} - @endif{{ config('app.name', 'Laravel') }}</title>

    <!-- Scripts -->

    <script src="https://code.jquery.com/jquery-3.6.0.slim.min.js"></script>
    <script src="{{ asset('ict-assets/js/bootstrap.bundle.min.js') }}"></script>
    {{-- Compatibility shim: mappa attributi BS4 (data-toggle, data-dismiss, data-target) in BS5 (data-bs-*) --}}
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('[data-toggle]').forEach(function(el) {
            if (!el.getAttribute('data-bs-toggle')) el.setAttribute('data-bs-toggle', el.getAttribute('data-toggle'));
        });
        document.querySelectorAll('[data-dismiss]').forEach(function(el) {
            if (!el.getAttribute('data-bs-dismiss')) el.setAttribute('data-bs-dismiss', el.getAttribute('data-dismiss'));
        });
        document.querySelectorAll('[data-target]').forEach(function(el) {
            if (!el.getAttribute('data-bs-target')) el.setAttribute('data-bs-target', el.getAttribute('data-target'));
        });
    });
    </script>
    <script src="{{ asset('ict-assets/js/app.js') }}" defer></script>
    {{-- <script src="{{ asset('ict-assets/js/common.js') }}" defer></script> --}} {{-- Sostituito da Livewire ChildFormComponent (removeItem) --}}

    {{-- <script src="{{ asset('js/multiselect.js') }}" defer></script> --}}
    {{-- <script type="text/javascript" src="https://cdn.jsdelivr.net/jquery/latest/jquery.min.js"></script> --}}

    {{-- daterangepicker rimosso: sostituito dal componente Alpine.js dateRangeField (type=daterange) --}}
    {{-- <script type="text/javascript" src="{{asset('ict-assets/js/jquery.slimscroll.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('ict-assets/js/jquery.slimscroll.horizontal.min.js')}}"></script> --}}


    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('ict-assets/css/app.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('ict-assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('ict-assets/css/style.css') }}">
    <link rel="stylesheet" href="{{ asset('ict-assets/css/daterange.css') }}">
    <link rel="stylesheet" href="{{ asset('ict-assets/css/fontawesome/css/all.min.css') }}">
    
    <!-- Imposto la variabile di colore da usare nello style.css -->
    <style>
        :root {
            --custom-bg: {{ config('ict.css_color', '#4d7496') }};
        }
    </style>
    
</head>

<body>
    <div class="container-fluid m-0 p-0 fill">
        <nav class="navbar navbar-expand-md navbar-light bg-blue shadow-sm">
            <div class="container-fluid" id="app">
                <a class="navbar-brand" href="{{ url('/') }}">
                    {{ config('app.name', 'Laravel') }}
                </a>


                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav mr-auto">

                    </ul>

                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ml-auto">
                        <!-- Authentication Links -->

                        @if (Session::has('loggedUser'))
                        <li class="nav-item">

                            <a href="{{ route('auth.logout') }}" id="navbarDropdown" class="nav-link" style="float:left"
                                alt="Logout" title="Logout">
                                {{ Session::get('loggedUser')->name }} <i
                                    class="fas fa-sign-out-alt icon-menu icon-menu-right"></i>
                            </a>

                        </li>
                        @endif
                    </ul>
                </div>
            </div>
        </nav>

        <div class="row fill">
            @if(Session::has('loggedUser'))
            <x-ict-nav-sidebar />
            @endif
            @yield('content')

        </div>
        <div id="footer">
            {{-- @if (!empty($report['multicheck_reference']))
                @include('ict::multiselect.multiselect-js')
            @endif --}} {{-- Sostituito da Alpine.js + Livewire ict-multicheck-manager in report.blade.php --}}

            <script>
               @if(isset($roles_checker) && $roles_checker['has_edit_button'] == 0)
               document.querySelectorAll('button.btn, a.btn').forEach(function(el) { el.setAttribute('disabled', 'true'); el.style.pointerEvents = 'none'; el.style.opacity = '0.5'; });
               @endif

                // JS per rendere disabled una select già readonly (impedire l'apertura del menù)
                @if(Session::has('disabledJsSelect'))
                    @foreach(session('disabledJsSelect') as $element) 
                        document.getElementById('{{$element}}').addEventListener('mousedown', function(e) {e.preventDefault(); });// Impedisce l'apertura del menu a tendina
                    @endforeach
                @endif

                
            </script>

            @yield('footer')

            <script src="{{ asset('ict-assets/js/plugins/tinymce/tinymce.min.js') }}"></script>
            <script>
                tinymce.init({
                    selector: 'textarea.tinymce',
                    menubar: false,
                    skin: 'tinymce-5',
                    plugins: 'advlist anchor autolink charmap code codesample directionality help image insertdatetime link lists media nonbreaking pagebreak preview searchreplace table template visualblocks visualchars wordcount',
                    toolbar: 'undo redo | blocks | bold italic strikethrough forecolor backcolor blockquote | link image media | alignleft aligncenter alignright alignjustify | numlist bullist outdent indent | removeformat',
                    height: 250
                });
                document.querySelectorAll('div.tox-promotion').forEach(function(el) { el.remove(); });

                document.addEventListener('alpine:init', () => {

                    /**
                     * finderField — Componente Alpine.js per ricerca autocomplete su campi text con classe "finder".
                     * Sostituisce finder.blade.php (jQuery AJAX).
                     *
                     * @param {string} wireModel  - Nome del wire:model Livewire (es. 'formData.options')
                     * @param {string} searchUrl  - URL dell'endpoint di ricerca
                     * @param {string} mapStr     - Mapping campi nel formato "formField>valueKey|formField>valueKey"
                     */
                    Alpine.data('finderField', (wireModel, searchUrl, mapStr, fieldName) => ({
                        query: '',
                        results: [],
                        isOpen: false,
                        mappings: [],

                        init() {
                            this.query = this.$wire.get(wireModel) || '';
                            this.mappings = mapStr.split('|').map(m => {
                                const [formField, valueKey] = m.split('>');
                                return { formField: formField.trim(), valueKey: valueKey.trim() };
                            });
                        },

                        async search() {
                            if (this.query.length < 3) {
                                this.results = [];
                                this.isOpen = false;
                                return;
                            }
                            try {
                                const url = new URL(searchUrl, window.location.origin);
                                url.searchParams.set('query', this.query);
                                const response = await fetch(url, {
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
                                    }
                                });
                                if (!response.ok) {
                                    console.error('Finder HTTP error:', response.status, response.statusText);
                                    return;
                                }
                                const data = await response.json();
                                if (data.result === 'success') {
                                    this.results = data.items || [];
                                    this.isOpen = this.results.length > 0;
                                }
                            } catch (e) {
                                console.error('Finder search error:', e);
                            }
                        },

                        selectItem(item) {
                            const ownMapping = this.mappings.find(m => m.formField === fieldName);
                            const newQuery = ownMapping ? (item.values[ownMapping.valueKey] ?? item.display) : item.display;

                            // Aggiorna gli altri campi mappati via Livewire
                            this.mappings.forEach(({ formField, valueKey }) => {
                                if (formField !== fieldName && item.values[valueKey] !== undefined) {
                                    this.$wire.set('formData.' + formField, item.values[valueKey]);
                                }
                            });

                            // Aggiorna il campo finder (Alpine)
                            this.query = newQuery;
                            this.isOpen = false;
                            this.results = [];

                            // Sincronizza il campo finder a Livewire dopo che il morph si è stabilizzato
                            setTimeout(() => {
                                this.$wire.set('formData.' + fieldName, newQuery);
                                this.query = newQuery;
                            }, 300);
                        }
                    }));

                    /**
                     * dateRangeField — Componente Alpine.js per selezione range di date.
                     * Sostituisce il plugin jQuery daterangepicker.
                     * Output formato: dd/mm/yyyy - dd/mm/yyyy
                     *
                     * @param {string} wireModel - Nome del wire:model Livewire (es. 'formData.date_range')
                     */
                    Alpine.data('dateRangeField', (wireModel) => ({
                        isOpen: false,
                        startDate: null,
                        endDate: null,
                        selectingEnd: false,
                        currentMonth: new Date().getMonth(),
                        currentYear: new Date().getFullYear(),
                        displayValue: '',
                        hoverDate: null,

                        init() {
                            const val = this.$wire.get(wireModel) || '';
                            if (val) this._parseValue(val);
                        },

                        _parseValue(val) {
                            const parts = val.split(' - ');
                            if (parts.length === 2) {
                                this.startDate = this._toDate(parts[0].trim());
                                this.endDate = this._toDate(parts[1].trim());
                                if (this.startDate && this.endDate) {
                                    this.displayValue = val;
                                    this.currentMonth = this.startDate.getMonth();
                                    this.currentYear = this.startDate.getFullYear();
                                }
                            }
                        },

                        _toDate(str) {
                            const p = str.split('/');
                            if (p.length !== 3) return null;
                            const d = new Date(parseInt(p[2]), parseInt(p[1]) - 1, parseInt(p[0]));
                            return isNaN(d.getTime()) ? null : d;
                        },

                        _fmt(date) {
                            const dd = String(date.getDate()).padStart(2, '0');
                            const mm = String(date.getMonth() + 1).padStart(2, '0');
                            return dd + '/' + mm + '/' + date.getFullYear();
                        },

                        _sameDay(a, b) {
                            return a && b &&
                                a.getFullYear() === b.getFullYear() &&
                                a.getMonth() === b.getMonth() &&
                                a.getDate() === b.getDate();
                        },

                        toggleCalendar() { this.isOpen = !this.isOpen; },

                        get daysInMonth() {
                            return new Date(this.currentYear, this.currentMonth + 1, 0).getDate();
                        },

                        get firstDayOfWeek() {
                            const d = new Date(this.currentYear, this.currentMonth, 1).getDay();
                            return d === 0 ? 6 : d - 1; // Lunedì = 0
                        },

                        get calendarDays() {
                            const days = [];
                            const prev = new Date(this.currentYear, this.currentMonth, 0).getDate();
                            for (let i = this.firstDayOfWeek - 1; i >= 0; i--) {
                                days.push({ day: prev - i, cur: false, date: new Date(this.currentYear, this.currentMonth - 1, prev - i) });
                            }
                            for (let i = 1; i <= this.daysInMonth; i++) {
                                days.push({ day: i, cur: true, date: new Date(this.currentYear, this.currentMonth, i) });
                            }
                            const rest = 42 - days.length;
                            for (let i = 1; i <= rest; i++) {
                                days.push({ day: i, cur: false, date: new Date(this.currentYear, this.currentMonth + 1, i) });
                            }
                            return days;
                        },

                        get monthLabel() {
                            const name = new Date(this.currentYear, this.currentMonth)
                                .toLocaleString('it-IT', { month: 'long' });
                            return name.charAt(0).toUpperCase() + name.slice(1) + ' ' + this.currentYear;
                        },

                        prevMonth() {
                            if (this.currentMonth === 0) { this.currentMonth = 11; this.currentYear--; }
                            else { this.currentMonth--; }
                        },

                        nextMonth() {
                            if (this.currentMonth === 11) { this.currentMonth = 0; this.currentYear++; }
                            else { this.currentMonth++; }
                        },

                        selectDay(dayObj) {
                            if (!this.selectingEnd || !this.startDate) {
                                this.startDate = dayObj.date;
                                this.endDate = null;
                                this.selectingEnd = true;
                            } else {
                                if (dayObj.date < this.startDate) {
                                    this.endDate = this.startDate;
                                    this.startDate = dayObj.date;
                                } else {
                                    this.endDate = dayObj.date;
                                }
                                this.selectingEnd = false;
                                this._apply();
                            }
                        },

                        dayClass(dayObj) {
                            const d = dayObj.date;
                            const cls = [];
                            if (!dayObj.cur) cls.push('other-month');
                            if (this._sameDay(d, new Date())) cls.push('today');
                            if (this._sameDay(d, this.startDate)) cls.push('range-start');
                            if (this._sameDay(d, this.endDate)) cls.push('range-end');

                            // Highlight range (incluso hover durante selezione)
                            if (this.startDate) {
                                const end = this.endDate || (this.selectingEnd && this.hoverDate ? this.hoverDate : null);
                                if (end) {
                                    let s = this.startDate, e = end;
                                    if (s > e) { const t = s; s = e; e = t; }
                                    if (d >= s && d <= e) cls.push('in-range');
                                }
                            }
                            return cls.join(' ');
                        },

                        _apply() {
                            if (this.startDate && this.endDate) {
                                this.displayValue = this._fmt(this.startDate) + ' - ' + this._fmt(this.endDate);
                                this.$wire.set(wireModel, this.displayValue);
                                this.isOpen = false;
                            }
                        },

                        setPreset(key) {
                            const t = new Date(); t.setHours(0,0,0,0);
                            const presets = {
                                today:     [new Date(t), new Date(t)],
                                yesterday: [(() => { const d = new Date(t); d.setDate(d.getDate()-1); return d; })(), (() => { const d = new Date(t); d.setDate(d.getDate()-1); return d; })()],
                                last7:     [(() => { const d = new Date(t); d.setDate(d.getDate()-6); return d; })(), new Date(t)],
                                last30:    [(() => { const d = new Date(t); d.setDate(d.getDate()-29); return d; })(), new Date(t)],
                                thisMonth: [new Date(t.getFullYear(), t.getMonth(), 1), new Date(t.getFullYear(), t.getMonth()+1, 0)],
                                lastMonth: [new Date(t.getFullYear(), t.getMonth()-1, 1), new Date(t.getFullYear(), t.getMonth(), 0)],
                                thisYear:  [new Date(t.getFullYear(), 0, 1), new Date(t.getFullYear(), 11, 31)],
                            };
                            const p = presets[key];
                            if (p) {
                                this.startDate = p[0];
                                this.endDate = p[1];
                                this.selectingEnd = false;
                                this._apply();
                            }
                        },

                        clear() {
                            this.startDate = null;
                            this.endDate = null;
                            this.displayValue = '';
                            this.selectingEnd = false;
                            this.$wire.set(wireModel, '');
                            this.isOpen = false;
                        }
                    }));

                    Alpine.data('multiSelectField', (entangled, options) => ({
                        selected: entangled ?? [],
                        options: options,
                        search: '',
                        open: false,
                        get filteredOptions() {
                            if (!this.search) return this.options;
                            const s = this.search.toLowerCase();
                            const filtered = {};
                            for (const [code, label] of Object.entries(this.options)) {
                                if (label.toLowerCase().includes(s)) {
                                    filtered[code] = label;
                                }
                            }
                            return filtered;
                        },
                        get allSelected() {
                            return Object.keys(this.options).length > 0
                                && Object.keys(this.options).every(code => this.selected.includes(code));
                        },
                        toggle(code) {
                            const idx = this.selected.indexOf(code);
                            if (idx === -1) {
                                this.selected.push(code);
                            } else {
                                this.selected.splice(idx, 1);
                            }
                        },
                        selectAll() {
                            if (this.allSelected) {
                                this.selected = [];
                            } else {
                                this.selected = Object.keys(this.options);
                            }
                        },
                        getLabel(code) {
                            return this.options[code] ?? code;
                        }
                    }));
                });
            </script>

            {{-- @include('js.finder') --}}
        </div>
    </div>
</body>

</html>