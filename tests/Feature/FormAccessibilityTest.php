<?php

declare(strict_types=1);

use App\Filament\Resources\CampaignCustomerSegmentResource;
use App\Models\CampaignCustomerSegment;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

beforeEach(function () {
    config()->set('cache.default', 'array');
    config()->set('session.driver', 'array');
    Cache::setDefaultDriver('array');
    Cache::store('array')->forever('travian.world_config', []);
});

it('renders associated label for login input', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
    $response->assertSee('label for="login"', false);
    $response->assertSeeText(__('auth.login.fields.login.label'));
});

it('renders login error messages with alert semantics', function () {
    $errors = new ViewErrorBag;
    $errors->put('default', new MessageBag(['login' => ['Invalid credentials.']]));

    $response = $this
        ->withViewErrors($errors)
        ->get(route('login'));

    $response->assertOk();
    $response->assertSee('id="login-error"', false);
    $response->assertSee('role="alert"', false);
    $response->assertSeeText('Invalid credentials.');
});

it('includes descriptive hint bindings in the segment form', function () {
    $view = view('admin.campaign-customer-segments._form', [
        'schema' => CampaignCustomerSegmentResource::formSchema(),
        'segment' => new CampaignCustomerSegment,
        'filtersJson' => '',
    ]);

    $errors = new ViewErrorBag;
    $errors->put('default', new MessageBag(['name' => ['Required field.']]));
    $view->with('errors', $errors);

    $html = $view->render();

    expect($html)->toContain('aria-describedby="segment-name-hint segment-name-error"');
    expect($html)->toContain('id="segment-name-error"');
    expect($html)->toContain('role="alert"');
    expect($html)->toContain(__('admin.campaign_customer_segments.fields.name.label'));
});
