<script>
    (function () {
        const storageKey = 'schoolpass.theme';

        let theme = null;

        try {
            theme = window.localStorage.getItem(
                storageKey
            );
        } catch (error) {
            theme = null;
        }

        if (
            theme !== 'light'
            && theme !== 'dark'
        ) {
            theme = window.matchMedia
                && window.matchMedia(
                    '(prefers-color-scheme: dark)'
                ).matches
                    ? 'dark'
                    : 'light';
        }

        document.documentElement.setAttribute(
            'data-bs-theme',
            theme
        );

        document.documentElement.style.colorScheme =
            theme;
    })();
</script>