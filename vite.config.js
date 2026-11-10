
import { defineConfig } from 'vite';
import { fileURLToPath, URL } from 'node:url';

import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                'resources/js/guardian-credential.js',
                'resources/js/schoolcore/main.tsx',
                'resources/js/teacher/main.tsx',
                'resources/js/grade-portal/main.tsx',
                'resources/js/schoolcore-public/main.tsx',
                'resources/js/paselista/paselista-landing.tsx',
            ],
            refresh: true,
        }),

        react(),
        tailwindcss(),
    ],

    resolve: {
        alias: {
            '@': fileURLToPath(
                new URL('./resources/js/schoolcore', import.meta.url)
            ),
        },
    },

    server: {
        host: '127.0.0.1',
        port: 5173,
        strictPort: true,
        hmr: {
            host: '127.0.0.1',
        },
    },
});
