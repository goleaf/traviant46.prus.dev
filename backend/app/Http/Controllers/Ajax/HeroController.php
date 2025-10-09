<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Models\Hero;
use App\Services\Game\HeroService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HeroController extends Controller
{
    public function __construct(
        protected HeroService $heroService,
    ) {
    }

    public function overview(Request $request): JsonResponse
    {
        $hero = $this->heroService->resolve($request->user());

        return response()->json([
            'data' => $this->heroService->overview($request->user(), $hero),
        ]);
    }

    public function inventory(Request $request): JsonResponse
    {
        $hero = $this->heroService->resolve($request->user());

        return response()->json([
            'data' => $hero->equipment ?? [],
        ]);
    }

    public function show(Request $request, Hero $hero): JsonResponse
    {
        $this->authorize('view', $hero);

        return response()->json([
            'data' => $this->heroService->overview($request->user(), $hero),
        ]);
    }
}
