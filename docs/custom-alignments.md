# Custom Alignments

The Custom Alignments toolbar dropdown uses the existing `align` attribute alongside the WordPress alignment dropdown. Configure widths in `settings._experimentalLayout` in `theme.json`:

```json
[
  { "name": "Grid", "slug": "grid", "width": "1680px", "icon": "grid" },
  { "name": "Medium", "slug": "medium", "width": "60rem", "icon": "stretchWide" }
]
```

Each slug must be unique, start with a letter, contain only letters, numbers, hyphens or underscores, and differ from the native alignment names. Widths must be valid CSS `max-width` values. Names use the `tribe` text domain. Custom widths apply in constrained layouts and are sorted by their measured widths when the menu opens or the editor canvas is resized. Each option shows its configured maximum width beneath its name, and the toggle shows the active custom alignment. Selecting another alignment replaces the previous one; WordPress's dropdown shows its default icon for custom values. Use WordPress's alignment dropdown to clear a custom alignment. Deactivate the old Block Editor Custom Alignments plugin before using this control.

Core blocks supporting wide alignment and blocks with `"align": true` support custom widths. Other blocks must include each supported slug in their `supports.align` array, for example `"align": ["wide", "grid", "full"]`. To restrict the dropdown, set `settings._experimentalLayoutInclude` or `settings._experimentalLayoutExclude` to arrays of block names. An empty include array allows all eligible blocks; exclusions take precedence. Existing custom-aligned content remains styled even when its block is excluded.

Set `icon` to an exported name from [`@wordpress/icons`](https://wordpress.github.io/gutenberg/?path=/story/icons-icon--library), such as `grid` or `stretchWide`. Unknown names are reported in the browser console and use the default icon.
