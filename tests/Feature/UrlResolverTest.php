<?php

namespace Tests\Feature;

use App\Controllers\UsersController;
use App\Models\User;
use Larajax\Ui\Facades\Ui;
use Larajax\Ui\Widgets\Lists;
use Larajax\Ui\Widgets\Toolbar;
use Tests\TestCase;

class UrlResolverTest extends TestCase
{
    protected function tearDown(): void
    {
        Ui::resolveUrlUsing(null);
        Ui::resolveLinkUsing('page', null);

        parent::tearDown();
    }

    protected function useAdminUrls(): void
    {
        Ui::resolveUrlUsing(fn (string $path) => url('admin/'.ltrim($path, '/')));
    }

    protected function makeList(array $config = []): Lists
    {
        return Lists::createIn(new UsersController, $config + [
            'model' => new User,
            'recordUrl' => 'users/:id/edit',
            'columns' => [
                'website' => ['label' => 'Website', 'type' => 'linkage'],
                'profile' => ['label' => 'Profile', 'type' => 'linkage', 'linkUrl' => 'users/:id'],
            ],
        ]);
    }

    protected function renderLinkage(Lists $list, string $column, User $user): string
    {
        return (string) $list->getColumnValue($user, $list->getColumns()[$column]);
    }

    protected function makeUser(array $attributes = []): User
    {
        $user = (new User)->forceFill($attributes);
        $user->id = 7;

        return $user;
    }

    public function test_without_a_resolver_widget_paths_use_the_app_url(): void
    {
        $list = $this->makeList();

        $this->assertSame(url('users/7/edit'), $list->getRecordUrl($this->makeUser()));
        $this->assertStringContainsString('href="'.url('users/7').'"', $this->renderLinkage($list, 'profile', $this->makeUser()));
        $this->assertSame(url('users/create'), Ui::url('users/create'));
    }

    public function test_the_resolver_builds_record_linkage_and_toolbar_urls(): void
    {
        $this->useAdminUrls();
        $list = $this->makeList();

        $this->assertSame(url('admin/users/7/edit'), $list->getRecordUrl($this->makeUser()));
        $this->assertStringContainsString('href="'.url('admin/users/7').'"', $this->renderLinkage($list, 'profile', $this->makeUser()));

        $toolbar = Toolbar::createIn(new UsersController, [
            'buttons' => ['create' => ['label' => 'New User', 'url' => 'users/create']],
        ]);
        $this->assertStringContainsString('href="'.url('admin/users/create').'"', (string) $toolbar->render());
    }

    public function test_the_resolver_leaves_absolute_linkage_urls_alone(): void
    {
        $this->useAdminUrls();
        $list = $this->makeList(['columns' => [
            'profile' => ['label' => 'Profile', 'type' => 'linkage', 'linkUrl' => 'https://example.com/:id'],
        ]]);

        $this->assertStringContainsString('href="https://example.com/7"', $this->renderLinkage($list, 'profile', $this->makeUser()));
    }

    public function test_links_with_a_registered_scheme_resolve_to_their_address(): void
    {
        Ui::resolveLinkUsing('page', fn (string $link) => $link === 'page://about' ? url('about-us') : null);
        $list = $this->makeList();

        $html = $this->renderLinkage($list, 'website', $this->makeUser(['website' => 'page://about']));
        $this->assertStringContainsString('href="'.url('about-us').'"', $html);
        $this->assertStringContainsString('>/about-us</a>', $html);

        $html = $this->renderLinkage($list, 'website', $this->makeUser(['website' => 'page://missing']));
        $this->assertStringNotContainsString('<a', $html);
    }

    public function test_links_with_an_unregistered_scheme_stay_as_text(): void
    {
        $list = $this->makeList();

        $html = $this->renderLinkage($list, 'website', $this->makeUser(['website' => 'page://about']));

        $this->assertSame('page://about', $html);
    }
}
