import { defineConfig } from 'vite'
import { svelte } from '@sveltejs/vite-plugin-svelte'

// https://vitejs.dev/config/
export default defineConfig({
    plugins: [svelte()],
    server: {
        port: 2009,
        portStrict: true,
        proxy: {
            '/conia' : 'http://localhost:1983',
        }
    }
})
