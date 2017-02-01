
    @foreach (['danger', 'warning', 'success', 'info'] as $msg)
    @if(Session::has('alert-' . $msg))
    <div class="alert alert-{{ $msg }}"><ul><li>{{ Session::get('alert-' . $msg) }}</li></ul></div>
    @endif
    @endforeach
