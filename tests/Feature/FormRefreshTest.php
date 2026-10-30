<?php

namespace Tests\Feature;

use App\Controllers\UsersController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Facade;
use Larajax\Ui\Widgets\Form;
use Tests\TestCase;

class FormRefreshTest extends TestCase
{
    use RefreshDatabase;

    protected function ajax(string $handler, User $user, array $data = [])
    {
        return $this->withHeaders([
            'X-AJAX-HANDLER' => $handler,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post("/users/{$user->id}/edit", $data + [
            'name' => $user->name,
            'email' => $user->email,
        ]);
    }

    public function test_the_form_renders_the_form_widget_control_with_its_refresh_handler(): void
    {
        $user = User::factory()->create();

        $this->get("/users/{$user->id}/edit")
            ->assertOk()
            ->assertSee('data-control="formwidget"', false)
            ->assertSee('data-refresh-handler="form::onRefresh"', false);
    }

    public function test_on_refresh_renders_the_requested_fields_with_the_posted_values(): void
    {
        $user = User::factory()->create(['notes' => 'Saved note']);

        $resp = $this->ajax('form::onRefresh', $user, ['fields' => ['notes'], 'notes' => 'Draft note']);

        $resp->assertOk();
        $ops = $resp->json('__ajax.ops');
        $this->assertCount(1, $ops);
        $this->assertStringEndsWith('notes-group', $ops[0]['selector']);
        $this->assertStringContainsString('Draft note', $ops[0]['html']);
        $this->assertSame('Saved note', $user->refresh()->notes);
    }

    public function test_on_refresh_without_fields_renders_the_whole_form(): void
    {
        $user = User::factory()->create();

        $resp = $this->ajax('form::onRefresh', $user);

        $resp->assertOk();
        $html = $resp->json('__ajax.ops.0.html');
        $this->assertStringContainsString('name="email"', $html);
        $this->assertStringContainsString('name="notes"', $html);
    }

    public function test_on_lazy_load_tab_renders_the_fields_of_a_lazy_tab(): void
    {
        $form = Form::createIn(new UsersController, [
            'model' => User::factory()->create(['notes' => 'Lazy note']),
            'tabs' => [
                'lazy' => ['Notes'],
                'fields' => [
                    'name' => ['label' => 'Name', 'tab' => 'Account'],
                    'notes' => ['label' => 'Notes', 'type' => 'textarea', 'tab' => 'Notes'],
                ],
            ],
        ]);

        $form->bindToController();
        $html = (string) $form->render();

        $this->assertStringContainsString('data-tab-lazy-handler="'.$form->getEventHandler('onLazyLoadTab').'"', $html);
        $this->assertStringNotContainsString('Lazy note', $html);

        $this->app->instance('request', Request::create('/users', 'POST', [
            'target' => '#lazy-pane',
            'name' => 'Notes',
            'section' => 'primary',
        ]));
        Facade::clearResolvedInstance('request');

        $result = $form->onLazyLoadTab();

        $this->assertArrayHasKey('#lazy-pane', $result);
        $this->assertStringContainsString('Lazy note', (string) $result['#lazy-pane']);
    }
}
