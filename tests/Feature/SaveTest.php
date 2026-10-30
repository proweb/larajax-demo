<?php
namespace Tests\Feature;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SaveTest extends TestCase
{
    use RefreshDatabase;

    public function test_on_update_saves_the_user(): void
    {
        $group = UserGroup::factory()->create(['code' => 'admin']);
        $user = User::factory()->create(['name' => 'Original']);

        $resp = $this->withHeaders([
            'X-AJAX-HANDLER' => 'onUpdate',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post("/users/{$user->id}/edit", [
            'name' => 'Renamed Person',
            'email' => $user->email,
            'notes' => 'changed via ajax',
            'primary_group' => $group->id,
            'groups' => [$group->id],
        ]);

        $resp->assertOk();
        $user->refresh();
        $this->assertSame('Renamed Person', $user->name);
        $this->assertSame('changed via ajax', $user->notes);
        $this->assertSame($group->id, $user->primary_group_id);
        $this->assertSame(1, $user->groups()->count());
    }

    public function test_on_update_clears_all_secondary_groups(): void
    {
        $group = UserGroup::factory()->create(['code' => 'editors']);
        $user = User::factory()->create();
        $user->groups()->attach($group);

        // An empty checkbox list submits only its empty hidden input
        $this->withHeaders([
            'X-AJAX-HANDLER' => 'onUpdate',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post("/users/{$user->id}/edit", [
            'name' => $user->name,
            'email' => $user->email,
            'groups' => '',
        ])->assertOk();

        $this->assertSame(0, $user->groups()->count());
    }

    public function test_on_update_clears_an_emptied_field(): void
    {
        // A posted empty string must clear the field even when middleware converts it to null
        $user = User::factory()->create(['notes' => 'existing notes']);

        $resp = $this->withHeaders([
            'X-AJAX-HANDLER' => 'onUpdate',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post("/users/{$user->id}/edit", [
            'name' => $user->name,
            'email' => $user->email,
            'notes' => '',
        ]);

        $resp->assertOk();
        $user->refresh();
        $this->assertSame('', (string) $user->notes);
    }

    public function test_on_update_validates_required_name(): void
    {
        $user = User::factory()->create();
        $resp = $this->withHeaders([
            'X-AJAX-HANDLER' => 'onUpdate',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post("/users/{$user->id}/edit", ['name' => '', 'email' => $user->email]);

        $resp->assertStatus(422);
    }

    public function test_on_store_creates_a_user(): void
    {
        $group = UserGroup::factory()->create();
        $resp = $this->withHeaders([
            'X-AJAX-HANDLER' => 'onStore',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post('/users/create', [
            'name' => 'Brand New',
            'email' => 'brandnew@example.com',
            'password' => 'secret123',
            'password_confirmation' => 'secret123',
            'primary_group' => $group->id,
        ]);

        $resp->assertOk();
        $this->assertDatabaseHas('users', ['email' => 'brandnew@example.com', 'name' => 'Brand New']);
    }
}
