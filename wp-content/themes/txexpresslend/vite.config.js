import { defineConfig } from 'vite';
import tailwindcss from '@tailwindcss/vite';
import svgSpritemap from '@spiriit/vite-plugin-svg-spritemap';

// Vite only builds files into dist/ here; WordPress serves the pages.
// PHP finds out which files to enqueue from dist/.vite/manifest.json (inc/vite.php).
export default defineConfig({
    // Relative paths: CSS points to the fonts next to it,
    // wherever the theme ends up on the server.
    base: './',

    // The logo and favicon live in assets/ and are served as is, nothing to copy.
    publicDir: false,

    build: {
        outDir: 'dist',
        emptyOutDir: true,
        manifest: true,
        rollupOptions: {
            input: ['src/main.js', 'src/assets/css/main.css'],
        },
    },

    plugins: [
        tailwindcss(),

        // Bundles all icons from src/assets/icons (including multicolor/)
        // into one sprite, dist/assets/icons.svg. In PHP: txl_icon('arrow-right').
        svgSpritemap('src/assets/icons/**/*.svg', {
            // Icons are cleaned up by our svgo.config.js (pnpm icons) —
            // a second pass here would only break currentColor and var().
            svgo: false,
            oxvg: false,

            // Otherwise the ids would be sprite-arrow-right.
            prefix: false,

            output: {
                filename: 'icons.svg',
                name: 'icons.svg',
                hrefAttribute: 'href',
                use: false,
                view: false,
            },
        }),
    ],
});
