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

    <script type="text/javascript" src="https://cdn.jsdelivr.net/momentjs/latest/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />
    {{-- <script type="text/javascript" src="{{asset('ict-assets/js/jquery.slimscroll.min.js')}}"></script>
    <script type="text/javascript" src="{{asset('ict-assets/js/jquery.slimscroll.horizontal.min.js')}}"></script> --}}


    <!-- Fonts -->
    <link rel="dns-prefetch" href="//fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css?family=Nunito" rel="stylesheet">

    <!-- Styles -->
    <link href="{{ asset('ict-assets/css/app.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="{{ asset('ict-assets/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('ict-assets/css/style.css') }}">
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
                // $(document).on('blur', '.numeric', function(e) {
                //     var field = e.currentTarget;
                //     console.log($("#"+field.id).val());
                //     if($("#"+field.id).val() == 'undefined') {
                //         var def_value = $("#"+field.id).val().replace(",", ".");
                //         $("#"+field.id).val(def_value);
                //         console.log($("#"+field.id).val());
                //     }

                // });

                var url = '{{route("ref_numeric")}}';
                $("select#reference").on('change', function() {
                    @if(!request()->has('report'))
                        var requestData = {'reference': $("#reference").val()};
                    @else
                        var requestData = {'reference': $("#reference").val(), 'report': {{request("report")}}};
                    @endif
                    $.ajax(url, {
                            method: 'GET',
                            data: requestData, 
                            complete: function(response){
                                    console.log(response.responseJSON.result);
                                    if(response.responseJSON.result == 'success') {
                                        $("#code").val(response.responseJSON.code)
                                    } else {
                                        alert(response.responseJSON.error);
                                    }
                            }
                    });
                });

               @if(isset($roles_checker) && $roles_checker['has_edit_button'] == 0)
               
               $('button.btn, a.btn').attr('disabled', true);
               @endif 


                $('.datapicker').daterangepicker({
                    ranges: {
                        'Today': [moment(), moment()],
                        'Yesterday': [moment().subtract(1, 'days'), moment().subtract(1, 'days')],
                        'Last 7 Days': [moment().subtract(6, 'days'), moment()],
                        'Last 30 Days': [moment().subtract(29, 'days'), moment()],
                        'This Month': [moment().startOf('month'), moment().endOf('month')],
                        'Last Month': [moment().subtract(1, 'month').startOf('month'), moment().subtract(1, 'month').endOf('month')],
                        'This Year': [moment().startOf('year'), moment().endOf('year')],
                    },
                    autoUpdateInput: false,
                    locale: {
                        cancelLabel: 'Clear'
                    }
                });

                $('.datapicker').on('apply.daterangepicker', function(ev, picker) {
                    $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
                });

                $('.datapicker').on('cancel.daterangepicker', function(ev, picker) {
                    let id = ev.currentTarget.id;
                    $(this).val('');
                });

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
                $("document").find("div.tox-promotion").remove();

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