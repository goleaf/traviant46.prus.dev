@extends('auth.layout')

@section('title', __('Sign in'))

@section('subtitle', __('Access your Travian administration account securely.'))

@section('content')
    <form method="POST" action="{{ route('login') }}" class="space-y-6">
        @csrf

        <flux:field>
            <flux:label for="login">{{ __('Username or Email') }}</flux:label>
            <flux:input id="login" type="text" name="login" value="{{ old('login') }}" required autofocus autocomplete="username" />
            <flux:error name="login" />
        </flux:field>

        <flux:field>
            <flux:label for="password">{{ __('Password') }}</flux:label>
            <flux:input id="password" type="password" name="password" required autocomplete="current-password" />
            <flux:error name="password" />
        </flux:field>

        <flux:with-inline-field label="{{ __('Remember me') }}">
            <flux:checkbox id="remember" name="remember" :checked="old('remember')" />
        </flux:with-inline-field>

        <flux:button type="submit" variant="primary" color="sky" class="w-full">
            {{ __('Log in') }}
        </flux:button>
    </form>

    <div class="flex flex-col items-center gap-2 text-sm">
        <flux:link href="{{ route('password.request') }}" class="font-medium">
            {{ __('Forgot your password?') }}
        </flux:link>
        <flux:link href="{{ route('register') }}" class="font-medium">
            {{ __('Create a new account') }}
        </flux:link>
    </div>
@endsection
