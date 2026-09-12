<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBannerRequest;
use App\Http\Requests\Admin\UpdateBannerRequest;
use App\Models\BannerCms;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;

class BannerController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->apiPaginated(BannerCms::latest()->paginate(20));
    }

    public function store(StoreBannerRequest $request): JsonResponse
    {
        $banner = BannerCms::create($request->validated());
        AuditLogger::log('cms.banner.create', $banner, null, $banner->toArray());

        return response()->apiSuccess($banner, 'Banner created.', [], 201);
    }

    public function update(UpdateBannerRequest $request, BannerCms $banner): JsonResponse
    {
        $before = $banner->toArray();
        $banner->update($request->validated());
        AuditLogger::log('cms.banner.update', $banner, $before, $banner->fresh()->toArray());

        return response()->apiSuccess($banner, 'Banner updated.');
    }

    public function destroy(BannerCms $banner): JsonResponse
    {
        $before = $banner->toArray();
        $banner->delete();
        AuditLogger::log('cms.banner.delete', $banner, $before, null);

        return response()->apiSuccess(null, 'Banner deleted.');
    }
}
