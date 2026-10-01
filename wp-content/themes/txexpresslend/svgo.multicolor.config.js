import path from 'node:path';

const prefixByFileName = (node, info) =>
  info.path ? path.basename(info.path, '.svg') : 'icon';

/**
 * Multicolor icons: same as above, but the palette is kept (no currentColor).
 * To recolor them from outside, use CSS variables right in the SVG:
 *   <path fill="var(--icon-accent, #f2803d)" />
 * Variables are inherited inside <use>, unlike regular selectors.
 */
export default {
  multipass: true,
  js2svg: {
    indent: 2,
    pretty: true,
  },
  plugins: [
    'convertStyleToAttrs',
    {
      name: 'preset-default',
      params: {
        overrides: {
          convertPathData: { floatPrecision: 2 },
        },
      },
    },
    'removeDimensions',
    'removeTitle',
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
