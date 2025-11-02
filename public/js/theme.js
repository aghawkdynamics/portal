document.addEventListener('DOMContentLoaded', () => {

    const toggle = document.getElementById('themeToggle');
    const menu = document.getElementById('themeMenu');
    const current = toggle.querySelector('.theme-current');
    const btns = menu.querySelectorAll('.theme-btn');

    function applyTheme(t) {
        document.body.className = document.body.className.replace(/\btheme-\w+\b/g, '').trim();
        document.body.classList.add('theme-' + t);
        localStorage.setItem('theme', t);
        document.cookie = `theme=${t}; path=/; max-age=${60 * 60 * 24 * 365}`;

        btns.forEach(b => b.classList.toggle('active', b.dataset.theme === t));
        /* Apply current theme color to toggle button */
        const src = Array.from(btns).find(b => b.dataset.theme === t);
        if (src) current.style.background = src.style.background;
    }

    applyTheme(localStorage.getItem('theme') || 'light');

    // Click handler
    toggle.addEventListener('click', e => {
        menu.classList.toggle('show');
        e.stopPropagation();
    });

    // Keyboard support for toggle
    toggle.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') {
            e.preventDefault();
            menu.classList.toggle('show');
        } else if (e.key === 'Escape') {
            menu.classList.remove('show');
        }
    });

    // Close menu when clicking outside
    document.addEventListener('click', () => menu.classList.remove('show'));

    // Theme button handlers
    btns.forEach(b => {
        // Click handler
        b.addEventListener('click', e => {
            applyTheme(b.dataset.theme);
            menu.classList.remove('show');
            e.stopPropagation();
        });

        // Keyboard support for theme buttons
        b.addEventListener('keydown', e => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                applyTheme(b.dataset.theme);
                menu.classList.remove('show');
                toggle.focus();
            }
        });
    });
});