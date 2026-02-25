@extends('ict::layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-sm-4">
            <div class="card m-5">
                <div class="card-header bg-blue">{{ __('Inserisci i tuoi dati di accesso') }}</div>

                <div class="card-body">
                    <form method="POST" action="{{ route('auth.check') }}">
                        @csrf
                        <div class="results">
                            @if(session()->has('errors'))
                                <div class="alert alert-danger">{{ session('errors') }}</div>
                            @endif
                        </div>
                        
                        <div class="col-sm-10 form-group row ml-auto mr-auto">
                            <label for="email" class="col-form-label">{{ __('User o email') }}</label>

                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <div class="input-group-text"><i class="fas fa-user"></i></div>
                                  </div>
                          
                                <input id="email" type="text" class="form-control" name="email" value="{{ old('email') }}" required autocomplete="email" autofocus>

                            </div>
                        </div>

                        <div class="col-sm-10 form-group row ml-auto mr-auto">
                            <label for="password" class="col-form-label">{{ __('Password') }}</label>

                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <div class="input-group-text"><i class="fas fa-key"></i></div>
                                  </div>
                                <input id="password" type="password" class="form-control" name="password" required autocomplete="current-password">

                            </div>
                        </div>

                        <div class="form-group row mb-0">
                            <div class="mt-3">
                                <button type="submit" class="btn btn-primary">
                                    {{ __('Login') }}
                                </button>

                                @if (Route::has('password.request'))
                                    <a class="btn btn-link" href="{{ route('password.request') }}">
                                        {{ __('Forgot Your Password?') }}
                                    </a>
                                @endif
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
