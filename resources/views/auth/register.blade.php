@extends('auth.layout')

@section('title', __('Create account'))

@section('subtitle', __('Launch a new Travian account with secure authentication defaults.'))

@section('content')
    <form method="POST" action="{{ route('register') }}" class="legacy-form" autocomplete="on" novalidate>
        @csrf

        <table class="transparent loginTable">
            <tbody>
                <tr class="account-name">
                    @php($usernameErrorId = 'username-error')
                    <th scope="row">
                        <label for="username">{{ __('auth.register.fields.username.label') }}</label>
                    </th>
                    <td>
                        <input
                            id="username"
                            type="text"
                            name="username"
                            value="{{ old('username') }}"
                            class="legacy-input"
                            required
                            autofocus
                            autocomplete="username"
                            @if ($errors->has('username'))
                                aria-invalid="true"
                                aria-describedby="{{ $usernameErrorId }}"
                            @endif
                        >
                        @error('username')
                            <div id="{{ $usernameErrorId }}" class="legacy-error" role="alert" aria-live="assertive">
                                {{ $message }}
                            </div>
                        @enderror
                    </td>
                </tr>
                <tr class="display-name">
                    @php($nameErrorId = 'name-error')
                    <th scope="row">
                        <label for="name">{{ __('auth.register.fields.name.label') }}</label>
                    </th>
                    <td>
                        <input
                            id="name"
                            type="text"
                            name="name"
                            value="{{ old('name') }}"
                            class="legacy-input"
                            required
                            @if ($errors->has('name'))
                                aria-invalid="true"
                                aria-describedby="{{ $nameErrorId }}"
                            @endif
                        >
                        @error('name')
                            <div id="{{ $nameErrorId }}" class="legacy-error" role="alert" aria-live="assertive">
                                {{ $message }}
                            </div>
                        @enderror
                    </td>
                </tr>
                <tr class="email">
                    @php($emailErrorId = 'email-error')
                    <th scope="row">
                        <label for="email">{{ __('auth.shared.fields.email.label') }}</label>
                    </th>
                    <td>
                        <input
                            id="email"
                            type="email"
                            name="email"
                            value="{{ old('email') }}"
                            class="legacy-input"
                            required
                            autocomplete="email"
                            @if ($errors->has('email'))
                                aria-invalid="true"
                                aria-describedby="{{ $emailErrorId }}"
                            @endif
                        >
                        @error('email')
                            <div id="{{ $emailErrorId }}" class="legacy-error" role="alert" aria-live="assertive">
                                {{ $message }}
                            </div>
                        @enderror
                    </td>
                </tr>
                <tr class="password">
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
                            autocomplete="new-password"
                            @if ($errors->has('password'))
                                aria-invalid="true"
                                aria-describedby="{{ $passwordErrorId }}"
                            @endif
                        >
                        @error('password')
                            <div id="{{ $passwordErrorId }}" class="legacy-error" role="alert" aria-live="assertive">
                                {{ $message }}
                            </div>
                        @enderror
                    </td>
                </tr>
                <tr class="password-confirmation">
                    <th scope="row">
                        <label for="password_confirmation">{{ __('auth.shared.fields.password_confirmation.label') }}</label>
                    </th>
                    <td>
                        <input
                            id="password_confirmation"
                            type="password"
                            name="password_confirmation"
                            class="legacy-input"
                            required
                            autocomplete="new-password"
                        >
                    </td>
                </tr>
                <tr class="submit">
                    <th></th>
                    <td>
                        <button type="submit" class="legacy-button">
                            {{ __('auth.register.actions.submit') }}
                        </button>
                    </td>
                </tr>
            </tbody>
        </table>
    </form>
@endsection

@section('footer')
    <div class="legacy-links">
        <a href="{{ route('login') }}">{{ __('auth.register.links.login') }}</a>
    </div>
@endsection

@section('aside')
    <h3>{{ __('Forge your legacy') }}</h3>
    <p>
        {{ __('Register to begin expanding villages, forging alliances, and mastering economy cycles in this faithful Travian remake.') }}
    </p>
    <ul>
        <li>{{ __('Choose a unique commander name to represent your tribe.') }}</li>
        <li>{{ __('Securely store contact details for sitter and recovery workflows.') }}</li>
        <li>{{ __('Start with Laravel-backed protection against imposters and bots.') }}</li>
    </ul>
@endsection
