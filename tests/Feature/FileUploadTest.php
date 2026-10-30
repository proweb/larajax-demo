<?php

namespace Tests\Feature;

use App\Models\File;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class FileUploadTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        // Rain removes the stored files with their records, which the rollback alone would leave behind
        File::all()->each->delete();

        parent::tearDown();
    }

    protected function ajax(string $handler, UserGroup $group, array $data = [])
    {
        return $this->withHeaders([
            'X-AJAX-HANDLER' => $handler,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post("/user-groups/{$group->id}/edit", $data + [
            'name' => $group->name,
            'code' => $group->code,
        ]);
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
        $this->assertFileExists($logo->getLocalPath());
    }

    public function test_uploads_of_other_file_types_are_refused(): void
    {
        $group = UserGroup::factory()->create(['code' => 'editors']);

        $this->ajax('formLogo::onUpload', $group, [
            'file_data' => UploadedFile::fake()->create('notes.txt', 1, 'text/plain'),
        ])->assertStatus(400);

        $this->assertNull($group->fresh()->logo);
    }

    /**
     * attachDocuments stores files on the group directly, since a test reuses its first
     * AJAX handler for later requests to the same route.
     */
    protected function attachDocuments(UserGroup $group, array $names): array
    {
        return array_map(function ($name) use ($group) {
            $file = new File;
            $file->data = UploadedFile::fake()->create($name, 1);
            $file->save();
            $group->documents()->add($file);

            return $file->id;
        }, $names);
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

        $this->assertSame(['notes.txt'], $group->documents()->withDeferred('session-1')->pluck('file_name')->all());
        $this->assertSame(['agenda.txt', 'notes.txt'], $group->fresh()->documents->pluck('file_name')->all());
    }

    public function test_saving_the_form_commits_a_removed_document(): void
    {
        $group = UserGroup::factory()->create(['code' => 'editors']);
        [$first] = $this->attachDocuments($group, ['agenda.txt', 'notes.txt']);
        $group->documents()->remove(File::find($first), 'session-1');

        $this->ajax('onUpdate', $group, ['_session_key' => 'session-1'])->assertOk();

        $this->assertSame(['notes.txt'], $group->fresh()->documents->pluck('file_name')->all());
    }
}
