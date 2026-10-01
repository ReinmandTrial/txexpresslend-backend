import path from 'node:path';

/** id prefix from the file name: clip0_1_2 → arrow-right__clip0_1_2 */
const prefixByFileName = (node, info) =>
  info.path ? path.basename(info.path, '.svg') : 'icon';

/**
 * Single-color icons: any fill/stroke color becomes currentColor and
 * width/height are removed — size and color come from classes (size-6 text-orange).
 *
 * Multicolor icons go into src/assets/icons/multicolor/ —
 * they have a separate config, svgo.multicolor.config.js, which keeps the palette.
 */
export default {
  multipass: true,
  js2svg: {
    indent: 2,
    pretty: true,
  },
  plugins: [
    // Figma often exports color as style="fill:#..." — move it to attributes,
    // otherwise convertColors won't see it. Must run before preset-default.
    'convertStyleToAttrs',

    {
      name: 'preset-default',
      params: {
        overrides: {
          convertPathData: { floatPrecision: 2 },
          convertColors: { currentColor: true },
        },
      },
    },

    // Remove width/height, keep viewBox — CSS sets the size.
    'removeDimensions',

    // Figma titles like "arrow-right" aren't needed: accessibility is handled
    // with aria-hidden + sr-only in the markup.
    'removeTitle',

    // Inside the sprite, ids from different icons would collide (they'd all be "a", "b").
    // A file-name prefix prevents that.
    {
      name: 'prefixIds',
      params: {
        prefix: prefixByFileName,
        delim: '__',
        prefixClassNames: false,
      },
    },
  ],
};
