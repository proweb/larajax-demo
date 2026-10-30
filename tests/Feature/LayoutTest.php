<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserGroup;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_shell_renders_the_sidebar_top_bar_and_assets(): void
    {
        $this->get('users')
            ->assertOk()
            ->assertSee('<body class="amber-layout">', false)
            ->assertSee('id="amber-sidebar"', false)
            ->assertSee('class="topbar"', false)
            ->assertSee('data-amber-theme-toggle', false)
            ->assertSee('css/amber.css', false)
            ->assertSee('js/amber.js', false);
    }

    public function test_the_colour_mode_is_set_before_the_stylesheets_load(): void
    {
        $html = $this->get('users')->assertOk()->getContent();

        $themeScriptAt = strpos($html, "document.documentElement.setAttribute('data-bs-theme'");
        $stylesheetAt = strpos($html, 'rel="stylesheet"');

        $this->assertNotFalse($themeScriptAt);
        $this->assertNotFalse($stylesheetAt);
        $this->assertLessThan($stylesheetAt, $themeScriptAt);
        $this->assertStringContainsString("localStorage.getItem('amber-theme')", $html);
        $this->assertStringContainsString("matchMedia('(prefers-color-scheme: dark)')", $html);
    }

    public function test_the_current_page_is_marked_in_the_sidebar(): void
    {
        $html = $this->get('users')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/href="[^"]*\/users"\s+aria-current="page"/', $html);
        $this->assertDoesNotMatchRegularExpression('/href="[^"]*\/user-groups"\s+aria-current="page"/', $html);

        $html = $this->get('users/structure')->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/href="[^"]*\/users\/structure"\s+aria-current="page"/', $html);
        $this->assertDoesNotMatchRegularExpression('/href="[^"]*\/users"\s+aria-current="page"/', $html);

        $group = UserGroup::factory()->create(['code' => 'editors']);
        $html = $this->get("user-groups/{$group->id}/edit")->assertOk()->getContent();
        $this->assertMatchesRegularExpression('/href="[^"]*\/user-groups"\s+aria-current="page"/', $html);
    }

    public function test_the_top_bar_search_filters_the_users_list(): void
    {
        User::factory()->create(['name' => 'Zelda Quartermaine']);
        User::factory()->create(['name' => 'Bob Ordinary']);

        $this->get('users?q=Zelda')
            ->assertOk()
            ->assertSee('Zelda Quartermaine')
            ->assertDontSee('Bob Ordinary')
            ->assertSee('value="Zelda"', false);
    }
}
