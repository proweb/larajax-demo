<?php

namespace Tests\Feature;

use App\Controllers\UsersController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Larajax\Ui\Widgets\Filter;
use Tests\TestCase;

class FilterTest extends TestCase
{
    use RefreshDatabase;

    /**
     * renderDateScopeForm renders the popover form of a date scope with the given configuration.
     */
    protected function renderDateScopeForm(array $config): string
    {
        $filter = Filter::createIn(new UsersController, [
            'model' => new User,
            'scopes' => [
                'created_at' => $config + ['label' => 'Created', 'type' => 'date'],
            ],
        ]);

        $filter->bindToController();

        return (string) $filter->renderScopeFormElement('created_at');
    }

    public function test_date_filter_pickers_take_the_locale_first_day_unless_one_is_configured(): void
    {
        $default = $this->renderDateScopeForm([]);
        $this->assertSame(3, substr_count($default, 'data-control="datepicker"'));
        $this->assertStringNotContainsString('data-first-day', $default);

        $this->assertSame(3, substr_count($this->renderDateScopeForm(['firstDay' => 1]), 'data-first-day="1"'));
        $this->assertSame(3, substr_count($this->renderDateScopeForm(['firstDay' => 0]), 'data-first-day="0"'));
    }

    public function test_the_date_filter_form_posts_the_raw_date_inputs(): void
    {
        $resp = $this->withHeaders([
            'X-AJAX-HANDLER' => 'filter::onLoadFilterForm',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post('users', [
            'scopeName' => 'created_at',
        ]);

        $resp->assertOk();

        // The date widget only applies when the raw inputs are posted
        $content = $resp->getContent();
        $this->assertStringContainsString('Filter[valueRaw]', $content);
        $this->assertStringContainsString('Filter[afterRaw]', $content);
        $this->assertStringContainsString('Filter[beforeRaw]', $content);
    }

    public function test_the_date_filter_applies_an_after_condition(): void
    {
        User::factory()->create(['email' => 'old@example.com', 'created_at' => '2020-01-01 10:00:00']);
        User::factory()->create(['email' => 'new@example.com', 'created_at' => '2025-06-01 10:00:00']);

        $resp = $this->withHeaders([
            'X-AJAX-HANDLER' => 'filter::onFilterUpdate',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post('users', [
            'scopeName' => 'created_at',
            'Filter' => [
                'condition' => 'after',
                'value' => '2024-01-01',
                'valueRaw' => '2024-01-01',
            ],
        ]);

        $resp->assertOk();
        $resp->assertSee('new@example.com');
        $resp->assertDontSee('old@example.com');
    }

    public function test_a_dependent_scope_resets_and_refreshes_when_its_dependency_changes(): void
    {
        User::factory()->create(['name' => 'Alice', 'email' => 'alice@example.com']);
        User::factory()->create(['name' => 'Bob', 'email' => 'bob@example.com']);
        User::factory()->create(['name' => 'Carol', 'email' => 'carol@example.org']);

        // The name scope depends on the domain scope and starts filtered to Alice
        $resp = $this->withSession([
            'widget.app-UsersController-Filter' => json_encode([
                'scope-name' => ['value' => ['Alice' => 'Alice']],
            ]),
        ])->withHeaders([
            'X-AJAX-HANDLER' => 'filter::onFilterUpdate',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post('users', [
            'scopeName' => 'domain',
            'value' => 'example.com',
        ]);

        $resp->assertOk();

        // The name scope is rendered again without its value
        $patch = collect($resp->json('__ajax.ops'))->firstWhere('selector', '#Filter-scope-name-group');
        $this->assertNotNull($patch);
        $this->assertStringContainsString('data-scope-name="name"', $patch['html']);
        $this->assertStringNotContainsString('filter-setting', $patch['html']);

        // The list drops the name filter and keeps the domain filter
        $resp->assertSee('alice@example.com');
        $resp->assertSee('bob@example.com');
        $resp->assertDontSee('carol@example.org');

        $store = json_decode(session('widget.app-UsersController-Filter'), true);
        $this->assertSame(['value' => 'example.com'], $store['scope-domain']);
        $this->assertNull($store['scope-name']);
    }

    public function test_preloading_returns_the_popover_form_of_each_widget_scope(): void
    {
        $resp = $this->withHeaders([
            'X-AJAX-HANDLER' => 'filter::onFilterUpdate',
            'X-Requested-With' => 'XMLHttpRequest',
        ])->post('users', [
            'preload' => 'true',
        ]);

        $resp->assertOk();

        $content = $resp->json('popoverContent');
        $this->assertEqualsCanonicalizing(['id', 'email', 'created_at', 'name'], array_keys($content));

        // The number form carries the condition triggers the popover binds
        $this->assertStringContainsString('data-trigger="[name=\'Filter[condition]\']"', $content['id']);
        $this->assertStringContainsString('data-filter-action="apply"', $content['email']);
        $this->assertStringContainsString('data-control="groupfilter"', $content['name']);
    }
}
