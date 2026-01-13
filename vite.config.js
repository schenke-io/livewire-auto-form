import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'workbench/resources/js/workbench.js',
                'workbench/resources/css/workbench.css'
            ],
            refresh: true,
            publicDirectory: 'workbench/public'
        }),
    ]
});
