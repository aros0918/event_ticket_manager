@extends('auth.authapp')

@section('title')
    @lang('em.login')
@endsection

@section('authcontent')
    <!-- card -->
    <div class="card shadow border-0">
        <!-- card body -->
        @if (config('voyager.demo_mode'))
            <div class="alert alert-info">
                <a href="https://localhost:3000" target="_blank">
                    @lang('em.visit_accounts')
                </a>
            </div>
        @endif

        <div class="card-body p-5">

            @if ($errors->any())
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>
                                <span role="alert">
                                    <strong>{{ $error }}</strong>
                                </span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
            <h3 class="mb-4">@lang('em.login')</h3>
            <!-- form -->
            <form method="POST" action="{{ route('login') }}">
                <!-- email -->
                <div class="mb-3">
                    <label for="email" class="form-label">@lang('em.email_address')</label>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input id="email" type="email"
                        class="wpcf7-form-control form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                        name="email" value="{{ old('email') }}" required autofocus placeholder="@lang('em.email')">
                    @if ($errors->has('email'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('email') }}</strong>
                        </span>
                    @endif

                </div>
                <!-- password -->
                <div class="mb-3">
                    <label for="password" class="form-label">@lang('em.password')</label>
                    <input id="password" type="password"
                        class="wpcf7-form-control form-control{{ $errors->has('password') ? ' is-invalid' : '' }}"
                        name="password" required placeholder="@lang('em.password')">
                    @if ($errors->has('password'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="d-flex justify-content-between mb-2 pb-2 mt-3 text-sm ">
                    <!-- form check -->
                    <div class="form-check ">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" checked
                            value="1">
                        <label class="form-check-label" for="remember">@lang('em.remember')</label>
                    </div>
                    <!-- forgot password -->
                    <div class="fw-bold">
                        <a href="{{ route('password_request') }}" class="text-inherit"> @lang('em.forgot_password')</a>
                    </div>

                </div>
                <!-- button -->
                <button type="submit" class="btn btn-primary btn-block">@lang('em.login') <i
                        class="fas fa-sign-in-alt"></i></button>
            </form>
            <div class="mt-4">
                <p class="mb-0">@lang('em.donot_account')<a href="{{ route('show_register') }}">
                        @lang('em.register')</a></p>
            </div>
            

        </div>
    </div>


@endsection
