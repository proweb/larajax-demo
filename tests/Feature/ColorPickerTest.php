<?php

namespace Tests\Feature;

use App\Controllers\UsersController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Larajax\Ui\Widgets\Form;
use October\Rain\Exception\ApplicationException;
use Tests\TestCase;

class ColorPickerTest extends TestCase
{
    use RefreshDatabase;

    protected function makeColorForm(User $user, array $config = [], array $formConfig = []): Form
    {
        $form = Form::createIn(new UsersController, $formConfig + [
            'model' => $user,
            'fields' => [
                'notes' => $config + ['label' => 'Color', 'type' => 'colorpicker'],
            ],
        ]);

        $form->bindToController();

        return $form;
    }

    public function test_the_field_renders_an_input_with_the_preset_colors_as_swatches(): void
    {
        $html = (string) $this->makeColorForm(User::factory()->create(['notes' => '#3498db']))->render();

        $this->assertStringContainsString('data-control="colorpicker"', $html);
        $this->assertStringContainsString('data-swatches="[&quot;#1abc9c&quot;,&quot;#16a085&quot;', $html);
        $this->assertStringContainsString('<div class="clr-field" style="color: #3498db">', $html);
        $this->assertMatchesRegularExpression('/name="notes"\s+value="#3498db"[^>]*data-colorpicker-input/', $html);
        $this->assertStringNotContainsString('data-swatches-only', $html);
        $this->assertStringNotContainsString('data-show-alpha', $html);
    }

    public function test_options_render_as_control_attributes(): void
    {
        $html = (string) $this->makeColorForm(User::factory()->create(), [
            'availableColors' => ['#000000', '#ffffff'],
            'allowCustom' => false,
            'showAlpha' => true,
            'allowEmpty' => true,
        ])->render();

        $this->assertStringContainsString('data-swatches="[&quot;#000000&quot;,&quot;#ffffff&quot;]"', $html);
        $this->assertStringContainsString('data-swatches-only="true"', $html);
        $this->assertStringContainsString('data-show-alpha="true"', $html);
        $this->assertStringContainsString('data-allow-empty="true"', $html);
    }

    public function test_show_input_drops_the_swatches(): void
    {
        $html = (string) $this->makeColorForm(User::factory()->create(), ['showInput' => true])->render();

        $this->assertStringContainsString('data-swatches="[]"', $html);
    }

    public function test_available_colors_can_come_from_a_model_method(): void
    {
        $user = new class extends User {
            protected $table = 'users';

            public function getBrandColors(): array
            {
                return ['#ff0000', '#00ff00'];
            }
        };

        $html = (string) $this->makeColorForm($user, ['availableColors' => 'getBrandColors'])->render();
        $this->assertStringContainsString('data-swatches="[&quot;#ff0000&quot;,&quot;#00ff00&quot;]"', $html);

        $this->expectException(ApplicationException::class);
        $this->expectExceptionMessage('must define a method missingColors()');
        (string) $this->makeColorForm($user, ['availableColors' => 'missingColors'])->render();
    }

    public function test_locked_fields_render_without_the_picker(): void
    {
        $user = User::factory()->create(['notes' => '#3498db']);

        $readOnly = (string) $this->makeColorForm($user, ['readOnly' => true])->render();
        $this->assertStringNotContainsString('data-control="colorpicker"', $readOnly);
        $this->assertStringNotContainsString('data-colorpicker-input', $readOnly);
        $this->assertMatchesRegularExpression('/name="notes"[^>]*readonly/', $readOnly);

        $preview = (string) $this->makeColorForm($user, [], ['previewMode' => true])->render();
        $this->assertStringNotContainsString('data-control="colorpicker"', $preview);
        $this->assertMatchesRegularExpression('/name="notes"[^>]*disabled/', $preview);
        $this->assertStringContainsString('style="color: #3498db"', $preview);
    }

    public function test_saved_values_are_normalized_to_hex(): void
    {
        $widget = $this->makeColorForm(User::factory()->create())->getFormWidget('notes');

        $this->assertNull($widget->getSaveValue(''));
        $this->assertSame('#3498db', $widget->getSaveValue('3498db'));
        $this->assertSame('#3498db80', $widget->getSaveValue('#3498db80'));
    }
}
