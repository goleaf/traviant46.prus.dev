@extends('auth.layout')

@section('title', __('Create account'))

@section('subtitle', __('Launch a new Travian account with secure authentication defaults.'))

@section('content')
    <form method="POST" action="{{ route('register') }}" class="space-y-6">
        @csrf

        <flux:field>
            <flux:label for="username">{{ __('Username') }}</flux:label>
            <flux:input id="username" type="text" name="username" value="{{ old('username') }}" required autofocus autocomplete="username" />
            <flux:error name="username" />
        </flux:field>

        <flux:field>
            <flux:label for="name">{{ __('Display name') }}</flux:label>
            <flux:input id="name" type="text" name="name" value="{{ old('name') }}" required />
            <flux:error name="name" />
        </flux:field>

        <flux:field>
            <flux:label for="email">{{ __('Email address') }}</flux:label>
            <flux:input id="email" type="email" name="email" value="{{ old('email') }}" required autocomplete="email" />
            <flux:error name="email" />
        </flux:field>

        <flux:field>
            <flux:label for="password">{{ __('Password') }}</flux:label>
            <flux:input id="password" type="password" name="password" required autocomplete="new-password" />
            <flux:error name="password" />
        </flux:field>

        <flux:field>
            <flux:label for="password_confirmation">{{ __('Confirm password') }}</flux:label>
            <flux:input id="password_confirmation" type="password" name="password_confirmation" required autocomplete="new-password" />
        </flux:field>

        <flux:button type="submit" variant="primary" color="sky" class="w-full">
            {{ __('Register') }}
        </flux:button>
    </form>

    <div class="flex flex-col items-center gap-2 text-sm">
        <flux:link href="{{ route('login') }}" class="font-medium">
            {{ __('Already have an account? Sign in') }}
        </flux:link>
    </div>
@endsection
