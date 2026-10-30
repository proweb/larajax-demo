<?php

namespace Tests\Feature;

use App\Controllers\UsersController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Larajax\Ui\Widgets\Form;
use Tests\TestCase;

class SensitiveTest extends TestCase
{
    use RefreshDatabase;

    protected function makeSensitiveForm(User $user, array $config = []): Form
    {
        $form = Form::createIn(new UsersController, [
            'model' => $user,
            'fields' => [
                'notes' => $config + ['label' => 'Secret', 'type' => 'sensitive'],
            ],
        ]);

        $form->bindToController();

        return $form;
    }

    public function test_a_stored_value_renders_masked_with_a_reveal_handler(): void
    {
        $form = $this->makeSensitiveForm(User::factory()->create(['notes' => 's3cret']), ['allowCopy' => true]);
        $html = (string) $form->render();

        $this->assertStringContainsString('data-control="sensitive"', $html);
        $this->assertStringContainsString('data-reveal-handler="formNotes::onShowValue"', $html);
        $this->assertMatchesRegularExpression('/type="password"\s+name="notes"[^>]*value="__hidden__"/', $html);
        $this->assertStringContainsString('data-sensitive-copy', $html);
        $this->assertStringContainsString('data-icon-show="ph ph-eye"', $html);
        $this->assertStringContainsString('data-icon-hide="ph ph-eye-slash"', $html);
        $this->assertStringNotContainsString('s3cret', $html);

        $this->assertSame(['value' => 's3cret'], $form->getFormWidget('notes')->onShowValue());
    }

    public function test_the_placeholder_saves_the_stored_value_and_new_input_replaces_it(): void
    {
        $widget = $this->makeSensitiveForm(User::factory()->create(['notes' => 's3cret']))->getFormWidget('notes');

        $this->assertSame('s3cret', $widget->getSaveValue('__hidden__'));
        $this->assertSame('changed', $widget->getSaveValue('changed'));
    }

    public function test_an_empty_value_renders_without_the_placeholder(): void
    {
        $html = (string) $this->makeSensitiveForm(User::factory()->create(['notes' => null]))->render();

        $this->assertMatchesRegularExpression('/name="notes"[^>]*value=""/', $html);
        $this->assertStringNotContainsString('data-sensitive-copy', $html);
    }

    public function test_textarea_mode_shows_a_masked_facade(): void
    {
        $html = (string) $this->makeSensitiveForm(User::factory()->create(['notes' => 's3cret']), ['mode' => 'textarea'])->render();

        $this->assertStringContainsString('data-display-mode="textarea"', $html);
        $this->assertStringContainsString('data-sensitive-facade', $html);
        $this->assertMatchesRegularExpression('/<textarea\s+name="notes"/', $html);
        $this->assertStringContainsString('Reveal Contents', $html);
    }

    public function test_a_read_only_field_is_disabled(): void
    {
        $html = (string) $this->makeSensitiveForm(User::factory()->create(['notes' => 's3cret']), ['readOnly' => true])->render();

        $this->assertMatchesRegularExpression('/name="notes"[^>]*disabled/', $html);
    }
}
