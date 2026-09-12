<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreBlogRequest;
use App\Http\Requests\Admin\UpdateBlogRequest;
use App\Models\BlogCms;
use App\Services\Audit\AuditLogger;
use Illuminate\Http\JsonResponse;

class BlogController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->apiPaginated(BlogCms::latest()->paginate(20));
    }

    public function store(StoreBlogRequest $request): JsonResponse
    {
        $blog = BlogCms::create([
            ...$request->validated(),
            'author_id' => $request->user()->id,
            'published_at' => $request->input('status') === 'published' ? now() : null,
        ]);
        AuditLogger::log('cms.blog.create', $blog, null, $blog->toArray());

        return response()->apiSuccess($blog, 'Blog post created.', [], 201);
    }

    public function update(UpdateBlogRequest $request, BlogCms $blog): JsonResponse
    {
        $before = $blog->toArray();

        $data = $request->validated();

        if (($data['status'] ?? null) === 'published' && $blog->published_at === null) {
            $data['published_at'] = now();
        }

        $blog->update($data);
        AuditLogger::log('cms.blog.update', $blog, $before, $blog->fresh()->toArray());

        return response()->apiSuccess($blog, 'Blog post updated.');
    }

    public function destroy(BlogCms $blog): JsonResponse
    {
        $before = $blog->toArray();
        $blog->delete();
        AuditLogger::log('cms.blog.delete', $blog, $before, null);

        return response()->apiSuccess(null, 'Blog post deleted.');
    }
}
