<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Alliance;
use App\Services\Game\AllianceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AllianceController extends Controller
{
    public function __construct(
        protected AllianceService $allianceService,
    ) {
    }

    public function overview(Request $request): JsonResponse
    {
        $alliance = $this->allianceService->resolve($request->user());

        return response()->json([
            'data' => $this->allianceService->tools($request->user(), $alliance),
        ]);
    }

    public function show(Request $request, Alliance $alliance): JsonResponse
    {
        $this->authorize('view', $alliance);

        return response()->json([
            'data' => $this->allianceService->tools($request->user(), $alliance),
        ]);
    }
}
