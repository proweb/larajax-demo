<?php

namespace Tests\Feature;

use App\Controllers\UsersController;
use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Larajax\Ui\Widgets\Form;
use Tests\TestCase;

class RecordFinderTest extends TestCase
{
    use RefreshDatabase;

    protected function makeFinderForm(User $user, array $field = [], array $config = []): Form
    {
        $form = Form::createIn(new UsersController, $config + [
            'model' => $user,
            'fields' => [
                'primary_group' => $field + [
                    'label' => 'Primary Group',
                    'type' => 'recordfinder',
                    'list' => ['columns' => [
                        'name' => ['label' => 'Name', 'searchable' => true],
                        'code' => ['label' => 'Code'],
                    ]],
                    'nameFrom' => 'name',
                    'descriptionFrom' => 'code',
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

    public function test_a_selected_record_renders_its_name_and_description(): void
    {
        $group = UserGroup::factory()->create(['name' => 'Editors', 'code' => 'editors']);
        $user = User::factory()->create(['primary_group_id' => $group->id]);

        $html = (string) $this->makeFinderForm($user)->render();

        $this->assertStringContainsString('data-control="recordfinder"', $html);
        $this->assertStringContainsString('<span class="recordname">Editors</span>', $html);
        $this->assertStringContainsString('<span class="description"> - editors</span>', $html);
        $this->assertMatchesRegularExpression('/name="primary_group"[^>]*value="'.$group->id.'"/', $html);
        $this->assertStringContainsString('formPrimaryGroup::onRefresh', $html);
        $this->assertStringContainsString('formPrimaryGroup::onClearRecord', $html);
        $this->assertStringContainsString('Replace', $html);
    }

    public function test_an_empty_field_offers_select_without_clear(): void
    {
        $html = (string) $this->makeFinderForm(User::factory()->create(['primary_group_id' => null]))->render();

        $this->assertStringContainsString('Select', $html);
        $this->assertStringNotContainsString('onClearRecord', $html);
        $this->assertStringNotContainsString('class="record-item"', $html);
    }

    public function test_preview_mode_without_a_record_says_so(): void
    {
        $html = (string) $this->makeFinderForm(User::factory()->create(['primary_group_id' => null]), [], ['previewMode' => true])->render();

        $this->assertStringContainsString('There is no record selected.', $html);
    }

    public function test_the_find_popup_lists_records_that_pick_themselves_into_the_field(): void
    {
        $guest = UserGroup::factory()->create(['name' => 'Guest', 'code' => 'guest']);
        $editors = UserGroup::factory()->create(['name' => 'Editors', 'code' => 'editors']);
        $this->postRequest(['recordfinder_flag' => 1]);

        $form = $this->makeFinderForm(User::factory()->create(), ['scope' => 'withoutGuest']);
        $widget = $form->getFormWidget('primary_group');

        $html = (string) $widget->onFindRecord();

        $this->assertStringContainsString('Find Record', $html);
        $this->assertStringContainsString('class="recordfinder-popup"', $html);
        $this->assertStringContainsString('data-request-parent-form="#'.$widget->getId().'"', $html);
        $this->assertStringContainsString('formPrimaryGroupSearch', $html);
        $this->assertStringContainsString("jax.fetchControl(document.getElementById(&#039;".$widget->getId()."&#039;), &#039;recordfinder&#039;).updateRecord(this, &#039;".$editors->id."&#039;)", $html);
        $this->assertStringContainsString('Editors', $html);
        $this->assertStringNotContainsString('>Guest<', $html);
    }

    public function test_refreshing_with_a_picked_key_shows_that_record(): void
    {
        $editors = UserGroup::factory()->create(['name' => 'Editors', 'code' => 'editors']);
        $user = User::factory()->create(['primary_group_id' => null]);

        $form = $this->makeFinderForm($user);
        $this->postRequest(['primary_group' => $editors->id]);

        $result = $form->getFormWidget('primary_group')->onRefresh();
        $html = (string) reset($result);

        $this->assertStringContainsString('<span class="recordname">Editors</span>', $html);
        $this->assertMatchesRegularExpression('/value="'.$editors->id.'"/', $html);
    }

    public function test_clearing_the_record_empties_the_field(): void
    {
        $group = UserGroup::factory()->create(['name' => 'Editors', 'code' => 'editors']);
        $form = $this->makeFinderForm(User::factory()->create(['primary_group_id' => $group->id]));

        $result = $form->getFormWidget('primary_group')->onClearRecord();
        $html = (string) reset($result);

        $this->assertStringNotContainsString('Editors', $html);
        $this->assertStringContainsString('Select', $html);
        $this->assertNull($form->getFormWidget('primary_group')->getSaveValue(''));
        $this->assertSame('5', $form->getFormWidget('primary_group')->getSaveValue('5'));
    }

    public function test_use_relation_false_reads_the_record_from_the_model_class(): void
    {
        $group = UserGroup::factory()->create(['name' => 'Editors', 'code' => 'editors']);
        $user = User::factory()->create(['primary_group_id' => $group->id]);

        $form = Form::createIn(new UsersController, [
            'model' => $user,
            'fields' => [
                'primary_group_id' => [
                    'label' => 'Group',
                    'type' => 'recordfinder',
                    'list' => ['columns' => ['name' => ['label' => 'Name']]],
                    'useRelation' => false,
                    'modelClass' => UserGroup::class,
                ],
            ],
        ]);
        $form->bindToController();

        $html = (string) $form->render();

        $this->assertStringContainsString('<span class="recordname">Editors</span>', $html);
        $this->assertMatchesRegularExpression('/name="primary_group_id"[^>]*value="'.$group->id.'"/', $html);
    }
}
