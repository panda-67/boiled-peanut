import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';
import { resolve } from 'node:path';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            hotFile: '../public_html/kacang/hot',
            buildDirectory: 'build',
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
    build: {
        outDir: resolve(__dirname, '../public_html/kacang/build'),
        manifest: 'manifest.json',
        emptyOutDir: true,
    },
});
