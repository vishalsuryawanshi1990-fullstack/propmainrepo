<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreFaqRequest;
use App\Http\Requests\Admin\UpdateFaqRequest;
use App\Models\Faq;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;

class FaqController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->apiPaginated(Faq::orderBy('sort_order')->paginate(50));
    }

    public function store(StoreFaqRequest $request): JsonResponse
    {
        $faq = Faq::create($request->validated());
        AuditLogger::log('cms.faq.create', $faq, null, $faq->toArray());

        return response()->apiSuccess($faq, 'FAQ created.', [], 201);
    }

    public function update(UpdateFaqRequest $request, Faq $faq): JsonResponse
    {
        $before = $faq->toArray();
        $faq->update($request->validated());
        AuditLogger::log('cms.faq.update', $faq, $before, $faq->fresh()->toArray());

        return response()->apiSuccess($faq, 'FAQ updated.');
    }

    public function destroy(Faq $faq): JsonResponse
    {
        $before = $faq->toArray();
        $faq->delete();
        AuditLogger::log('cms.faq.delete', $faq, $before, null);

        return response()->apiSuccess(null, 'FAQ deleted.');
    }
}
