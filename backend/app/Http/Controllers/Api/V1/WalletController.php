<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\WalletResource;
use App\Http\Resources\WalletTransactionResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->apiSuccess(new WalletResource($request->user()->wallet));
    }

    public function transactions(Request $request): JsonResponse
    {
        $transactions = $request->user()->wallet->transactions()->latest()->paginate(20);

        return response()->apiSuccess(
            WalletTransactionResource::collection($transactions),
            'OK',
            ['page' => $transactions->currentPage(), 'per_page' => $transactions->perPage(), 'total' => $transactions->total()],
        );
    }
}
