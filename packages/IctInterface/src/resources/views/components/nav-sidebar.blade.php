<div di="sidebar" class="sidebar-fixed col-sm-2">
  <div class="slimScrollDiv" style="position: relative; overflow: hidden; height: 100%;">
    <div id="sidebar-content" style="overflow: hidden; height: 100%;">
      <div class="accordion nav-sidebar p-0" id="accordionMenu">
        <ul class="navbar-nav mr-auto mt-2 mt-lg-0">
          
                  <li class="nav-item bg-dark">
                    <a class="nav-link text-white" id="dashboard" href="/dashboard" title="Dashboard">
                      <i class="fas fa-desktop"></i> Dashboard
                    </a>
                  </li>
              </ul>
          @foreach ($navSidebar as $menuGroup)
            <div class="card">
              <div class="card-header" id="heading{{preg_replace_array('/\s/', ['_','_'], $menuGroup['label'])}}">
                  <button title="{{$menuGroup['tooltip']}}" class="btn btn-block text-left" type="button" data-toggle="collapse" data-target="#collapse{{preg_replace_array('/\s/', ['_','_'], $menuGroup['label'])}}" aria-expanded="false" aria-controls="collapse{{preg_replace_array('/\s/', ['_','_'], $menuGroup['label'])}}">
                    @if ($menuGroup['icon'])
                      <i class="{{$menuGroup['icon']}}"></i> 
                    @endif
                    <span class="mb-0 text-left-p10">{{$menuGroup['label']}}</span><i class="fas fa-angle-down  rotate-icon icon-menu icon-menu-right"></i>
                  </button>
              </div>
              <div id="collapse{{preg_replace_array('/\s/', ['_','_'], $menuGroup['label'])}}" class="@if($openMenu == $menuGroup['id']) show @else collapse @endif" aria-labelledby="heading{{preg_replace_array('/\s/', ['_','_'], $menuGroup['label'])}}">
                <div class="card-body">
                  <ul class="navbar-nav mr-auto mt-2 mt-lg-0">
              @foreach ($menuGroup['submenu'] as $menuItem)
                      <li class="nav-item  @if($activeReport == $menuItem->report_id) active-menu @endif">
                        <a class="nav-link" id="report_{{$menuItem->report_id}}" href="{{$menuItem->href_url}}" title="{{$menuItem->report_title}}">
                          @if($activeReport == $menuItem->report_id || $activeReport==null)
                            <i class="fas fa-desktop"></i>
                          @else
                            <i class="fas fa-caret-right"></i>
                          @endif 
                            {{Str::before($menuItem->report_title, '|')}}</a>
                      </li>
              @endforeach
                  </ul>
                </div>
              </div>
            </div>
          @endforeach
        </div>
      </div>
    </div>
  </div>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
        var accordion = document.getElementById('accordionMenu');
        if (!accordion) return;

        accordion.querySelectorAll('[data-toggle="collapse"], [data-bs-toggle="collapse"]').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();

                var targetSelector = btn.getAttribute('data-bs-target') || btn.getAttribute('data-target');
                var target = document.querySelector(targetSelector);
                if (!target) return;

                var isOpen = target.classList.contains('show');

                // Chiudi tutte le sezioni aperte
                accordion.querySelectorAll('.collapse.show').forEach(function(el) {
                    var bsCollapse = bootstrap.Collapse.getOrCreateInstance(el, {toggle: false});
                    bsCollapse.hide();
                });

                // Se era chiusa, aprila
                if (!isOpen) {
                    var bsCollapse = bootstrap.Collapse.getOrCreateInstance(target, {toggle: false});
                    bsCollapse.show();
                }
            });
        });
    });
  </script>