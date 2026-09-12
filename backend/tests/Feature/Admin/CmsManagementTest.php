<?php

namespace Tests\Feature\Admin;

use App\Models\BannerCms;
use App\Models\BlogCms;
use App\Models\Faq;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CmsManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
    }

    public function test_a_moderator_cannot_manage_cms(): void
    {
        $moderator = User::factory()->create();
        $moderator->assignRole('moderator');

        $this->actingAs($moderator)->postJson('/api/v1/admin/cms/faqs', [
            'question' => 'Q?', 'answer' => 'A.',
        ])->assertStatus(403);
    }

    public function test_admin_can_manage_banners(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $create = $this->actingAs($admin)->postJson('/api/v1/admin/cms/banners', [
            'title' => 'Diwali Offer', 'image_path' => 'banners/diwali.jpg',
        ]);
        $create->assertStatus(201);

        $id = $create->json('data.id');
        $this->actingAs($admin)->deleteJson("/api/v1/admin/cms/banners/{$id}")->assertOk();
        $this->assertSoftDeleted(BannerCms::class, ['id' => $id]);
    }

    public function test_admin_can_publish_a_blog_post(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/cms/blogs', [
            'title' => 'Buying Tips',
            'slug' => 'buying-tips',
            'body' => 'Some helpful content.',
            'status' => 'published',
        ]);

        $response->assertStatus(201);
        $this->assertNotNull(BlogCms::where('slug', 'buying-tips')->first()->published_at);
    }

    public function test_admin_can_manage_faqs(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->postJson('/api/v1/admin/cms/faqs', [
            'question' => 'How do unlocks work?',
            'answer' => 'You spend a credit to see contact details.',
        ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas(Faq::class, ['question' => 'How do unlocks work?']);
    }
}
