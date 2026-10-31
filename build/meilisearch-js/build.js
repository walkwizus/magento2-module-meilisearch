import { build } from 'esbuild';
import { readFileSync } from 'node:fs';
import { dirname, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = dirname(fileURLToPath(import.meta.url));
const { version } = JSON.parse(readFileSync(resolve(root, 'node_modules/meilisearch/package.json'), 'utf8'));
const outfile = resolve(
    root,
    '../../src/module-meilisearch-frontend/view/frontend/web/js/lib/meilisearch/umd/index.min.js'
);

await build({
    entryPoints: [resolve(root, 'entry.js')],
    outfile,
    bundle: true,
    minify: true,
    sourcemap: true,
    format: 'iife',
    globalName: '__meilisearch',
    platform: 'browser',
    target: ['es2020'],
    legalComments: 'none',
    banner: {
        js: `/*! meilisearch-js v${version} | MIT License | https://github.com/meilisearch/meilisearch-js */\n`
            + '(function (root, factory) {'
            + 'if (typeof define === "function" && define.amd) { define([], factory); }'
            + 'else { var m = factory(); root.meilisearch = m; root.Meilisearch = m.Meilisearch; }'
            + '}(typeof self !== "undefined" ? self : this, function () {'
    },
    footer: {
        js: 'return __meilisearch;}));'
    }
});

console.log(`meilisearch-js v${version} -> ${outfile}`);
