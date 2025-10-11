@extends('auth.layout')

@section('title', __('Reset password'))

@section('subtitle', __('We will email you a secure link to reset your password.'))

@section('content')
    <form method="POST" action="{{ route('password.email') }}" class="space-y-6">
        @csrf

        <flux:field>
            <flux:label for="email">{{ __('Email address') }}</flux:label>
            <flux:input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email" />
            <flux:error name="email" />
        </flux:field>

        <flux:button type="submit" variant="primary" color="sky" class="w-full">
            {{ __('Send password reset link') }}
        </flux:button>
    </form>

    <div class="flex justify-center text-sm">
        <flux:link href="{{ route('login') }}" class="font-medium">
            {{ __('Back to login') }}
        </flux:link>
    </div>
@endsection
