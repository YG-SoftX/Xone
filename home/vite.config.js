import { defineConfig } from 'vite';
import { resolve } from 'path';

export default defineConfig({
    root: '.',
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        rollupOptions: {
            input: {
                main:        resolve(__dirname, 'index.html'),
                search:      resolve(__dirname, 'search.html'),
                neuralChat:  resolve(__dirname, 'neural-chat.html'),
            },
        },
        // Generate hashed filenames for cache-busting
        chunkSizeWarningLimit: 500,
    },
    server: {
        port: 3000,
        open: true,
    },
});
