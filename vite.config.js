import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/js/review.js', 'resources/js/exam.js','resources/js/auth.js', 'resources/js/crossword.js', 'resources/js/wordsearch.js', 'resources/js/sound-preview.js', 'resources/js/games/soldier-and-monster.js', 'resources/js/games/galaxy-defender.js', 'resources/js/games/zombie-defense.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
