<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UsersTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_home_page_redirects_to_users(): void
    {
        $this->get('/')->assertRedirect('users');
    }

    public function test_the_users_list_renders(): void
    {
        User::factory()->count(3)->create();

        $this->get('users')
            ->assertOk()
            ->assertSee('Users');
    }

    public function test_the_structure_page_renders_the_flat_reorder_variant(): void
    {
        User::factory()->count(2)->create();

        $this->get('users/structure')
            ->assertOk()
            ->assertSee('list-cell-tree', false);
    }

    public function test_the_toolbar_renders_declarative_yaml_buttons(): void
    {
        User::factory()->create();

        $this->get('users')
            ->assertOk()
            ->assertSee('New User')
            ->assertSee('data-request="onDelete"', false)
            ->assertSee('data-request-confirm', false)
            ->assertSee('data-list-checked-trigger', false)
            ->assertSee('data-list-checked-request', false);
    }

    public function test_on_delete_removes_the_checked_users(): void
    {
        $users = User::factory()->count(3)->create();

        $resp = $this->withHeaders([
            'X-AJAX-HANDLER' => 'onDelete',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post('users', [
            'checked' => [$users[0]->id, $users[2]->id],
        ]);

        $resp->assertOk();
        $this->assertSame([$users[1]->id], User::pluck('id')->all());
    }

    public function test_the_edit_form_renders_for_a_user(): void
    {
        $group = UserGroup::factory()->create();
        $user = User::factory()->create(['primary_group_id' => $group->id]);

        $this->get("users/{$user->id}/edit")
            ->assertOk()
            ->assertSee($user->email);
    }
}
