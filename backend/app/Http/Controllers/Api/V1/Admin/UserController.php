<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $users = User::query()
            ->when($request->filled('search'), fn ($q) => $q->where(fn ($q) => $q
                ->where('name', 'like', '%'.$request->input('search').'%')
                ->orWhere('phone', 'like', '%'.$request->input('search').'%')
                ->orWhere('email', 'like', '%'.$request->input('search').'%')))
            ->when($request->filled('role'), fn ($q) => $q->role($request->input('role')))
            ->with('roles')
            ->latest()
            ->paginate(20);

        return response()->apiSuccess(
            UserResource::collection($users),
            'OK',
            ['page' => $users->currentPage(), 'per_page' => $users->perPage(), 'total' => $users->total()],
        );
    }

    /**
     * doc09's "Suspend/ban user flow" — status is checked at auth time
     * (Sprint 7 hardening wires the actual gate), this just changes it.
     */
    public function updateStatus(Request $request, User $user): JsonResponse
    {
        $request->validate(['status' => ['required', 'string', 'in:active,suspended,banned']]);
        $before = $user->only('status');

        $user->update(['status' => $request->string('status')->toString()]);
        AuditLogger::log('user.status', $user, $before, $user->only('status'));

        return response()->apiSuccess(new UserResource($user), 'User status updated.');
    }
}
