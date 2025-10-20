<?php

declare(strict_types=1);

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\JsonResponse;

class OrderController extends Controller
{
    use AuthorizesRequests;

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Order::class);

        return response()->json(['data' => []]);
    }

    public function create(): JsonResponse
    {
        $this->authorize('viewAny', Product::class);

        return response()->json(['status' => 'ready']);
    }
}
