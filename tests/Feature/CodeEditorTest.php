<?php

namespace Tests\Feature;

use App\Controllers\UserGroupsController;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Larajax\Ui\FormWidgets\CodeEditor;
use Larajax\Ui\Widgets\Form;
use Tests\TestCase;

class CodeEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function makeCodeForm(UserGroup $group, array $config = [], array $formConfig = []): Form
    {
        $form = Form::createIn(new UserGroupsController, $formConfig + [
            'model' => $group,
            'fields' => [
                'description' => $config + ['label' => 'Description', 'type' => 'codeeditor'],
            ],
        ]);

        $form->bindToController();

        return $form;
    }

    public function test_the_field_renders_the_control_toolbar_and_textarea(): void
    {
        $html = (string) $this->makeCodeForm(UserGroup::factory()->create(['description' => '<?php echo "a & b";']))->render();

        $this->assertStringContainsString('data-control="codeeditor"', $html);
        $this->assertStringContainsString('class="field-codeeditor size-large ', $html);
        $this->assertStringContainsString('data-language="php"', $html);
        $this->assertStringContainsString('data-word-wrap="fluid"', $html);
        $this->assertStringContainsString('data-theme=""', $html);
        $this->assertStringContainsString('data-read-only="false"', $html);
        foreach (['undo', 'redo', 'search', 'replace', 'format', 'wrap', 'fullscreen'] as $command) {
            $this->assertStringContainsString('data-editor-command="'.$command.'"', $html);
        }
        $this->assertStringContainsString('title="Find (Ctrl+F)"', $html);
        $this->assertStringContainsString('editor-toolbar-icon-exit', $html);
        $this->assertMatchesRegularExpression('/data-editor-language>\s*php\s*<\/span>/', $html);
        $this->assertStringContainsString('&lt;?php echo &quot;a &amp; b&quot;;</textarea>', $html);
        $this->assertStringNotContainsString('data-vendor-path', $html);
    }

    public function test_options_render_as_control_attributes(): void
    {
        $html = (string) $this->makeCodeForm(UserGroup::factory()->create(), [
            'language' => 'markdown',
            'theme' => 'vs-dark',
            'fontSize' => 14,
            'tabSize' => 2,
            'showGutter' => false,
            'autocompletion' => 'live',
            'showPrintMargin' => true,
            'size' => 'giant',
        ])->render();

        $this->assertStringContainsString('class="field-codeeditor size-giant ', $html);
        $this->assertStringContainsString('data-language="markdown"', $html);
        $this->assertStringContainsString('data-theme="vs-dark"', $html);
        $this->assertStringContainsString('data-font-size="14"', $html);
        $this->assertStringContainsString('data-tab-size="2"', $html);
        $this->assertStringContainsString('data-show-gutter="false"', $html);
        $this->assertStringContainsString('data-autocompletion="live"', $html);
        $this->assertStringContainsString('data-show-print-margin="true"', $html);
    }

    public function test_boolean_word_wrap_values_are_normalized(): void
    {
        $group = UserGroup::factory()->create();

        $on = (string) $this->makeCodeForm($group, ['wordWrap' => true])->render();
        $this->assertStringContainsString('data-word-wrap="fluid"', $on);

        $off = (string) $this->makeCodeForm($group, ['wordWrap' => false])->render();
        $this->assertStringContainsString('data-word-wrap="off"', $off);
    }

    public function test_disabled_and_read_only_fields_render_a_read_only_editor(): void
    {
        $group = UserGroup::factory()->create();

        $readOnly = (string) $this->makeCodeForm($group, ['readOnly' => true])->render();
        $this->assertStringContainsString('data-read-only="true"', $readOnly);

        $disabled = (string) $this->makeCodeForm($group, ['disabled' => true])->render();
        $this->assertStringContainsString('data-read-only="true"', $disabled);
    }

    public function test_preview_mode_renders_the_escaped_code(): void
    {
        $group = UserGroup::factory()->create(['description' => '<script>alert(1)</script>']);

        $html = (string) $this->makeCodeForm($group, [], ['previewMode' => true])->render();

        $this->assertStringNotContainsString('data-control="codeeditor"', $html);
        $this->assertStringContainsString('<pre>&lt;script&gt;alert(1)&lt;/script&gt;</pre>', $html);
    }

    public function test_editor_preferences_apply_before_the_field_config(): void
    {
        $manager = app('system.widgets');
        $manager->listFormWidgets();
        $manager->registerFormWidget(PreferencedCodeEditor::class, 'preferencedcodeeditor');

        $group = UserGroup::factory()->create();

        $preferred = (string) $this->makeCodeForm($group, ['type' => 'preferencedcodeeditor'])->render();
        $this->assertStringContainsString('data-font-size="16"', $preferred);
        $this->assertStringContainsString('data-theme="hc-black"', $preferred);

        $configured = (string) $this->makeCodeForm($group, ['type' => 'preferencedcodeeditor', 'fontSize' => 11])->render();
        $this->assertStringContainsString('data-font-size="11"', $configured);
    }

    public function test_the_code_is_saved_as_submitted(): void
    {
        $widget = $this->makeCodeForm(UserGroup::factory()->create())->getFormWidget('description');

        $this->assertSame("# Title\n\n<b>bold</b>", $widget->getSaveValue("# Title\n\n<b>bold</b>"));
    }
}

/**
 * PreferencedCodeEditor stands in for an editor that reads its defaults from user preferences.
 */
class PreferencedCodeEditor extends CodeEditor
{
    public function init()
    {
        $this->addViewPathFrom(CodeEditor::class);

        parent::init();
    }

    protected function applyEditorPreferences()
    {
        $this->fontSize = 16;
        $this->theme = 'hc-black';
    }
}
