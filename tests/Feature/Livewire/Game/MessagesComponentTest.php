<?php

declare(strict_types=1);

use App\Livewire\Game\Messages as GameMessages;
use App\Livewire\Messages\Compose as ComposeComponent;
use App\Livewire\Messages\Inbox as InboxComponent;
use App\Livewire\Messages\Sent as SentComponent;
use App\Models\Message;
use App\Models\MessageRecipient;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use Livewire\Livewire;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

beforeEach(function (): void {
    Config::set('game.communication.spam', [
        'rate_limit_threshold' => 500,
        'rate_limit_window_minutes' => 1,
        'duplicate_window_minutes' => 1,
        'recipient_unread_threshold' => 50,
        'global_unread_threshold' => 100,
    ]);

    Config::set('hashing.driver', 'bcrypt');
});

it('defaults to the inbox tab, switches tabs, and captures flash messages', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test(GameMessages::class)
        ->assertSet('currentTab', 'inbox')
        ->call('switchTab', 'compose')
        ->assertSet('currentTab', 'compose')
        ->call('switchTab', 'sent')
        ->assertSet('currentTab', 'sent')
        ->call('handleMessageSent', 'Delivered!')
        ->assertSet('flashMessage', 'Delivered!')
        ->call('dismissFlash')
        ->assertSet('flashMessage', null);
});

it('sends a message via the compose component', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create([
        'username' => 'targetCommander',
    ]);

    $this->actingAs($sender);

    Livewire::test(ComposeComponent::class)
        ->call('fillRecipient', $recipient->username)
        ->set('subject', 'Joint raid')
        ->set('body', str_repeat('Coordinate at dawn. ', 5))
        ->call('send')
        ->assertSet('statusMessage', __('Message delivered to :username.', ['username' => $recipient->username]))
        ->assertSet('errorMessage', null);

    $message = Message::query()->where('sender_id', $sender->getKey())->first();

    expect($message)->not->toBeNull();
    expect($message->subject)->toEqual('Joint raid');

    $recipientRecord = MessageRecipient::query()
        ->where('message_id', $message->getKey())
        ->where('recipient_id', $recipient->getKey())
        ->first();

    expect($recipientRecord)->not->toBeNull();
    expect($recipientRecord->status)->toEqual('unread');
    expect($recipientRecord->read_at)->toBeNull();
});

it('strips formatting from composed messages so only text persists', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create([
        'username' => 'formatTester',
    ]);

    $this->actingAs($sender);

    Livewire::test(ComposeComponent::class)
        ->set('recipient', $recipient->username)
        ->set('subject', '<b>Alliance</b> update')
        ->set('body', "<p>Attack <strong>now</strong>.</p><br><script>alert('x');</script>")
        ->call('send');

    $message = Message::query()->where('sender_id', $sender->getKey())->first();

    expect($message)->not->toBeNull();
    expect($message->subject)->toEqual('Alliance update');
    expect($message->body)->toEqual("Attack now.");
    expect($message->body)->not->toContain('script');
});

it('marks messages read and unread through the inbox component', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $message = Message::query()->create([
        'sender_id' => $sender->getKey(),
        'subject' => 'Scouting report',
        'body' => 'Watch the northern border.',
        'message_type' => 'player',
        'delivery_scope' => 'individual',
        'checksum' => Str::random(32),
        'sent_at' => now(),
    ]);

    $recipientRecord = MessageRecipient::query()->create([
        'message_id' => $message->getKey(),
        'recipient_id' => $recipient->getKey(),
        'status' => 'unread',
        'is_archived' => false,
    ]);

    $this->actingAs($recipient);

    $component = Livewire::test(InboxComponent::class);

    $component->call('toggleRead', $recipientRecord->getKey());
    $recipientRecord->refresh();

    expect($recipientRecord->status)->toEqual('read');
    expect($recipientRecord->read_at)->not->toBeNull();

    $component->call('toggleRead', $recipientRecord->getKey());
    $recipientRecord->refresh();

    expect($recipientRecord->status)->toEqual('unread');
    expect($recipientRecord->read_at)->toBeNull();
});

it('lists sent messages for the authenticated user', function (): void {
    $sender = User::factory()->create();
    $recipient = User::factory()->create();

    $message = Message::query()->create([
        'sender_id' => $sender->getKey(),
        'subject' => 'Alliance invitation',
        'body' => 'Join us for coordinated defense and trade bonuses.',
        'message_type' => 'player',
        'delivery_scope' => 'individual',
        'checksum' => Str::random(32),
        'sent_at' => now(),
    ]);

    MessageRecipient::query()->create([
        'message_id' => $message->getKey(),
        'recipient_id' => $recipient->getKey(),
        'status' => 'unread',
        'is_archived' => false,
    ]);

    $this->actingAs($sender);

    Livewire::test(SentComponent::class)
        ->assertSee($message->subject)
        ->assertSee($recipient->username);
});
