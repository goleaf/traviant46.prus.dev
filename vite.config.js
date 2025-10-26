import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

const port = Number(process.env.VITE_PORT ?? 5173);

export default defineConfig({
    server: {
        host: '0.0.0.0',
        port,
        strictPort: true,
        hmr: {
            host: process.env.VITE_HMR_HOST ?? 'localhost',
            port,
        },
    },
    preview: {
        port,
    },
    plugins: [
        laravel({
            input: ['resources/scss/game.scss', 'resources/js/app.js'],
            refresh: ['resources/views/**', 'app/Livewire/**'],
        }),
        tailwindcss(),
    ],
});
