<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserSession;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserSessionController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('search', ''));
        $userId = $request->query('user');

        $sessionsQuery = UserSession::query()
            ->with('user')
            ->orderByDesc('last_activity_at')
            ->orderByDesc('created_at');

        if ($userId !== null && $userId !== '') {
            $sessionsQuery->where('user_id', (int) $userId);
        }

        if ($search !== '') {
            $sessionsQuery->whereHas('user', function (Builder $query) use ($search): void {
                $query->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $sessions = $sessionsQuery->paginate(25)->withQueryString();

        $totalSessions = UserSession::query()->count();
        $activeUsers = UserSession::query()->distinct('user_id')->count('user_id');

        $topUsers = User::query()
            ->withCount('sessions')
            ->whereHas('sessions')
            ->orderByDesc('sessions_count')
            ->limit(5)
            ->get();

        return view('admin.sessions.index', [
            'sessions' => $sessions,
            'filters' => [
                'search' => $search,
                'user' => $userId,
            ],
            'stats' => [
                'totalSessions' => $totalSessions,
                'activeUsers' => $activeUsers,
                'topUsers' => $topUsers,
            ],
        ]);
    }
}
