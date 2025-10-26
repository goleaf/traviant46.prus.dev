@extends('auth.layout')

@section('title', __('Sign in'))

@section('subtitle', __('Access your Travian administration account securely.'))

@section('content')
    <form method="POST" action="{{ route('login') }}" class="legacy-form" autocomplete="on" novalidate>
        @csrf

        <table class="transparent loginTable">
            <tbody>
                <tr class="account">
                    @php($loginErrorId = 'login-error')
                    <th scope="row" class="accountNameOrEmailAddress">
                        <label for="login">{{ __('auth.login.fields.login.label') }}</label>
                    </th>
                    <td>
                        <input
                            id="login"
                            type="text"
                            name="login"
                            value="{{ old('login') }}"
                            class="legacy-input"
                            required
                            autofocus
                            autocomplete="username"
                            @if ($errors->has('login'))
                                aria-invalid="true"
                                aria-describedby="{{ $loginErrorId }}"
                            @endif
                        >
                        @error('login')
                            <div id="{{ $loginErrorId }}" class="legacy-error" role="alert">
                                {{ $message }}
                            </div>
                        @enderror
                    </td>
                </tr>
                <tr class="pass">
                    @php($passwordErrorId = 'password-error')
                    <th scope="row">
                        <label for="password">{{ __('auth.shared.fields.password.label') }}</label>
                    </th>
                    <td>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="legacy-input"
                            required
                            autocomplete="current-password"
                            @if ($errors->has('password'))
                                aria-invalid="true"
                                aria-describedby="{{ $passwordErrorId }}"
                            @endif
                        >
                        @error('password')
                            <div id="{{ $passwordErrorId }}" class="legacy-error" role="alert">
                                {{ $message }}
                            </div>
                        @enderror
                    </td>
                </tr>
                <tr class="remember">
                    <th scope="row">
                        <label for="remember">{{ __('auth.login.fields.remember.label') }}</label>
                    </th>
                    <td>
                        <label class="legacy-checkbox" for="remember">
                            <input
                                id="remember"
                                type="checkbox"
                                name="remember"
                                value="1"
                                {{ old('remember') ? 'checked' : '' }}
                            >
                            <span>{{ __('auth.login.fields.remember.help') }}</span>
                        </label>
                    </td>
                </tr>
                <tr class="submit">
                    <th></th>
                    <td>
                        <button type="submit" class="legacy-button">
                            {{ __('auth.login.actions.submit') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
@endsection

@section('footer')
    <div class="legacy-links">
        <a href="{{ route('password.request') }}">{{ __('auth.login.links.forgot_password') }}</a>
        <a href="{{ route('register') }}">{{ __('auth.login.links.register') }}</a>
    </div>
@endsection

@section('aside')
    <h3>{{ __('Command your villages') }}</h3>
    <p>
        {{ __('Resume stewardship over your Travian empire with the classic control room layout and refreshed Laravel-driven services.') }}
    </p>
    <ul>
        <li>{{ __('Coordinate attacks and defenses with alliance intelligence in real time.') }}</li>
        <li>{{ __('Monitor production, hero growth, and trader routes from a single dashboard.') }}</li>
        <li>{{ __('Switch between sitter assignments without leaving the legacy interface.') }}</li>
    </ul>
@endsection
