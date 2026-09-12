<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\BannerCms;
use App\Models\BlogCms;
use App\Models\Faq;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public read-only CMS content for the app home screen and the SEO
 * website — see 03-database-schema.md's banners_cms/blogs_cms/faqs and
 * doc09's admin-side CRUD (Api\V1\Admin\{Banner,Blog,Faq}Controller).
 */
class CmsController extends Controller
{
    public function banners(Request $request): JsonResponse
    {
        $banners = BannerCms::where('is_active', true)
            ->where('placement', $request->input('placement', 'app_home'))
            ->where(fn ($q) => $q->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($q) => $q->whereNull('ends_at')->orWhere('ends_at', '>=', now()))
            ->orderBy('sort_order')
            ->get();

        return response()->apiSuccess($banners);
    }

    public function blogs(): JsonResponse
    {
        $blogs = BlogCms::where('status', 'published')
            ->latest('published_at')
            ->paginate(10);

        return response()->apiPaginated($blogs);
    }

    public function blog(string $slug): JsonResponse
    {
        $blog = BlogCms::where('slug', $slug)->where('status', 'published')->firstOrFail();

        return response()->apiSuccess($blog);
    }

    public function faqs(): JsonResponse
    {
        $faqs = Faq::where('is_active', true)->orderBy('sort_order')->get();

        return response()->apiSuccess($faqs);
    }
}
