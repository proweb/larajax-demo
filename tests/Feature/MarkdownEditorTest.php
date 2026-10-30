<?php

namespace Tests\Feature;

use App\Controllers\UserGroupsController;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Larajax\Ui\Widgets\Form;
use Tests\TestCase;

class MarkdownEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function makeMarkdownForm(UserGroup $group, array $config = [], array $formConfig = []): Form
    {
        $form = Form::createIn(new UserGroupsController, $formConfig + [
            'model' => $group,
            'fields' => [
                'description' => $config + ['label' => 'Description', 'type' => 'markdown'],
            ],
        ]);

        $form->bindToController();

        return $form;
    }

    protected function postRequest(array $data): void
    {
        $this->app->instance('request', Request::create('/user-groups', 'POST', $data));
        Facade::clearResolvedInstance('request');
    }

    public function test_the_field_renders_the_control_over_the_textarea(): void
    {
        $html = (string) $this->makeMarkdownForm(UserGroup::factory()->create(['description' => '**Bold** & <b>tag</b>']), ['placeholder' => 'Describe the group'])->render();

        $this->assertStringContainsString('data-control="markdowneditor"', $html);
        $this->assertStringContainsString('class="field-markdowneditor size-large ', $html);
        $this->assertStringContainsString('data-view-mode="tab"', $html);
        $this->assertStringContainsString('data-safe-mode="true"', $html);
        $this->assertStringContainsString('placeholder="Describe the group"', $html);
        $this->assertStringContainsString('data-size="large"', $html);
        $this->assertStringContainsString('data-toolbar-buttons="[{&quot;name&quot;:&quot;bold&quot;,&quot;title&quot;:&quot;Bold&quot;', $html);
        $this->assertStringContainsString('&lt;i class=\&quot;ph ph-text-b\&quot;', $html);
        $this->assertStringContainsString('&quot;name&quot;:&quot;code&quot;', $html);
        $this->assertStringContainsString('&quot;|&quot;', $html);
        $this->assertStringContainsString('>**Bold** &amp; &lt;b&gt;tag&lt;/b&gt;</textarea>', $html);
    }

    public function test_side_by_side_starts_in_split_mode(): void
    {
        $group = UserGroup::factory()->create();

        $sideBySide = (string) $this->makeMarkdownForm($group, ['sideBySide' => true])->render();
        $this->assertStringContainsString('data-view-mode="split"', $sideBySide);

        $mode = (string) $this->makeMarkdownForm($group, ['mode' => 'split'])->render();
        $this->assertStringContainsString('data-view-mode="split"', $mode);
    }

    public function test_read_only_fields_render_the_markdown_and_keep_the_value(): void
    {
        $group = UserGroup::factory()->create(['description' => '# Title']);

        $html = (string) $this->makeMarkdownForm($group, ['readOnly' => true])->render();

        $this->assertStringNotContainsString('data-control="markdowneditor"', $html);
        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringContainsString('<textarea name="description"', $html);
        $this->assertStringContainsString(' hidden># Title</textarea>', $html);
    }

    public function test_disabled_fields_render_the_markdown_without_a_value(): void
    {
        $group = UserGroup::factory()->create(['description' => '# Title']);

        $html = (string) $this->makeMarkdownForm($group, ['disabled' => true])->render();

        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringNotContainsString('<textarea', $html);
    }

    public function test_preview_mode_renders_the_markdown(): void
    {
        $group = UserGroup::factory()->create(['description' => "# Title\n\n<script>alert(1)</script>"]);

        $html = (string) $this->makeMarkdownForm($group, [], ['previewMode' => true])->render();

        $this->assertStringNotContainsString('data-control="markdowneditor"', $html);
        $this->assertStringContainsString('<h1>Title</h1>', $html);
        $this->assertStringNotContainsString('<script>', $html);
    }

    public function test_the_refresh_handler_renders_the_posted_markdown_cleaned(): void
    {
        $widget = $this->makeMarkdownForm(UserGroup::factory()->create())->getFormWidget('description');

        $this->postRequest(['description' => "# Title\n\n**Bold**<script>alert(1)</script>"]);
        $preview = $widget->onRefresh()['preview'];

        $this->assertStringContainsString('<h1>Title</h1>', $preview);
        $this->assertStringContainsString('<strong>Bold</strong>', $preview);
        $this->assertStringNotContainsString('<script>', $preview);
    }

    public function test_the_refresh_handler_keeps_html_without_safe_mode(): void
    {
        $widget = $this->makeMarkdownForm(UserGroup::factory()->create(), ['safeMode' => false])->getFormWidget('description');

        $this->postRequest(['description' => '<span class="note">Note</span>']);

        $this->assertStringContainsString('<span class="note">Note</span>', $widget->onRefresh()['preview']);
    }

    public function test_the_markdown_is_saved_as_submitted(): void
    {
        $widget = $this->makeMarkdownForm(UserGroup::factory()->create())->getFormWidget('description');

        $this->assertSame("# Title\n\n* One", $widget->getSaveValue("# Title\n\n* One"));
    }
}
