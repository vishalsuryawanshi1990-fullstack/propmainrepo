<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $logs = AuditLog::query()
            ->when($request->filled('actor_id'), fn ($q) => $q->where('actor_id', $request->integer('actor_id')))
            ->when($request->filled('action'), fn ($q) => $q->where('action', $request->string('action')))
            ->with('actor')
            ->latest()
            ->paginate(30);

        return response()->apiPaginated($logs, fn (AuditLog $log) => [
            'id' => $log->id,
            'actor' => $log->actor?->name,
            'action' => $log->action,
            'subject_type' => class_basename($log->subject_type),
            'subject_id' => $log->subject_id,
            'before' => $log->before,
            'after' => $log->after,
            'ip_address' => $log->ip_address,
            'created_at' => $log->created_at,
        ]);
    }
}
