import { defineConfig, loadEnv } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig(({ mode }) => {
    const env = loadEnv(mode, process.cwd(), '');

    const lanIp = env.LAN_IP || '192.168.1.10';

    return {
        plugins: [
            laravel({
                input: [
                    'resources/css/app.css',
                    'resources/js/app.js',
                    'resources/js/artikel-editor.js',
                ],

                refresh: true,

                fonts: [
                    bunny('Plus Jakarta Sans', {
                        weights: [400, 500, 600, 700, 800],
                    }),

                    bunny('Fraunces', {
                        weights: [400, 500, 600],
                        styles: ['normal', 'italic'],
                    }),
                ],
            }),

            tailwindcss(),
        ],

        server: {
            host: '0.0.0.0',
            port: 5173,
            strictPort: true,

            cors: {
                origin: true,
            },

            hmr: {
                host: lanIp,
                port: 5173,
                clientPort: 5173,
                protocol: 'ws',
            },

            origin: `http://${lanIp}:5173`,

            watch: {
                ignored: ['**/storage/framework/views/**'],
            },
        },
    };
});
