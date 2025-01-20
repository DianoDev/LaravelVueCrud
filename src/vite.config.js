import { defineConfig} from 'vite';
import laravel from 'laravel-vite-plugin';
import vue from '@vitejs/plugin-vue';
import {resolve} from 'node:path';

export default defineConfig(({ command, mode }) => {

    return {
        server: {
            fs: {
                cachedChecks: false
            },
            host: '0.0.0.0',
            strictPort: true,
            port: 5173
        },
        resolve: {
            alias: [
                {
                    find: /^~(.*)$/,
                    replacement: '$1',
                },
                { find: "@", replacement: resolve(__dirname, "./resources") }
            ]
        },
        plugins: [
            laravel({
                input: ['resources/sass/app.scss','resources/js/app.js'],
                refresh: true
            }),

            vue({
                template: {
                    transformAssetUrls: {
                        base: null,
                        includeAbsolute: false,
                    },
                },
            }),
        ],
        build: { chunkSizeWarningLimit: 1600 }
    }
});

