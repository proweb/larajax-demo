<?php

namespace Tests\Feature;

use App\Controllers\UsersController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Larajax\Ui\Widgets\Filter;
use Larajax\Ui\Widgets\Form;
use Tests\TestCase;

class CheckMarkupTest extends TestCase
{
    use RefreshDatabase;

    /**
     * renderForm renders a user form holding the given field definitions.
     *
     * @param  array<string, array<string, mixed>>  $fields
     */
    protected function renderForm(array $fields): string
    {
        $form = Form::createIn(new UsersController, [
            'model' => User::factory()->create(),
            'fields' => $fields,
        ]);

        $form->bindToController();

        return (string) $form->render();
    }

    public function test_a_checkbox_field_renders_a_check_beside_its_label_and_comment(): void
    {
        $html = $this->renderForm([
            'is_mail_blocked' => [
                'label' => 'Block mail',
                'type' => 'checkbox',
                'comment' => 'Stops outgoing mail.',
            ],
        ]);

        $this->assertMatchesRegularExpression('/<div class="form-field"\s*>\s*<input\s+type="hidden"/', $html);
        $this->assertMatchesRegularExpression('/<input\s+class="check"\s+type="checkbox"\s+id="[^"]*is_mail_blocked"/', $html);
        $this->assertMatchesRegularExpression('/<div class="form-field-content">\s*<label for="[^"]*is_mail_blocked">\s*Block mail/', $html);
        $this->assertStringContainsString('<p class="form-text">Stops outgoing mail.</p>', $html);
        $this->assertStringNotContainsString('form-check', $html);
    }

    public function test_a_switch_field_wraps_its_input_in_a_switch_track(): void
    {
        $html = $this->renderForm([
            'is_two_factor_enabled' => ['label' => 'Two-factor', 'type' => 'switch'],
        ]);

        $this->assertMatchesRegularExpression('/<div class="switch">\s*<input\s+type="checkbox"\s+role="switch"\s+switch\s+id="[^"]*is_two_factor_enabled"/', $html);
        $this->assertStringNotContainsString('form-switch', $html);
        $this->assertStringNotContainsString('form-check', $html);
    }

    public function test_option_lists_render_one_form_field_per_option_and_mark_inline_options(): void
    {
        $html = $this->renderForm([
            'role' => [
                'label' => 'Role',
                'type' => 'radio',
                'options' => ['admin' => 'Admin', 'editor' => 'Editor'],
            ],
            'tags' => [
                'label' => 'Tags',
                'type' => 'checkboxlist',
                'inlineOptions' => true,
                'options' => ['red' => 'Red', 'blue' => 'Blue'],
            ],
        ]);

        $this->assertSame(2, preg_match_all('/<input\s+class="radio"\s+id="checkbox_[^"]*role_/', $html));
        $this->assertSame(2, preg_match_all('/<div class="form-field is-inline">\s*<input\s+class="check"\s+type="checkbox"/', $html));
        $this->assertStringNotContainsString('form-check', $html);
    }

    public function test_checkbox_and_switch_filter_scopes_render_as_filter_chips(): void
    {
        $filter = Filter::createIn(new UsersController, [
            'model' => new User,
            'scopes' => [
                'verified' => [
                    'label' => 'Verified',
                    'type' => 'checkbox',
                    'conditions' => 'email_verified_at is not null',
                ],
                'blocked' => [
                    'label' => 'Blocked',
                    'type' => 'switch',
                    'conditions' => ['is_mail_blocked <> true', 'is_mail_blocked = true'],
                ],
            ],
        ]);

        $filter->bindToController();
        $html = (string) $filter->render();

        $this->assertMatchesRegularExpression('/class="filter-chip ">\s*<button[^>]*class="filter-scope scope-checkbox "[^>]*data-scope-name="verified"[^>]*>\s*<i class="ph ph-plus-circle filter-chip-icon"[^>]*><\/i>\s*<span class="filter-label">Verified<\/span>/', $html);
        $this->assertMatchesRegularExpression('/class="filter-scope filter-chip scope-switch "[^>]*data-scope-name="blocked"[^>]*>\s*<button[^>]*class="combobox-toggle"[^>]*data-bs-toggle="combobox"/', $html);
        $this->assertStringNotContainsString('form-check', $html);
    }
}
