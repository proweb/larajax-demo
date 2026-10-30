<?php

namespace Tests\Feature;

use App\Controllers\UsersController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Larajax\Ui\Facades\Ui;
use Larajax\Ui\Widgets\Form;
use Larajax\Ui\Widgets\Lists;
use Tests\TestCase;

class TimezoneTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Ui::resolveTimezoneUsing(null);

        parent::tearDown();
    }

    protected function useSydneyTime(): void
    {
        Ui::resolveTimezoneUsing(fn () => 'Australia/Sydney');
    }

    protected function renderDatePicker(array $config): string
    {
        $user = User::factory()->create(['email_verified_at' => '2026-10-20 14:30:00']);

        $form = Form::createIn(new UsersController, [
            'model' => $user,
            'fields' => [
                'email_verified_at' => $config + ['label' => 'Verified', 'type' => 'datepicker'],
            ],
        ]);

        $form->bindToController();

        return (string) $form->render();
    }

    protected function ajax(string $handler, string $url, array $data = [])
    {
        return $this->withHeaders([
            'X-AJAX-HANDLER' => $handler,
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post($url, $data);
    }

    public function test_without_a_resolver_the_user_timezone_is_the_app_timezone(): void
    {
        $this->assertSame('UTC', Ui::timezone());

        $html = $this->renderDatePicker([]);
        $this->assertStringContainsString('value="2026-10-20 14:30:00"', $html);
        $this->assertStringContainsString('data-timezone="UTC"', $html);
    }

    public function test_an_invalid_or_empty_timezone_falls_back_to_the_app_timezone(): void
    {
        Ui::resolveTimezoneUsing(fn () => 'Mars/Olympus');
        $this->assertSame('UTC', Ui::timezone());

        Ui::resolveTimezoneUsing(fn () => null);
        $this->assertSame('UTC', Ui::timezone());

        Ui::resolveTimezoneUsing(fn () => new \DateTimeZone('Europe/Paris'));
        $this->assertSame('Europe/Paris', Ui::timezone());
    }

    public function test_the_datepicker_shows_values_in_the_user_timezone(): void
    {
        $this->useSydneyTime();
        $user = User::factory()->create(['email_verified_at' => '2026-10-20 14:30:00']);

        $this->get("/users/{$user->id}/edit")
            ->assertOk()
            ->assertSee('value="2026-10-21 01:30:00"', false)
            ->assertSee('data-timezone="Australia/Sydney"', false);
    }

    public function test_the_datepicker_saves_values_from_the_user_timezone(): void
    {
        $this->useSydneyTime();
        $user = User::factory()->create(['email_verified_at' => '2026-10-20 14:30:00']);

        $this->ajax('onUpdate', "/users/{$user->id}/edit", [
            'name' => $user->name,
            'email' => $user->email,
            'email_verified_at' => '2026-10-21 09:00:00',
        ])->assertOk();

        $this->assertSame('2026-10-20 22:00:00', $user->refresh()->email_verified_at->toDateTimeString());
    }

    public function test_the_datepicker_preview_uses_the_user_timezone(): void
    {
        $this->useSydneyTime();

        $form = Form::createIn(new UsersController, [
            'model' => User::factory()->create(['email_verified_at' => '2026-10-20 14:30:00']),
            'previewMode' => true,
            'fields' => [
                'email_verified_at' => ['label' => 'Verified', 'type' => 'datepicker', 'format' => 'Y-m-d H:i T'],
            ],
        ]);

        $form->bindToController();

        $this->assertStringContainsString('2026-10-21 01:30 AEDT', (string) $form->render());
    }

    public function test_date_and_time_modes_and_use_timezone_false_do_not_convert(): void
    {
        $this->useSydneyTime();

        foreach ([['mode' => 'date'], ['mode' => 'time'], ['useTimezone' => false]] as $config) {
            $html = $this->renderDatePicker($config);

            $this->assertStringContainsString('value="2026-10-20 14:30:00"', $html);
            $this->assertStringContainsString('data-timezone="UTC"', $html);
            $this->assertStringContainsString('data-ignore-timezone', $html);
        }
    }

    public function test_list_columns_convert_datetime_values_but_not_dates_by_default(): void
    {
        $this->useSydneyTime();
        User::factory()->create(['created_at' => '2026-10-20 14:30:00']);

        $list = Lists::createIn(new UsersController, [
            'model' => new User,
            'columns' => [
                'created_at' => ['label' => 'Created', 'type' => 'datetime', 'format' => 'Y-m-d H:i'],
                'updated_at' => ['label' => 'Updated', 'type' => 'date', 'format' => 'Y-m-d'],
                'email_verified_at' => ['label' => 'Verified', 'type' => 'datetime', 'format' => '[Y-m-d H:i]', 'useTimezone' => false],
            ],
        ]);

        $record = User::first();
        $record->updated_at = '2026-10-20 14:30:00';
        $record->email_verified_at = '2026-10-20 14:30:00';

        $this->assertSame('2026-10-21 01:30', $list->getColumnValue($record, $list->getColumns()['created_at']));
        $this->assertSame('2026-10-20', $list->getColumnValue($record, $list->getColumns()['updated_at']));
        $this->assertSame('[2026-10-20 14:30]', $list->getColumnValue($record, $list->getColumns()['email_verified_at']));
    }

    public function test_the_date_filter_reads_picked_days_in_the_user_timezone(): void
    {
        $this->useSydneyTime();

        // 21 Oct 00:30 and 20 Oct 23:30 in Sydney
        User::factory()->create(['email' => 'early@example.com', 'created_at' => '2026-10-20 13:30:00']);
        User::factory()->create(['email' => 'late@example.com', 'created_at' => '2026-10-20 12:30:00']);

        $resp = $this->ajax('filter::onFilterUpdate', 'users', [
            'scopeName' => 'created_at',
            'Filter' => [
                'condition' => 'equals',
                'value' => '2026-10-21',
                'valueRaw' => '2026-10-21',
            ],
        ]);

        $resp->assertOk();
        $resp->assertSee('early@example.com');
        $resp->assertDontSee('late@example.com');
    }
}
