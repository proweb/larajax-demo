<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuickCreateTest extends TestCase
{
    use RefreshDatabase;

    protected function ajax(string $handler, User $user, array $data = [])
    {
        return $this->withHeaders([
            'X-AJAX-HANDLER' => $handler,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post("/users/{$user->id}/edit", $data + [
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function test_the_relation_dropdown_offers_a_create_new_option(): void
    {
        $user = User::factory()->create();

        $this->get("/users/{$user->id}/edit")
            ->assertOk()
            ->assertSee('data-control="relation-quick-create"', false)
            ->assertSee('formPrimaryGroup::onLoadQuickCreateForm', false)
            ->assertSee('- Create New Primary Group -');
    }

    public function test_on_load_quick_create_form_renders_the_related_model_form(): void
    {
        $user = User::factory()->create();

        $resp = $this->ajax('formPrimaryGroup::onLoadQuickCreateForm', $user, ['_relation_quick_create' => 1]);

        $resp->assertOk();
        $content = $resp->getContent();
        $this->assertStringContainsString('New Primary Group', $content);
        $this->assertStringContainsString('QuickCreate[name]', $content);
        $this->assertStringContainsString('formPrimaryGroup::onQuickCreateRecord', $content);
    }

    public function test_on_quick_create_record_creates_and_selects_the_record(): void
    {
        $user = User::factory()->create();

        $resp = $this->ajax('formPrimaryGroup::onQuickCreateRecord', $user, [
            '_relation_quick_create' => 1,
            'QuickCreate' => ['name' => 'Moderators', 'code' => 'moderators'],
        ]);

        $resp->assertOk();
        $group = UserGroup::where('code', 'moderators')->first();
        $this->assertNotNull($group);
        $this->assertSame('Moderators', $group->name);

        // The relation widget is re-rendered with the new record selected
        $html = $resp->json('__ajax.ops.0.html');
        $this->assertStringContainsString('data-quick-created', $html);
        $this->assertMatchesRegularExpression('/<option[^>]*selected="selected"[^>]*value="'.$group->id.'"/', $html);
    }

    public function test_saving_with_the_create_new_option_selected_stores_no_group(): void
    {
        $group = UserGroup::factory()->create(['code' => 'editors']);
        $user = User::factory()->create(['primary_group_id' => $group->id]);

        $this->ajax('onUpdate', $user, ['primary_group' => '__quick_create__'])->assertOk();

        $this->assertNull($user->refresh()->primary_group_id);
    }
}
