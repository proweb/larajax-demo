@php
    $navItems = [
        [
            'label' => 'Users',
            'icon' => 'ph-users',
            'href' => url('users'),
            'active' => request()->is('users', 'users/*') && ! request()->is('users/structure', 'users/create'),
            'children' => [
                [
                    'label' => 'Structure',
                    'icon' => 'ph-tree-structure',
                    'href' => url('users/structure'),
                    'active' => request()->is('users/structure'),
                ],
                [
                    'label' => 'New User',
                    'icon' => 'ph-user-plus',
                    'href' => url('users/create'),
                    'active' => request()->is('users/create'),
                ],
            ],
        ],
        [
            'label' => 'Groups',
            'icon' => 'ph-users-three',
            'href' => url('user-groups'),
            'active' => request()->is('user-groups', 'user-groups/*'),
        ],
    ];
@endphp

<aside class="sidebar" id="amber-sidebar">
    <div class="sidebar-brand-row">
        @include('partials.site.brand')
        <button
            class="icon-button sidebar-close"
            type="button"
            aria-label="Close navigation"
            data-amber-menu-close>
            <i class="ph ph-x" aria-hidden="true"></i>
        </button>
    </div>

    <nav class="sidebar-nav" aria-label="Main navigation">
        <ul class="sidebar-nav-list">
            @foreach ($navItems as $item)
                @php
                    $children = $item['children'] ?? [];
                    $childIsActive = collect($children)->contains('active', true);
                    $isActive = $item['active'] || $childIsActive;
                @endphp

                <li
                    @class(['sidebar-nav-item', 'active' => $isActive, 'show' => $children])
                    @if ($children) data-amber-nav-group @endif>
                    @if ($children)
                        <div class="sidebar-nav-link sidebar-nav-group">
                            <a
                                class="sidebar-nav-group-link"
                                href="{{ $item['href'] }}"
                                @if ($item['active']) aria-current="page" @endif>
                                <i class="ph {{ $item['icon'] }} sidebar-nav-icon" aria-hidden="true"></i>
                                <span class="sidebar-nav-label">
                                    {{ $item['label'] }}
                                </span>
                            </a>
                            <button
                                class="sidebar-nav-group-toggle"
                                type="button"
                                aria-label="Toggle {{ $item['label'] }} navigation"
                                aria-expanded="true"
                                data-amber-nav-group-toggle>
                                <i class="ph ph-caret-up sidebar-nav-chevron" aria-hidden="true"></i>
                            </button>
                        </div>

                        <ul class="sidebar-nav-children">
                            @foreach ($children as $child)
                                <li @class(['sidebar-nav-child', 'active' => $child['active']])>
                                    <a
                                        class="sidebar-nav-link sidebar-nav-link-child"
                                        href="{{ $child['href'] }}"
                                        @if ($child['active']) aria-current="page" @endif>
                                        <i class="ph {{ $child['icon'] }} sidebar-nav-icon" aria-hidden="true"></i>
                                        <span class="sidebar-nav-label">
                                            {{ $child['label'] }}
                                        </span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <a
                            class="sidebar-nav-link"
                            href="{{ $item['href'] }}"
                            @if ($isActive) aria-current="page" @endif>
                            <i class="ph {{ $item['icon'] }} sidebar-nav-icon" aria-hidden="true"></i>
                            <span class="sidebar-nav-label">
                                {{ $item['label'] }}
                            </span>
                            @isset($item['badge'])
                                <span class="sidebar-nav-badge">
                                    {{ $item['badge'] }}
                                </span>
                            @endisset
                        </a>
                    @endif
                </li>
            @endforeach
        </ul>
    </nav>

    <div class="sidebar-support">
        <a
            class="sidebar-nav-link"
            href="https://amber.larajax.org"
            target="_blank"
            rel="noopener">
            <i class="ph ph-book-open sidebar-nav-icon" aria-hidden="true"></i>
            <span class="sidebar-nav-label">
                Documentation
            </span>
        </a>
    </div>
</aside>
