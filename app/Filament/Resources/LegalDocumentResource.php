<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Models\LegalDocument;
use App\Models\User;
use Illuminate\Support\Facades\Gate;

class LegalDocumentResource
{
    public static function canViewAny(User $user): bool
    {
        return Gate::forUser($user)->allows('viewAny', LegalDocument::class);
    }

    public static function canUpdate(User $user): bool
    {
        return Gate::forUser($user)->allows('update', LegalDocument::class);
    }
}
