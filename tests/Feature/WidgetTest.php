<?php

namespace Tests\Feature;

use App\Controllers\UsersController;
use App\Models\User;
use App\Models\UserGroup;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;
use Larajax\Ui\Widgets\Form;
use Larajax\Ui\Widgets\Lists;
use Larajax\Ui\Widgets\ListStructure;
use October\Rain\Exception\ApplicationException;
use Tests\TestCase;

class WidgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Log::spy();
    }

    protected function makeList(): Lists
    {
        return Lists::createIn(new UsersController, [
            'model' => new User,
            'columns' => ['name' => ['label' => 'Name']],
        ]);
    }

    public function test_widget_errors_hide_internal_details_outside_debug_mode(): void
    {
        config(['app.debug' => false]);

        $list = $this->makeList();
        $list->handleError(new Exception('SQLSTATE connection details'));

        $this->assertSame("We're sorry, but something went wrong and the page cannot be displayed.", $list->getFatalError());
    }

    public function test_widget_errors_show_safe_exception_messages(): void
    {
        config(['app.debug' => false]);

        $list = $this->makeList();
        $list->handleError(new ApplicationException('Record is locked'));

        $this->assertSame('Record is locked', $list->getFatalError());
    }

    public function test_widget_errors_include_details_in_debug_mode(): void
    {
        config(['app.debug' => true]);

        $list = $this->makeList();
        $list->handleError(new Exception('Something broke'));

        $this->assertStringContainsString('"Something broke" on line', $list->getFatalError());
    }

    public function test_list_structure_reads_include_reference_pool_from_config(): void
    {
        $structure = ListStructure::createIn(new UsersController, [
            'model' => new User,
            'columns' => ['name' => ['label' => 'Name']],
            'showTree' => false,
            'includeReferencePool' => true,
        ]);

        $this->assertTrue($structure->includeReferencePool);
    }

    public function test_read_only_dropdown_renders_readonly_with_an_escaped_value(): void
    {
        $user = User::factory()->create();
        $user->name = 'quote"value';

        $form = Form::createIn(new UsersController, [
            'model' => $user,
            'fields' => [
                'name' => [
                    'label' => 'Name',
                    'type' => 'dropdown',
                    'readOnly' => true,
                    'options' => ['quote"value' => 'Quoted'],
                ],
            ],
        ]);

        $form->bindToController();
        $html = (string) $form->render();

        $this->assertStringContainsString('<div class="form-control" readonly>', $html);
        $this->assertStringContainsString('value="quote&quot;value"', $html);
        $this->assertStringNotContainsString('value="quote"value"', $html);
    }

    public function test_a_relation_field_without_a_scope_renders_for_a_plain_eloquent_model(): void
    {
        $group = UserGroup::factory()->create(['name' => 'Unscoped Group', 'code' => 'unscoped']);

        $form = Form::createIn(new UsersController, [
            'model' => User::factory()->create(),
            'fields' => [
                'groups' => ['label' => 'Groups', 'type' => 'relation'],
            ],
        ]);

        $form->bindToController();
        $html = (string) $form->render();

        $this->assertStringContainsString('Unscoped Group', $html);
        $this->assertStringContainsString('value="'.$group->id.'"', $html);
    }
}
