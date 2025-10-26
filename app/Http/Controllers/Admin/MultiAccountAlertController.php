<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\DismissMultiAccountAlertRequest;
use App\Http\Requests\Admin\FilterMultiAccountAlertsRequest;
use App\Http\Requests\Admin\IpLookupRequest;
use App\Http\Requests\Admin\ResolveMultiAccountAlertRequest;
use App\Http\Resources\Admin\LoginActivityResource;
use App\Models\LoginActivity;
use App\Models\MultiAccountAlert;
use App\Models\User;
use App\Services\Security\IpLookupService;
use App\Services\Security\MultiAccountAlertsService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Throwable;

class MultiAccountAlertController extends Controller
{
    public function index(FilterMultiAccountAlertsRequest $request): View
    {
        $request->validated();

        return view('admin.multi-account-alerts.index');
    }

    public function show(MultiAccountAlert $multiAccountAlert): View
    {
        $multiAccountAlert->loadMissing(['resolvedBy', 'dismissedBy']);

        $userIds = $multiAccountAlert->user_ids ?? [];
        $users = collect($userIds)->isEmpty()
            ? collect()
            : User::query()->whereIn('id', $userIds)->get()->keyBy('id');

        $timelineStart = $multiAccountAlert->window_started_at ?? $multiAccountAlert->first_seen_at;
        $timelineEnd = $multiAccountAlert->last_seen_at ?? $multiAccountAlert->first_seen_at;

        $activities = LoginActivity::query()
            ->with(['user', 'actingSitter'])
            ->when($multiAccountAlert->source_type === 'ip', fn ($query) => $query->where('ip_address', $multiAccountAlert->ip_address))
            ->when($multiAccountAlert->source_type === 'device', fn ($query) => $query->where('device_hash', $multiAccountAlert->device_hash))
            ->when($multiAccountAlert->world_id !== null && $multiAccountAlert->world_id !== '', fn ($query) => $query->where('world_id', $multiAccountAlert->world_id))
            ->when($timelineStart !== null, fn ($query) => $query->where('logged_at', '>=', $timelineStart))
            ->when($timelineEnd !== null, fn ($query) => $query->where('logged_at', '<=', $timelineEnd))
            ->orderByDesc('logged_at')
            ->limit(50)
            ->get();

        return view('admin.multi-account-alerts.show', [
            'alert' => $multiAccountAlert,
            'users' => $users,
            'activities' => $activities,
            'vpnSuspected' => (bool) Arr::get($multiAccountAlert->metadata, 'vpn_suspected', false),
        ]);
    }

    public function resolve(ResolveMultiAccountAlertRequest $request, MultiAccountAlert $multiAccountAlert, MultiAccountAlertsService $alertsService): RedirectResponse
    {
        $alertsService->resolve($multiAccountAlert, $request->user(), $request->validated('notes'));

        return redirect()
            ->route('admin.multi-account-alerts.show', $multiAccountAlert)
            ->with('status', 'Alert resolved successfully.');
    }

    public function dismiss(DismissMultiAccountAlertRequest $request, MultiAccountAlert $multiAccountAlert, MultiAccountAlertsService $alertsService): RedirectResponse
    {
        $alertsService->dismiss($multiAccountAlert, $request->user(), $request->validated('notes'));

        return redirect()
            ->route('admin.multi-account-alerts.show', $multiAccountAlert)
            ->with('status', 'Alert dismissed successfully.');
    }

    public function activities(FilterMultiAccountAlertsRequest $request, MultiAccountAlert $multiAccountAlert): JsonResponse
    {
        $this->authorize('view', $multiAccountAlert);

        $limit = (int) $request->integer('limit', 50);

        $timelineStart = $multiAccountAlert->window_started_at ?? $multiAccountAlert->first_seen_at;
        $timelineEnd = $multiAccountAlert->last_seen_at ?? $multiAccountAlert->first_seen_at;

        $activities = LoginActivity::query()
            ->with(['user', 'actingSitter'])
            ->when($multiAccountAlert->source_type === 'ip', fn ($query) => $query->where('ip_address', $multiAccountAlert->ip_address))
            ->when($multiAccountAlert->source_type === 'device', fn ($query) => $query->where('device_hash', $multiAccountAlert->device_hash))
            ->when($multiAccountAlert->world_id !== null && $multiAccountAlert->world_id !== '', fn ($query) => $query->where('world_id', $multiAccountAlert->world_id))
            ->when($timelineStart !== null, fn ($query) => $query->where('logged_at', '>=', $timelineStart))
            ->when($timelineEnd !== null, fn ($query) => $query->where('logged_at', '<=', $timelineEnd))
            ->orderByDesc('logged_at')
            ->limit(max(1, min($limit, 200)))
            ->get();

        return response()->json([
            'data' => LoginActivityResource::collection($activities),
        ]);
    }

    public function lookup(IpLookupRequest $request, IpLookupService $ipLookupService): JsonResponse
    {
        $user = $request->user();
        $rateKey = sprintf('multiaccount:ip_lookup:%s', $user?->getAuthIdentifier() ?? Str::lower($request->ip()));
        $attempts = (int) config('multiaccount.ip_lookup.rate_limit.attempts', 30);
        $decay = max(60, (int) config('multiaccount.ip_lookup.rate_limit.per_minutes', 10) * 60);

        if (RateLimiter::tooManyAttempts($rateKey, $attempts)) {
            $seconds = RateLimiter::availableIn($rateKey);

            return response()->json([
                'message' => 'Too many lookup attempts. Please try again later.',
                'retry_after' => $seconds,
            ], 429);
        }

        RateLimiter::hit($rateKey, $decay);

        try {
            $result = $ipLookupService->lookup($request->validated('ip'));
        } catch (InvalidArgumentException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        } catch (Throwable $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 502);
        }

        return response()->json([
            'data' => $result,
        ]);
    }
}
