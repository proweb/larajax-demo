<?php

namespace Tests\Feature;

use App\Controllers\UsersController;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Larajax\Ui\Widgets\Form;
use Tests\TestCase;

class TagListTest extends TestCase
{
    use RefreshDatabase;

    protected function ajax(string $handler, UserGroup $group, array $data = [])
    {
        return $this->withHeaders([
            'X-AJAX-HANDLER' => $handler,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post("/user-groups/{$group->id}/edit", $data + [
            'name' => $group->name,
            'code' => $group->code,
        ]);
    }

    protected function makeUserForm(User $user, array $field, array $config = []): Form
    {
        $form = Form::createIn(new UsersController, $config + [
            'model' => $user,
            'fields' => ['field' => $field + ['label' => 'Tags', 'type' => 'taglist']],
        ]);

        $form->bindToController();

        return $form;
    }

    public function test_the_group_form_renders_members_as_a_tag_list(): void
    {
        $group = UserGroup::factory()->create(['code' => 'editors']);
        $member = User::factory()->create(['name' => 'Member Person']);
        User::factory()->create(['name' => 'Other Person']);
        $group->users()->attach($member);

        $html = $this->get("/user-groups/{$group->id}/edit")->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<select[^>]*name="users\[\]"[^>]*class="form-control custom-select\s+select-hide-selected"[^>]*multiple/', $html);
        $this->assertStringContainsString('data-placeholder="Add members"', $html);
        $this->assertMatchesRegularExpression('/<option\s+selected="selected"\s+value="'.$member->id.'"\s*>Member Person<\/option>/', $html);
        $this->assertStringContainsString('>Other Person</option>', $html);
    }

    public function test_saving_syncs_the_selected_members_and_an_empty_list_clears_them(): void
    {
        $group = UserGroup::factory()->create(['code' => 'editors']);
        [$first, $second] = User::factory()->count(2)->create();

        $this->ajax('onUpdate', $group, ['users' => [$first->id, $second->id]])->assertOk();
        $this->assertEqualsCanonicalizing([$first->id, $second->id], $group->users()->pluck('users.id')->all());

        $this->ajax('onUpdate', $group, ['users' => ''])->assertOk();
        $this->assertCount(0, $group->users()->get());
    }

    public function test_the_quick_create_form_leaves_members_out(): void
    {
        $user = User::factory()->create();

        $resp = $this->withHeaders([
            'X-AJAX-HANDLER' => 'formPrimaryGroup::onLoadQuickCreateForm',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post("/users/{$user->id}/edit", [
            '_relation_quick_create' => 1,
            'name' => $user->name,
            'email' => $user->email,
        ]);

        $resp->assertOk();
        $this->assertStringContainsString('QuickCreate[name]', $resp->getContent());
        $this->assertStringNotContainsString('QuickCreate[users]', $resp->getContent());
    }

    public function test_string_mode_splits_and_joins_custom_tags(): void
    {
        $form = $this->makeUserForm(User::factory()->create(['notes' => 'red,green']), [
            'valueFrom' => 'notes',
            'customTags' => true,
        ]);

        $html = (string) $form->render();
        $this->assertStringContainsString('data-token-separators=","', $html);
        $this->assertMatchesRegularExpression('/<option\s+selected="selected"\s+value="red"\s*>red<\/option>/', $html);
        $this->assertMatchesRegularExpression('/<option\s+selected="selected"\s+value="green"\s*>green<\/option>/', $html);

        $this->assertSame('red,blue', $form->getFormWidget('field')->getSaveValue(['red', 'blue']));
    }

    public function test_a_space_separator_and_max_items_render_on_the_select(): void
    {
        $form = $this->makeUserForm(User::factory()->create(['notes' => 'one two']), [
            'valueFrom' => 'notes',
            'customTags' => true,
            'separator' => 'space',
            'maxItems' => 3,
        ]);

        $html = (string) $form->render();
        $this->assertStringContainsString('data-token-separators=" "', $html);
        $this->assertStringContainsString('data-maximum-selection-length="3"', $html);
        $this->assertSame('a b', $form->getFormWidget('field')->getSaveValue(['a', 'b']));
    }

    public function test_an_array_cast_attribute_uses_array_mode(): void
    {
        $form = $this->makeUserForm(User::factory()->create(['links' => ['alpha', 'beta']]), [
            'valueFrom' => 'links',
            'options' => ['alpha' => 'Alpha', 'beta' => 'Beta', 'gamma' => 'Gamma'],
        ]);

        $html = (string) $form->render();
        $widget = $form->getFormWidget('field');

        $this->assertSame('array', $widget->mode);
        $this->assertMatchesRegularExpression('/<option\s+selected="selected"\s+value="alpha"\s*>Alpha<\/option>/', $html);
        $this->assertSame(['gamma'], $widget->getSaveValue(['gamma']));
    }

    public function test_a_relation_uses_relation_mode_with_its_records_as_options(): void
    {
        $user = User::factory()->create();
        $groups = UserGroup::factory()->count(2)->sequence(['name' => 'Alpha', 'code' => 'alpha'], ['name' => 'Beta', 'code' => 'beta'])->create();
        $user->groups()->attach($groups[1]);

        $form = $this->makeUserForm($user, ['valueFrom' => 'groups', 'nameFrom' => 'name']);
        $html = (string) $form->render();

        $this->assertSame('relation', $form->getFormWidget('field')->mode);
        $this->assertMatchesRegularExpression('/<option\s+value="'.$groups[0]->id.'"\s*>Alpha<\/option>/', $html);
        $this->assertMatchesRegularExpression('/<option\s+selected="selected"\s+value="'.$groups[1]->id.'"\s*>Beta<\/option>/', $html);
    }

    public function test_preview_mode_lists_the_selected_labels(): void
    {
        $form = $this->makeUserForm(User::factory()->create(['notes' => 'red']), [
            'valueFrom' => 'notes',
            'options' => ['red' => 'Red', 'blue' => 'Blue'],
        ], ['previewMode' => true]);

        $html = (string) $form->render();

        $this->assertStringContainsString('taglist--preview', $html);
        $this->assertStringContainsString('<li class="taglist__item">Red</li>', $html);
        $this->assertStringNotContainsString('Blue', $html);
        $this->assertStringNotContainsString('<select', $html);
    }
}
