<?php

namespace Tests\Feature;

use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PopupFormTest extends TestCase
{
    use RefreshDatabase;

    protected function ajax(string $handler, array $data = [])
    {
        return $this->withHeaders([
            'X-AJAX-HANDLER' => $handler,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post('/user-groups', $data);
    }

    public function test_list_rows_open_the_record_in_a_popup(): void
    {
        $group = UserGroup::factory()->create();

        $this->get('/user-groups')
            ->assertOk()
            ->assertSee("onclick=\"jax.listOnLoadForm(&#039;{$group->id}&#039;)\"", false);
    }

    public function test_on_load_popup_form_renders_the_record_form(): void
    {
        $group = UserGroup::factory()->create(['name' => 'Editors']);

        $resp = $this->ajax('onLoadPopupForm', ['form_record_id' => $group->id]);

        $resp->assertOk();
        $content = $resp->getContent();
        $this->assertStringContainsString('Edit Group', $content);
        $this->assertStringContainsString('onSavePopupForm', $content);
        $this->assertStringContainsString('Editors', $content);
    }

    public function test_on_save_popup_form_saves_the_record_and_refreshes_the_list(): void
    {
        $group = UserGroup::factory()->create(['name' => 'Editors', 'code' => 'editors']);

        $resp = $this->ajax('onSavePopupForm', [
            'form_record_id' => $group->id,
            'name' => 'Senior Editors',
            'code' => 'editors',
        ]);

        $resp->assertOk();
        $this->assertSame('Senior Editors', $group->refresh()->name);
        $this->assertStringContainsString('Senior Editors', $resp->getContent());
    }
}
