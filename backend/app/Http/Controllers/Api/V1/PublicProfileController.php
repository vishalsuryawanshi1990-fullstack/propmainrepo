<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicProfileResource;
use App\Models\User;
use Illuminate\Http\JsonResponse;

class PublicProfileController extends Controller
{
    public function show(User $user): JsonResponse
    {
        abort_unless($user->hasAnyRole(['agent', 'seller']), 404);

        return response()->apiSuccess(
            new PublicProfileResource($user->load(['agentProfile', 'sellerProfile']))
        );
    }
}
