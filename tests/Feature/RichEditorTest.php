<?php

namespace Tests\Feature;

use App\Controllers\UsersController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Larajax\Ui\Widgets\Form;
use October\Rain\Exception\ForbiddenException;
use Tests\TestCase;

class RichEditorTest extends TestCase
{
    use RefreshDatabase;

    protected function makeEditorForm(User $user, array $config = [], array $formConfig = []): Form
    {
        $form = Form::createIn(new UsersController, $formConfig + [
            'model' => $user,
            'fields' => [
                'notes' => $config + ['label' => 'Notes', 'type' => 'richeditor'],
            ],
        ]);

        $form->bindToController();

        return $form;
    }

    public function test_the_field_renders_the_control_over_a_hidden_textarea(): void
    {
        $html = (string) $this->makeEditorForm(User::factory()->create(['notes' => '<p>Hello & welcome</p>']), ['placeholder' => 'Write a note'])->render();

        $this->assertStringContainsString('data-control="richeditor"', $html);
        $this->assertStringContainsString('class="field-richeditor size-large ', $html);
        $this->assertMatchesRegularExpression('/<textarea\s+data-richeditor-textarea\s+placeholder="Write a note"\s+name="notes"/', $html);
        $this->assertStringContainsString('&lt;p&gt;Hello &amp; welcome&lt;/p&gt;</textarea>', $html);
        $this->assertStringNotContainsString('data-toolbar-buttons', $html);
        $this->assertStringNotContainsString('data-read-only', $html);
    }

    public function test_toolbar_buttons_accept_a_list_or_a_pipe_separated_string(): void
    {
        $user = User::factory()->create();

        $list = (string) $this->makeEditorForm($user, ['toolbarButtons' => ['bold', 'italic', '|', 'html']])->render();
        $this->assertStringContainsString('data-toolbar-buttons="bold,italic,|,html"', $list);

        $string = (string) $this->makeEditorForm($user, ['toolbarButtons' => 'bold|italic||html'])->render();
        $this->assertStringContainsString('data-toolbar-buttons="bold,italic,|,html"', $string);
    }

    public function test_options_render_as_control_attributes(): void
    {
        $html = (string) $this->makeEditorForm(User::factory()->create(), [
            'useLineBreaks' => true,
            'size' => 'huge',
            'editorOptions' => ['imageResize' => false, 'paragraphStyles' => ['lead' => 'Lead']],
        ])->render();

        $this->assertStringContainsString('class="field-richeditor size-huge ', $html);
        $this->assertStringContainsString('data-use-line-breaks="true"', $html);
        $this->assertStringContainsString('data-editor-options="{&quot;imageResize&quot;:false,&quot;paragraphStyles&quot;:{&quot;lead&quot;:&quot;Lead&quot;}}"', $html);
    }

    public function test_disabled_and_read_only_fields_render_a_read_only_editor(): void
    {
        $user = User::factory()->create();

        $readOnly = (string) $this->makeEditorForm($user, ['readOnly' => true])->render();
        $this->assertStringContainsString('data-read-only="true"', $readOnly);

        $disabled = (string) $this->makeEditorForm($user, ['disabled' => true])->render();
        $this->assertStringContainsString('data-read-only="true"', $disabled);
    }

    public function test_preview_mode_renders_the_cleaned_html(): void
    {
        $user = User::factory()->create(['notes' => '<p>Safe</p><script>alert(1)</script>']);

        $safe = (string) $this->makeEditorForm($user, [], ['previewMode' => true])->render();
        $this->assertStringNotContainsString('data-control="richeditor"', $safe);
        $this->assertStringContainsString('<p>Safe</p>', $safe);
        $this->assertStringNotContainsString('<script>', $safe);

        $raw = (string) $this->makeEditorForm($user, ['safeMode' => false], ['previewMode' => true])->render();
        $this->assertStringContainsString('<script>alert(1)</script>', $raw);
    }

    public function test_the_html_is_saved_as_submitted(): void
    {
        $widget = $this->makeEditorForm(User::factory()->create())->getFormWidget('notes');

        $this->assertSame('<p>Hello <strong>there</strong></p>', $widget->getSaveValue('<p>Hello <strong>there</strong></p>'));
    }

    public function test_the_field_passes_icon_pack_classes_and_translated_strings(): void
    {
        $html = (string) $this->makeEditorForm(User::factory()->create())->render();

        $this->assertStringContainsString('data-icons="{&quot;bold&quot;:&quot;ph ph-text-b&quot;,', $html);
        $this->assertStringContainsString('&quot;snippet&quot;:&quot;ph ph-newspaper&quot;}"', $html);
        $this->assertStringContainsString('data-lang="{&quot;toolbar&quot;:&quot;Editor toolbar&quot;,', $html);
        $this->assertStringContainsString('&quot;formatHeading1&quot;:&quot;Heading 1&quot;', $html);
    }

    public function test_the_built_in_upload_is_offered_only_with_an_upload_disk(): void
    {
        $user = User::factory()->create();

        $this->assertStringNotContainsString('data-upload-handler', (string) $this->makeEditorForm($user)->render());

        $enabled = (string) $this->makeEditorForm($user, ['uploadDisk' => 'public'])->render();
        $this->assertStringContainsString('data-upload-handler="formNotes::onUploadFile"', $enabled);

        $readOnly = (string) $this->makeEditorForm($user, ['uploadDisk' => 'public', 'readOnly' => true])->render();
        $this->assertStringNotContainsString('data-upload-handler', $readOnly);
    }

    public function test_the_upload_handler_refuses_without_an_upload_disk(): void
    {
        $widget = $this->makeEditorForm(User::factory()->create())->getFormWidget('notes');

        $this->expectException(ForbiddenException::class);
        $widget->onUploadFile();
    }

    /**
     * uploadToNotes posts a file to the demo notes field, which stores uploads on the public disk.
     */
    protected function uploadToNotes(UploadedFile $file, string $type)
    {
        $user = User::factory()->create();

        return $this->withHeaders([
            'X-AJAX-HANDLER' => 'formNotes::onUploadFile',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post("/users/{$user->id}/edit", [
            'name' => $user->name,
            'file_data' => $file,
            'file_type' => $type,
        ]);
    }

    public function test_uploaded_images_are_stored_and_their_address_returned(): void
    {
        Storage::fake('public');

        $response = $this->uploadToNotes(UploadedFile::fake()->image('photo.png', 20, 20), 'image');
        $this->assertSame(200, $response->status(), $response->getContent());

        $url = $response->json('url');
        $this->assertMatchesRegularExpression('#^/storage/uploads/richeditor/\w{40}\.png$#', $url);
        Storage::disk('public')->assertExists(Str::after($url, '/storage/'));
    }

    public function test_file_links_accept_documents(): void
    {
        Storage::fake('public');

        $response = $this->uploadToNotes(UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'), 'file');
        $this->assertSame(200, $response->status(), $response->getContent());

        $this->assertMatchesRegularExpression('#^/storage/uploads/richeditor/\w{40}\.pdf$#', $response->json('url'));
        $this->assertCount(1, Storage::disk('public')->allFiles('uploads/richeditor'));
    }

    public function test_images_refuse_documents(): void
    {
        Storage::fake('public');

        $response = $this->uploadToNotes(UploadedFile::fake()->create('notes.pdf', 10, 'application/pdf'), 'image')->assertStatus(400);

        $this->assertStringContainsString('jpg, jpeg, png, gif, webp.', $response->json('__ajax.message'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_uploads_refuse_active_content(): void
    {
        Storage::fake('public');

        $response = $this->uploadToNotes(UploadedFile::fake()->createWithContent('logo.svg', '<svg xmlns="http://www.w3.org/2000/svg"><script>alert(1)</script></svg>'), 'file')
            ->assertStatus(400)
            ->assertJsonPath('__ajax.ok', false);

        $this->assertStringContainsString('must have one of the following extensions', $response->json('__ajax.message'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }

    public function test_uploads_check_the_file_content_as_well_as_its_name(): void
    {
        Storage::fake('public');

        $path = tempnam(sys_get_temp_dir(), 'richeditor');
        file_put_contents($path, '<!DOCTYPE html><html><body><script>alert(1)</script></body></html>');

        try {
            $response = $this->uploadToNotes(new UploadedFile($path, 'report.pdf', 'application/pdf', null, true), 'file')->assertStatus(400);
        }
        finally {
            @unlink($path);
        }

        $this->assertStringContainsString('must be a file of type', $response->json('__ajax.message'));
        $this->assertSame([], Storage::disk('public')->allFiles());
    }
}
