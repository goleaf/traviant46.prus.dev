<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Auth\ImpersonationManager;
use Illuminate\Http\RedirectResponse;

class ImpersonationController extends Controller
{
    public function destroy(ImpersonationManager $manager): RedirectResponse
    {
        $impersonator = $manager->stop('manual');

        if ($impersonator instanceof User && $impersonator->isAdmin()) {
            return redirect()
                ->route('admin.dashboard')
                ->with('status', __('Impersonation session ended. You are back in administrator mode.'));
        }

        return redirect()
            ->route('home')
            ->with('status', __('Session restored.'));
    }
}
