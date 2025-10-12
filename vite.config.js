import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/scss/game.scss', 'resources/js/app.js'],
            refresh: ['resources/views/**', 'app/Livewire/**'],
        }),
        tailwindcss(),
    ],
});
