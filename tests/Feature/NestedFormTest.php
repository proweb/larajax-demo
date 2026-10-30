<?php

namespace Tests\Feature;

use App\Controllers\UsersController;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Larajax\Ui\Classes\FormField;
use Larajax\Ui\Widgets\Form;
use Tests\TestCase;

class NestedFormTest extends TestCase
{
    use RefreshDatabase;

    protected function makeNestedForm(User $user, string $field, array $config = []): Form
    {
        $form = Form::createIn(new UsersController, [
            'model' => $user,
            'fields' => [
                $field => $config + [
                    'label' => 'Nested',
                    'type' => 'nestedform',
                    'form' => [
                        'fields' => [
                            'name' => ['label' => 'Name', 'span' => 'left'],
                            'code' => ['label' => 'Code', 'span' => 'right'],
                        ],
                    ],
                ],
            ],
        ]);

        $form->bindToController();

        return $form;
    }

    protected function postRequest(array $data): void
    {
        $this->app->instance('request', Request::create('/users', 'POST', $data));
        Facade::clearResolvedInstance('request');
    }

    public function test_a_json_attribute_renders_and_saves_as_a_nested_form(): void
    {
        $user = User::factory()->create(['links' => ['name' => 'Blog', 'code' => 'blog']]);

        $html = (string) $this->makeNestedForm($user, 'links')->render();

        $this->assertStringContainsString('class="nested-form is-paneled"', $html);
        $this->assertMatchesRegularExpression('/name="links\[name\]"[^>]*value="Blog"/', $html);
        $this->assertMatchesRegularExpression('/name="links\[code\]"[^>]*value="blog"/', $html);

        $this->postRequest(['links' => ['name' => 'Work', 'code' => 'work']]);

        $saveData = $this->makeNestedForm($user, 'links')->getSaveData();
        $this->assertSame(['name' => 'Work', 'code' => 'work'], $saveData['links']);
    }

    public function test_a_relation_renders_and_saves_the_related_record(): void
    {
        $group = UserGroup::factory()->create(['name' => 'Editors', 'code' => 'editors']);
        $user = User::factory()->create(['primary_group_id' => $group->id]);

        $html = (string) $this->makeNestedForm($user, 'primary_group', ['showPanel' => false])->render();

        $this->assertStringContainsString('class="nested-form "', $html);
        $this->assertMatchesRegularExpression('/name="primary_group\[name\]"[^>]*value="Editors"/', $html);

        $this->postRequest(['primary_group' => ['name' => 'Writers', 'code' => 'writers']]);

        $saveData = $this->makeNestedForm($user, 'primary_group')->getSaveData();

        // The related record saves itself, so the parent model has nothing to set
        $this->assertSame(FormField::NO_SAVE_DATA, $saveData['primary_group']);
        $this->assertSame('Writers', $group->refresh()->name);
        $this->assertSame('writers', $group->code);
    }

    public function test_a_disabled_nested_form_renders_in_preview_mode(): void
    {
        $user = User::factory()->create(['links' => ['name' => 'Blog', 'code' => 'blog']]);

        $html = (string) $this->makeNestedForm($user, 'links', ['disabled' => true])->render();

        $this->assertStringContainsString('Blog', $html);
        $this->assertStringNotContainsString('name="links[name]"', $html);
    }
}
