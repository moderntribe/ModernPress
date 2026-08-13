# Gutenberg Blocks – Agent Reference

## Block Directories

```
wp-content/themes/core/blocks/tribe/      ← custom project blocks
wp-content/themes/core/blocks/core/       ← WordPress core block overrides
wp-content/themes/core/blocks/outermost/  ← Outermost plugin block overrides
```

## Naming Convention

Block folder name = block slug suffix.

| Block slug | Folder path | Type |
|-----------|-------------|-------------|
| `tribe/announcements` | `blocks/tribe/announcements/` | Custom Modern Tribe block |
| `core/heading` | `blocks/core/heading/` | Core WordPress block |
| `vendor/block` | `blocks/vendor/block` | Example block from a third-party plugin |

**Rule:** `mkdir` the folder, then set `"name": "tribe/<folder-name>"` in `block.json`. Never deviate.

## Files Per Block

| File | Required | Purpose |
|------|----------|---------|
| `block.json` | Yes | Block metadata, attributes, supports |
| `index.js` | Yes | Registers block client-side |
| `edit.js` | Yes | Editor component (React) |
| `style.pcss` | Yes | Front-end + editor shared styles |
| `editor.pcss` | No | Editor-only styles |
| `render.php` | No | Server-side render (replaces `save.js` for dynamic blocks) |
| `view.js` | No | Front-end interactivity script |
| `_mixins.pcss` | No | PostCSS mixins local to this block |

## `block.json` Skeleton

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 3,
  "name": "tribe/<slug>",
  "version": "1.0.0",
  "title": "<Human Title>",
  "category": "theme",
  "description": "<One sentence>",
  "attributes": {},
  "supports": {},
  "editorScript": "file:./index.js",
  "style": "file:./style.pcss",
  "editorStyle": "file:./editor.pcss",
  "render": "file:./render.php"
}
```

Remove `"render"` for client-side-only blocks. Remove `"editorStyle"` if no `editor.pcss`.

## PHP Registration

Blocks self-register via `Block_Registrar.php` — no manual step required. The registrar scans all three block directories and calls `register_block_type()` for each `block.json` it finds.

If a block needs server-side PHP logic (e.g., custom REST endpoints, block bindings), add a class in `wp-content/plugins/core/src/Blocks/` and register it in `Blocks_Definer.php`.

## JS Entry Point (`index.js`)

```js
import { registerBlockType } from '@wordpress/blocks';
import metadata from './block.json';
import Edit from './edit';

registerBlockType( metadata.name, { edit: Edit } );
```

For blocks using `render.php`, `save` returns `null`:

```js
registerBlockType( metadata.name, { edit: Edit, save: () => null } );
```
