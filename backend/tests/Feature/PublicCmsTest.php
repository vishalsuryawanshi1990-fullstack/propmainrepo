<?php

namespace Tests\Feature;

use App\Models\BannerCms;
use App\Models\BlogCms;
use App\Models\Faq;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicCmsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_active_current_banners_for_the_requested_placement_are_returned(): void
    {
        BannerCms::create(['title' => 'Active', 'image_path' => 'a.jpg', 'placement' => 'app_home', 'is_active' => true]);
        BannerCms::create(['title' => 'Inactive', 'image_path' => 'b.jpg', 'placement' => 'app_home', 'is_active' => false]);
        BannerCms::create(['title' => 'Website', 'image_path' => 'c.jpg', 'placement' => 'website_home', 'is_active' => true]);
        BannerCms::create(['title' => 'Expired', 'image_path' => 'd.jpg', 'placement' => 'app_home', 'is_active' => true, 'ends_at' => now()->subDay()]);

        $response = $this->getJson('/api/v1/banners');

        $response->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.title', 'Active');
    }

    public function test_only_published_blogs_are_listed_and_fetchable_by_slug(): void
    {
        BlogCms::create(['title' => 'Draft', 'slug' => 'draft', 'body' => 'x', 'status' => 'draft']);
        BlogCms::create(['title' => 'Live', 'slug' => 'live-post', 'body' => 'x', 'status' => 'published', 'published_at' => now()]);

        $this->getJson('/api/v1/blogs')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/v1/blogs/live-post')->assertOk();
        $this->getJson('/api/v1/blogs/draft')->assertStatus(404);
    }

    public function test_only_active_faqs_are_listed(): void
    {
        Faq::create(['question' => 'Q1', 'answer' => 'A1', 'is_active' => true]);
        Faq::create(['question' => 'Q2', 'answer' => 'A2', 'is_active' => false]);

        $this->getJson('/api/v1/faqs')->assertOk()->assertJsonCount(1, 'data');
    }
}
