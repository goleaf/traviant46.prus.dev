@extends('auth.layout')

@section('title', __('Sign in'))

@section('subtitle', __('Access your Travian administration account securely.'))

@section('content')
    <form method="POST" action="{{ route('login') }}" class="legacy-form" autocomplete="on">
        @csrf

        <table class="transparent loginTable">
            <tbody>
                <tr class="account">
                    <th class="accountNameOrEmailAddress">{{ __('Account name or Email address') }}</th>
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
                        >
                        @error('login')
                            <div class="legacy-error">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr class="pass">
                    <th>{{ __('Password') }}</th>
                    <td>
                        <input
                            id="password"
                            type="password"
                            name="password"
                            class="legacy-input"
                            required
                            autocomplete="current-password"
                        >
                        @error('password')
                            <div class="legacy-error">{{ $message }}</div>
                        @enderror
                    </td>
                </tr>
                <tr class="remember">
                    <th>{{ __('Remember me') }}</th>
                    <td>
                        <label class="legacy-checkbox" for="remember">
                            <input
                                id="remember"
                                type="checkbox"
                                name="remember"
                                value="1"
                                {{ old('remember') ? 'checked' : '' }}
                            >
                            <span>{{ __('Keep me signed in on this device') }}</span>
                        </label>
                    </td>
                </tr>
                <tr class="submit">
                    <th></th>
                    <td>
                        <button type="submit" class="legacy-button">
                            {{ __('Log in to your empire') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
@endsection

@section('footer')
    <div class="legacy-links">
        <a href="{{ route('password.request') }}">{{ __('Forgot your password?') }}</a>
        <a href="{{ route('register') }}">{{ __('Create a new account') }}</a>
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
