<script>
    (function () {
        const storageKey = 'schoolpass.theme';

        function normalizeTheme(theme) {
            return theme === 'dark'
                ? 'dark'
                : 'light';
        }

        function currentTheme() {
            return normalizeTheme(
                document.documentElement.getAttribute(
                    'data-bs-theme'
                )
            );
        }

        function updateButtons(theme) {
            const nextTheme =
                theme === 'dark'
                    ? 'light'
                    : 'dark';

            const label =
                nextTheme === 'dark'
                    ? 'Activar tema oscuro'
                    : 'Activar tema claro';

            document
                .querySelectorAll(
                    '[data-schoolpass-theme-toggle]'
                )
                .forEach(function (button) {
                    button.setAttribute(
                        'aria-label',
                        label
                    );

                    button.setAttribute(
                        'title',
                        label
                    );

                    const text =
                        button.querySelector(
                            '[data-schoolpass-theme-label]'
                        );

                    if (text) {
                        text.textContent = label;
                    }
                });
        }

        function applyTheme(
            requestedTheme,
            persist = true
        ) {
            const theme =
                normalizeTheme(requestedTheme);

            document.documentElement.setAttribute(
                'data-bs-theme',
                theme
            );

            document.documentElement.style.colorScheme =
                theme;

            if (persist) {
                try {
                    window.localStorage.setItem(
                        storageKey,
                        theme
                    );
                } catch (error) {
                    console.warn(
                        'No se pudo guardar el tema.',
                        error
                    );
                }
            }

            updateButtons(theme);

            window.dispatchEvent(
                new CustomEvent(
                    'schoolpass:theme-changed',
                    {
                        detail: {
                            theme: theme,
                        },
                    }
                )
            );
        }

        function toggleTheme() {
            applyTheme(
                currentTheme() === 'dark'
                    ? 'light'
                    : 'dark'
            );
        }

        document.addEventListener(
            'click',
            function (event) {
                const button =
                    event.target.closest(
                        '[data-schoolpass-theme-toggle]'
                    );

                if (! button) {
                    return;
                }

                event.preventDefault();

                toggleTheme();
            }
        );

        window.addEventListener(
            'storage',
            function (event) {
                if (
                    event.key !== storageKey
                    || ! event.newValue
                ) {
                    return;
                }

                applyTheme(
                    event.newValue,
                    false
                );
            }
        );

        document.addEventListener(
            'DOMContentLoaded',
            function () {
                updateButtons(
                    currentTheme()
                );
            }
        );

        window.SchoolPassTheme = {
            get: currentTheme,
            set: applyTheme,
            toggle: toggleTheme,
        };
    })();
</script>