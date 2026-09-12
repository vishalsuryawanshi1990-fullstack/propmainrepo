<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        return response()->apiSuccess(new UserResource($request->user()->load('wallet')));
    }

    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $user->fill($request->only(['name', 'email']))->save();

        return response()->apiSuccess(new UserResource($user->fresh('wallet')), 'Profile updated.');
    }

    /**
     * doc05 (DPDP Act right-to-erasure) + doc08 ("delete-account flow,
     * required for store compliance"): purges KYC files, revokes every
     * token, then soft-deletes the account itself.
     */
    public function destroy(Request $request): JsonResponse
    {
        $user = $request->user();

        DB::transaction(function () use ($user) {
            foreach ($user->kycDocuments as $document) {
                Storage::disk('local')->delete($document->file_path);
            }

            $user->kycDocuments()->delete();
            $user->tokens()->delete();
            $user->delete();
        });

        return response()->apiSuccess(null, 'Account deleted.');
    }
}
