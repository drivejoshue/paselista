(() => {
    const html = document.documentElement;
    const nav = document.getElementById('sp-public-nav');
    const menu = document.getElementById('sp-public-menu');
    const menuButton = document.getElementById('sp-public-menu-button');
    const themeButton = document.getElementById('sp-theme-toggle');
    const backToTop = document.getElementById('sp-back-to-top');
    const themeKey = 'schoolpass.public.theme';

    const applyTheme = (theme) => {
        const dark = theme === 'dark';
        html.classList.toggle('dark', dark);
        html.classList.toggle('light', !dark);
        localStorage.setItem(themeKey, dark ? 'dark' : 'light');

        if (themeButton) {
            themeButton.innerHTML = dark
                ? '<i class="uil uil-sun"></i>'
                : '<i class="uil uil-moon"></i>';
            themeButton.setAttribute(
                'aria-label',
                dark ? 'Activar tema claro' : 'Activar tema oscuro'
            );
        }
    };

    const storedTheme = localStorage.getItem(themeKey);
    const initialTheme = storedTheme
        || (window.matchMedia('(prefers-color-scheme: dark)').matches
            ? 'dark'
            : 'light');

    applyTheme(initialTheme);

    themeButton?.addEventListener('click', () => {
        applyTheme(html.classList.contains('dark') ? 'light' : 'dark');
    });

    menuButton?.addEventListener('click', () => {
        const isOpen = menu?.classList.toggle('is-open') ?? false;
        menuButton.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
    });

    menu?.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            menu.classList.remove('is-open');
            menuButton?.setAttribute('aria-expanded', 'false');
        });
    });

    const syncScrollState = () => {
        const scrolled = window.scrollY > 18;
        nav?.classList.toggle('is-scrolled', scrolled);
        backToTop?.classList.toggle('is-visible', window.scrollY > 420);
    };

    window.addEventListener('scroll', syncScrollState, { passive: true });
    syncScrollState();

    backToTop?.addEventListener('click', (event) => {
        event.preventDefault();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    });
})();
