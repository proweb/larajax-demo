/*
 * Amber demo layout
 *
 * Data attributes:
 * - data-amber-menu-toggle - opens the sidebar on small screens
 * - data-amber-menu-close - closes the sidebar on small screens
 * - data-amber-nav-group-toggle - expands or collapses a sidebar group
 * - data-amber-theme-toggle - switches between the light and dark theme and remembers the choice
 *
 * The layout head sets data-bs-theme before paint; until the toggle stores a choice, the page follows the OS.
 */
'use strict';

(function () {
    var THEME_KEY = 'amber-theme';

    function getStoredTheme() {
        try {
            var theme = localStorage.getItem(THEME_KEY);
            return theme === 'light' || theme === 'dark' ? theme : null;
        }
        catch (e) {
            return null;
        }
    }

    function openMenu() {
        document.body.classList.add('sidebar-open');
        setExpanded('[data-amber-menu-toggle]', true);
    }

    function closeMenu() {
        document.body.classList.remove('sidebar-open');
        setExpanded('[data-amber-menu-toggle]', false);
    }

    function setExpanded(selector, isExpanded) {
        document.querySelectorAll(selector).forEach(function (el) {
            el.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
        });
    }

    function toggleNavGroup(button) {
        var group = button.closest('[data-amber-nav-group]');
        if (!group) {
            return;
        }

        var isOpen = group.classList.toggle('show');
        button.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    }

    function toggleTheme() {
        var root = document.documentElement,
            theme = root.getAttribute('data-bs-theme') === 'dark' ? 'light' : 'dark';

        root.setAttribute('data-bs-theme', theme);

        try {
            localStorage.setItem(THEME_KEY, theme);
        }
        catch (e) {
            // Storage can be unavailable, the theme still applies to this page
        }
    }

    document.addEventListener('click', function (ev) {
        var target = ev.target;

        if (target.closest('[data-amber-menu-toggle]')) {
            openMenu();
        }
        else if (target.closest('[data-amber-menu-close]')) {
            closeMenu();
        }
        else if (target.closest('[data-amber-nav-group-toggle]')) {
            toggleNavGroup(target.closest('[data-amber-nav-group-toggle]'));
        }
        else if (target.closest('[data-amber-theme-toggle]')) {
            toggleTheme();
        }
    });

    document.addEventListener('keydown', function (ev) {
        if (ev.key === 'Escape' && document.body.classList.contains('sidebar-open')) {
            closeMenu();
        }
    });

    // Follows OS colour scheme changes until the visitor picks a theme with the toggle.
    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', function (ev) {
        if (!getStoredTheme()) {
            document.documentElement.setAttribute('data-bs-theme', ev.matches ? 'dark' : 'light');
        }
    });
})();
