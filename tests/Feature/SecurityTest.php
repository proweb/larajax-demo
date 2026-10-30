<?php

namespace Tests\Feature;

use App\Controllers\UsersController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Larajax\Ui\Widgets\Form;
use Larajax\Ui\Widgets\Lists;
use October\Rain\Exception\ForbiddenException;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SecurityTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, array{string, bool}>
     */
    public static function linkageUrlProvider(): array
    {
        return [
            'https' => ['https://example.com', true],
            'mailto' => ['mailto:someone@example.com', true],
            'relative path' => ['/users/1/edit', true],
            'javascript' => ['javascript:alert(1)', false],
            'uppercase javascript' => ['JAVASCRIPT:alert(1)', false],
            'javascript with control characters' => ["java\tscript:alert(1)", false],
            'data uri' => ['data:text/html,<script>alert(1)</script>', false],
        ];
    }

    #[DataProvider('linkageUrlProvider')]
    public function test_linkage_columns_only_link_safe_urls(string $url, bool $isLinked): void
    {
        $list = Lists::createIn(new UsersController, [
            'model' => new User,
            'columns' => [
                'website' => ['label' => 'Website', 'type' => 'linkage'],
            ],
        ]);

        $user = new User;
        $user->website = $url;

        $html = (string) $list->getColumnValue($user, $list->getColumns()['website']);

        if ($isLinked) {
            $this->assertStringContainsString('href="'.e($url).'"', $html);
        }
        else {
            $this->assertStringNotContainsString('<a', $html);
            $this->assertSame(e($url), $html);
        }
    }

    public function test_repeater_rejects_adding_items_in_preview_mode(): void
    {
        $form = Form::createIn(new UsersController, [
            'model' => User::factory()->create(),
            'previewMode' => true,
            'fields' => [
                'links' => [
                    'type' => 'repeater',
                    'form' => ['fields' => ['title' => ['label' => 'Title']]],
                ],
            ],
        ]);

        $form->bindToController();

        $this->expectException(ForbiddenException::class);

        $form->getFormWidget('links')->onAddItem();
    }
}
