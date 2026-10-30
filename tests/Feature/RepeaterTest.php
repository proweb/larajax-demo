<?php
namespace Tests\Feature;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RepeaterTest extends TestCase
{
    use RefreshDatabase;

    public function test_edit_page_renders_repeater_with_existing_items(): void
    {
        $user = User::factory()->create([
            'links' => [
                ['title' => 'Blog', 'url' => 'https://example.com/blog'],
                ['title' => 'Portfolio', 'url' => 'https://example.com/work'],
            ],
        ]);

        $resp = $this->get("/users/{$user->id}/edit");

        $resp->assertOk();
        $resp->assertSee('data-control="repeateraccordion"', false);
        $resp->assertSee('name="links[0][title]"', false);
        $resp->assertSee('value="Blog"', false);
        $resp->assertSee('name="links[1][url]"', false);
        $resp->assertSee('value="https://example.com/work"', false);
        $resp->assertSee('formLinks::onAddItem', false);
    }

    public function test_on_update_saves_repeater_items_as_json(): void
    {
        $user = User::factory()->create(['links' => null]);

        $resp = $this->withHeaders([
            'X-AJAX-HANDLER' => 'onUpdate',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post("/users/{$user->id}/edit", [
            'name' => $user->name,
            'email' => $user->email,
            'formLinks_loaded' => 1,
            'links' => [
                ['title' => 'Docs', 'url' => 'https://example.com/docs', '_index' => 0],
                ['title' => 'About', 'url' => 'https://example.com/about', '_index' => 1],
            ],
        ]);

        $resp->assertOk();
        $user->refresh();
        $this->assertSame([
            ['title' => 'Docs', 'url' => 'https://example.com/docs'],
            ['title' => 'About', 'url' => 'https://example.com/about'],
        ], $user->links);
    }

    public function test_on_update_with_no_items_clears_the_value(): void
    {
        $user = User::factory()->create([
            'links' => [['title' => 'Old', 'url' => 'https://example.com/old']],
        ]);

        $resp = $this->withHeaders([
            'X-AJAX-HANDLER' => 'onUpdate',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post("/users/{$user->id}/edit", [
            'name' => $user->name,
            'email' => $user->email,
            'formLinks_loaded' => 1,
            'links' => '',
        ]);

        $resp->assertOk();
        $user->refresh();
        $this->assertNull($user->links);
    }

    public function test_on_add_item_returns_a_new_item_partial(): void
    {
        $user = User::factory()->create([
            'links' => [['title' => 'Blog', 'url' => 'https://example.com/blog']],
        ]);

        $resp = $this->withHeaders([
            'X-AJAX-HANDLER' => 'formLinks::onAddItem',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post("/users/{$user->id}/edit", [
            'name' => $user->name,
            'email' => $user->email,
            'formLinks_loaded' => 1,
            'links' => [
                ['title' => 'Blog', 'url' => 'https://example.com/blog', '_index' => 0],
            ],
        ]);

        $resp->assertOk();

        // The new item is appended with the next available index
        $content = $resp->getContent();
        $this->assertStringContainsString('links[1][title]', $content);
        $this->assertStringContainsString('field-repeater-item', $content);
    }
}
