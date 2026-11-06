<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Larajax\Ui\Classes\HeldUploads;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    /**
     * setUp stores the uploads on a fake public disk.
     */
    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    protected function tearDown(): void
    {
        // The File model removes the stored files with their records, which the rollback alone would leave behind
        File::all()->each->delete();

        parent::tearDown();
    }

    /**
     * ajax posts one AJAX handler request to the group's edit page.
     */
    protected function ajax(string $handler, UserGroup $group, array $data = []): TestResponse
    {
        // Laravel keeps the route's controller across a test's requests, and the controller keeps its first AJAX handler
        app('router')->getRoutes()->getByName('user-groups.edit')->flushController();

        // Upload fields register with the request's held uploads, which a test's requests would otherwise share
        app()->forgetInstance(HeldUploads::class);

        return $this->withHeaders([
            'X-AJAX-HANDLER' => $handler,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post("/user-groups/{$group->id}/edit", $data + [
            'name' => $group->name,
            'code' => $group->code,
        ]);
    }

    /**
     * attachDocuments stores files on the group directly, since a test reuses its first
     * AJAX handler for later requests to the same route.
     */
    protected function attachDocuments(UserGroup $group, array $names): array
    {
        return array_map(function ($name) use ($group) {
            $file = $group->documents()->make()->fromPost(UploadedFile::fake()->create($name, 1));
            $file->save();

            return $file->id;
        }, $names);
    }

    public function test_the_group_form_renders_a_single_logo_and_multiple_documents(): void
    {
        $group = UserGroup::factory()->create(['code' => 'editors']);

        $html = $this->get("/user-groups/{$group->id}/edit")->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/class="field-fileupload is-image is-single\s[^"]*"\s+data-control="fileupload"/', $html);
        $this->assertMatchesRegularExpression('/class="field-fileupload size-large is-file is-multi is-sortable[^"]*"\s+data-control="fileupload"/', $html);
        $this->assertStringContainsString('data-file-types=".jpg,.jpeg,.png,.gif,.webp"', $html);
        $this->assertStringContainsString('data-messages="{&quot;maxFiles&quot;', $html);
    }

    public function test_the_create_form_leaves_the_upload_fields_out(): void
    {
        $html = $this->get('/user-groups/create')->assertOk()->getContent();

        $this->assertStringNotContainsString('data-control="fileupload"', $html);
    }

    public function test_uploading_attaches_the_file_straight_away(): void
    {
        $group = UserGroup::factory()->create(['code' => 'editors']);

        $response = $this->ajax('formLogo::onUpload', $group, [
            'file_data' => UploadedFile::fake()->image('logo.png', 200, 100),
        ]);
        $this->assertSame(200, $response->status(), $response->getContent());

        $logo = $group->fresh()->logo;
        $this->assertNotNull($logo);
        $this->assertSame('logo.png', $logo->file_name);
        $this->assertSame($logo->id, $response->json('id'));
        $this->assertStringContainsString('/storage/uploads/public/', $response->json('path'));
        Storage::disk('public')->assertExists($logo->getDiskPath());
    }

    public function test_uploads_of_other_file_types_are_refused(): void
    {
        $group = UserGroup::factory()->create(['code' => 'editors']);

        $this->ajax('formLogo::onUpload', $group, [
            'file_data' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain'),
        ])->assertStatus(400);

        $this->assertNull($group->fresh()->logo);
    }

    public function test_sorting_orders_the_documents(): void
    {
        $group = UserGroup::factory()->create(['code' => 'editors']);
        [$first, $second, $third] = $this->attachDocuments($group, ['agenda.txt', 'notes.txt', 'minutes.txt']);

        $sorted = $this->ajax('formDocuments::onSortAttachments', $group, [
            'sortOrder' => [$third => 1, $first => 2, $second => 3],
        ]);
        $this->assertSame(200, $sorted->status(), $sorted->getContent());

        $this->assertSame(['minutes.txt', 'agenda.txt', 'notes.txt'], $group->fresh()->documents->pluck('file_name')->all());
    }

    public function test_removing_a_document_waits_for_the_form_to_save(): void
    {
        $group = UserGroup::factory()->create(['code' => 'editors']);
        [$first] = $this->attachDocuments($group, ['agenda.txt', 'notes.txt']);

        $this->ajax('formDocuments::onRemoveAttachment', $group, ['file_id' => $first, '_session_key' => 'session-1'])->assertOk();

        // The removal is held in the session, so the file stays attached until the record saves
        $this->assertSame(['agenda.txt', 'notes.txt'], $group->fresh()->documents->pluck('file_name')->all());
        $this->assertNotNull(File::find($first));
    }

    public function test_saving_the_form_commits_a_removed_document(): void
    {
        $group = UserGroup::factory()->create(['code' => 'editors']);
        [$first] = $this->attachDocuments($group, ['agenda.txt', 'notes.txt']);
        $agenda = File::find($first);

        $this->ajax('formDocuments::onRemoveAttachment', $group, ['file_id' => $first, '_session_key' => 'session-1'])->assertOk();
        $this->ajax('onUpdate', $group, ['_session_key' => 'session-1'])->assertOk();

        $this->assertSame(['notes.txt'], $group->fresh()->documents->pluck('file_name')->all());
        $this->assertNull(File::find($first));
        Storage::disk('public')->assertMissing($agenda->getDiskPath());
    }
}
