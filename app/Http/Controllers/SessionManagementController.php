<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Security\UserSessionManager;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SessionManagementController extends Controller
{
    public function destroyAll(Request $request, UserSessionManager $sessionManager): RedirectResponse
    {
        $user = $request->user();

        abort_unless($user !== null, 403);

        $sessionIds = $sessionManager->invalidateAllFor($user);

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        $message = $sessionIds->count() > 1
            ? __('All active sessions were terminated. Please sign in again to continue.')
            : __('Your active session was terminated. Please sign in again to continue.');

        return redirect()->route('login')->with('status', $message);
    }
}
