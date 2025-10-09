@extends('auth.layout')

@section('title', __('Choose a new password'))

@section('subtitle', __('Securely set a new password to regain access.'))

@section('content')
    <form method="POST" action="{{ route('password.update') }}" class="space-y-6">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <flux:field>
            <flux:label for="email">{{ __('Email address') }}</flux:label>
            <flux:input id="email" type="email" name="email" value="{{ old('email', $request->email) }}" required autofocus autocomplete="email" />
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
            {{ __('Reset password') }}
        </flux:button>
    </form>
@endsection
