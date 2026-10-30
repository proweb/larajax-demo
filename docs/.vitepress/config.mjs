const sharedSidebar = [
    {
        text: "Introduction",
        items: [
            { text: 'Getting Started', link: '/getting-started' },
            { text: 'Installation', link: '/installation' }
        ]
    },
    {
        text: "Widgets",
        items: [
            { text: 'Forms', link: '/forms' },
            { text: 'Lists', link: '/lists' },
            { text: 'Filters', link: '/filters' },
            { text: 'Toolbar', link: '/toolbar' }
        ]
    },
    {
        text: "UI Elements",
        items: [
            { text: 'UI Elements', link: '/ui' }
        ]
    },
    {
        text: "Going Further",
        items: [
            { text: 'Eloquent Models', link: '/eloquent' },
            { text: 'AJAX & Larajax', link: '/ajax' }
        ]
    }
];

export default {
    title: 'Amber',
    description: 'Form, List and UI tools for Laravel',
    srcExclude: ['README.md'],
    head: [
        ['link', { rel: 'icon', href: '/favicon.svg' }]
    ],
    themeConfig: {
        nav: [
            { text: 'Guide', link: '/getting-started' },
            { text: 'GitHub', link: 'https://github.com/larajax/amber' }
        ],
        sidebar: {
            '/': sharedSidebar
        },
        socialLinks: [
            { icon: 'github', link: 'https://github.com/larajax/amber' }
        ],
        search: { provider: 'local' },
        outline: [2, 3] // h2 and h3
    }
}
