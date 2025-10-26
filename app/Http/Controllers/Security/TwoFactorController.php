<?php

declare(strict_types=1);

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Laravel\Fortify\Fortify;

class TwoFactorController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        abort_unless($user instanceof User, 403);

        $twoFactorEnabled = $user->two_factor_secret !== null;
        $twoFactorConfirmed = $user->two_factor_confirmed_at !== null;

        $qrCodeSvg = null;
        $secretKey = null;
        $recoveryCodes = [];

        if ($twoFactorEnabled) {
            $qrCodeSvg = $user->twoFactorQrCodeSvg();
            $secretKey = Fortify::currentEncrypter()->decrypt($user->two_factor_secret);

            if ($user->two_factor_recovery_codes !== null) {
                $recoveryCodes = $user->recoveryCodes();
            }
        }

        return view('security.two-factor', [
            'user' => $user,
            'twoFactorEnabled' => $twoFactorEnabled,
            'twoFactorConfirmed' => $twoFactorConfirmed,
            'qrCodeSvg' => $qrCodeSvg,
            'secretKey' => $secretKey,
            'recoveryCodes' => $recoveryCodes,
        ]);
    }
}
