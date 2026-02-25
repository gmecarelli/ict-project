<nav aria-label="Page navigation">
    
    {{$pages->appends(request()->all())->links();}}
  </nav>