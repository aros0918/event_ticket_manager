@extends('auth.authapp')

@section('title')
    @lang('em.reset_password')
@endsection

@section('authcontent')
    <div class="card border-0 shadow">
        <div class="card-body p-8">
            <h3 class="mb-4">@lang('em.reset_password')</h3>

            <form method="POST" action="{{ route('password_reset') }}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">

                <input type="hidden" name="token" value="{{ $token }}">

                <div class="mb-3">
                    <input id="email" type="email"
                        class="wpcf7-form-control form-control{{ $errors->has('email') ? ' is-invalid' : '' }}"
                        name="email" value="{{ $email ?? old('email') }}" required autofocus
                        placeholder="@lang('em.email')">
                    @if ($errors->has('email'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('email') }}</strong>
                        </span>
                    @endif
                </div>
                <div class="mb-3">
                    <input id="password" type="password"
                        class="wpcf7-form-control form-control{{ $errors->has('password') ? ' is-invalid' : '' }}"
                        name="password" required placeholder="@lang('em.new') @lang('em.password')">
                    @if ($errors->has('password'))
                        <span class="invalid-feedback" role="alert">
                            <strong>{{ $errors->first('password') }}</strong>
                        </span>
                    @endif
                </div>

                <div class="mb-3">
                    <input id="password-confirm" type="password" class="wpcf7-form-control form-control"
                        name="password_confirmation" required placeholder="@lang('em.confirm_password')">
                </div>

                <button type="submit" class="btn btn-primary">@lang('em.reset_password')</button>

            </form>
        </div>
    </div>
@endsection
