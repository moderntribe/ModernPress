# Faceted Directory

A filterable, searchable, paginated listing of any public post type (resource library, blog, news archive). Editors build it from blocks; no code changes are needed for content or filter updates.

## How it works (the short version)

1. An admin defines **facets** (a taxonomy + how to display it) in **Tribe → Facets**.
2. An editor adds the **Faceted Directory** block to a page. It contains two locked inner blocks: **Filter Bar** and **Directory Grid**.
3. The editor picks post types and filter bar position on the Faceted Directory block, and which facets appear on the Filter Bar block.
4. On the front end the whole block is a `<form method="get">`. With JS, changes re-render only the grid over REST. Without JS, the form submits normally.
5. Filtering uses a denormalized **index table** when it is built, and falls back to plain `tax_query` when it is not.

## Blocks

| Block | Slug | Role |
|-------|------|------|
| Faceted Directory | `tribe/faceted-directory` | Wrapper + `<form>`. Attributes `filterBarPosition` (`top`/`sidebar`) and `postTypes`. Provides both as block context. |
| Filter Bar | `tribe/filter-bar` | Renders the selected facets. Attribute `facets` (slug + optional `displayLabel`). Not insertable on its own. |
| Directory Grid | `tribe/directory-grid` | Renders the post cards and pagination. Attributes `postsPerPage` (default 12, max 100) and `showPagination`. Not insertable on its own. |

Inner blocks read the parent's values through block context (`tribe/faceted-directory/postTypes`, `.../filterBarPosition`). In the editor preview the context arrives as a `tribePreviewContext` query arg instead (see `Abstract_Block_Controller::get_context_value()`).

## Facet configuration

**Tribe → Facets** (`/wp-admin/admin.php?page=tribe-facets`, ACF options page, class `Settings\Facets_Settings`). One repeater row per facet:

| Field | Meaning |
|-------|---------|
| Label | Default label shown to visitors. |
| Slug | Unique key. Becomes the URL param `facet_<slug>`. |
| Taxonomy | Taxonomy the facet filters on. |
| Post Types | The facet is only offered in directories that include at least one of these. |
| Display Type | `dropdown` (multi-select) or `checkboxes`. |
| Customize by Layout | Shows Sidebar and Mobile types. Blank inherits `mobile → sidebar → display type`. |
| Starts Expanded | Accordion default (checkboxes only), separately for sidebar and mobile. |
| Show Hierarchy | Nest child terms; selecting a parent also matches its children. Ignored for non-hierarchical taxonomies. |
| Searchable | Type-ahead box inside a dropdown. |

A row missing Label, Slug, Taxonomy or Post Types is silently ignored (`Facet_Registry::normalize_row()`).

Two built-in facets need no configuration and are added on the Filter Bar block: **Search** (keyword, 400 ms debounce) and **Clear all**.

## URL parameters

| Param | Meaning |
|-------|---------|
| `facet_<slug>[]` | Selected term slugs for a facet (comma separated also accepted). Max 50 per facet. |
| `facet_search` | Keyword search. |
| `facet_page` | Page number. Not `paged`: WordPress canonical-redirects an unused `paged` on singular pages. |

URLs are shareable; the server applies them on load. Params for facets that do not apply to the directory's post types are ignored. Back/forward reloads the page to restore state.

## Request flow

### Initial page load

`render.php` (block) → controller → `Directory_Query::build()` → `WP_Query` → `components/directory-grid/grid.php`.

### Live filtering (JS)

```
change on a control → view.js → results.js
  → GET /wp-json/tribe/v1/facets/results?facet_...&post_types[]=...&posts_per_page=...&path=...
  → Results_Endpoint → Directory_Grid_Controller → grid.php
  → { html, found }  → grid.innerHTML replaced, live region announces "N results found"
```

- Stale requests are aborted (`AbortController`); the grid sets `aria-busy` while loading.
- On failure the form is submitted normally.
- The response carries `Cache-Control: public, max-age=60`.
- Desktop filters live. On mobile the flyout is deferred until **Show results**.

### Endpoint validation

The endpoint is public and read-only. Its params have a schema (`Results_Endpoint::get_args()`), so malformed shapes get a 400. Everything else is re-validated server side: post types are limited to public ones (attachments excluded), page size is capped, and `path` is reduced to a relative path so pagination links cannot point off-site.

## Querying

`Facets\Directory_Query::build()` returns WP_Query args and, on the indexed path, the total count.

1. Facet selections are read from the request, sanitized, and limited to facets that apply to the directory's post types.
2. Hierarchical facets expand to include descendants (`Facet_Terms::expand_with_descendants()`).
3. If there is **no search term**, the index is **built**, and at least one facet is selected, `Facet_Index::get_page()` returns one page of IDs plus the total. WP_Query then fetches just that page via `post__in`.
4. Otherwise a normal `tax_query` is used (AND between facets, OR between terms in a facet).

Search bypasses the index because the index paginates before WP_Query runs.

Both paths give the same semantics: **AND across facets, OR within a facet**, newest first.

## The facet index

A table `{prefix}tribe_facet_index` with one row per post + facet + term. It lets a multi-facet filter resolve in a single grouped query instead of one taxonomy join per facet.

| Class | Job (one each) |
|-------|-----|
| `Facet_Index_Table` | Schema, "built" flag, rebuild progress, cache version. Created on `admin_init` and on rebuild. |
| `Facet_Index_Writer` | Writes rows: replaces or deletes the rows of given posts. Knows nothing about *when*. |
| `Facet_Index_Sync` | Keeps the index current: queues changed posts, flushes once on `shutdown`, reacts to term edits and deletes. |
| `Facet_Index_Rebuilder` | Full rebuild, in resumable batches (see below). |
| `Facet_Index` | Read side: page + total queries, available-term lookup. |
| `Facet_Index_Admin` | Status message, **Rebuild Index** button, settings-save hook. |

How it stays current:

- `save_post` and `set_object_terms` **queue** the post ID. The queue flushes once on `shutdown`, so a save touching several taxonomies reindexes the post once.
- Only post types and taxonomies used by a facet are queued.
- `deleted_post` removes the rows. `edited_term` and `delete_term` queue the attached posts, or schedule a full rebuild above 500 posts.
- Saving **Tribe → Facets** schedules a rebuild (WP-Cron, +30 s).
- Writes are skipped while the index is neither built nor being rebuilt, because the next rebuild covers them.

### Rebuilding

Hosts kill requests after a fixed time (for example 30 s), so a rebuild never runs as one long request:

1. `start()` marks the index unbuilt, truncates the table and stores progress (`cursor` = last post ID, `indexed`, `total`).
2. `process()` indexes batches of 200 posts until a time budget is used (20 s, or 60% of `max_execution_time` if lower), saving the cursor after every batch.
3. If posts remain, it schedules the next run on WP-Cron (+5 s). A 60 s safety-net event is scheduled first, so a fatal or timeout mid-batch still resumes.
4. When no posts remain the index is marked built and progress is cleared.

The **Rebuild Index** button runs `start()` + the first batch immediately and the rest continues in the background. The Tribe → Facets screen shows "Rebuilding: X of Y posts indexed". Changes made during a rebuild are written through the normal sync path, so they are not lost. Directories use the `tax_query` fallback until the rebuild finishes.

On low-traffic sites WP-Cron only fires on page loads; use a real cron hitting `wp-cron.php` if a rebuild seems slow to continue.

The index also powers "hide terms with no results": `Facet_Index::get_available_term_slugs()` limits each facet's options to terms that have published posts of the directory's post types. That list is cached in a transient keyed by a version number, which is bumped on every index change.

**After a deploy or content import, rebuild the index.** Until it is built everything still works, just through slower taxonomy queries.

## PHP structure

All under `wp-content/plugins/core/src/`.

```
Facets/
  Facets_Definer.php      DI bindings (also binds \wpdb)
  Facets_Subscriber.php   hooks only; closures resolve services
  Facet_Registry.php      reads settings, normalizes facets, post-type helpers
  Facet_Types.php         type constants + layout inheritance rules
  Facet_Terms.php         term options, hierarchy nesting, descendant expansion (memoized)
  Facet_Renderer.php      markup for search / reset / dropdown / checkboxes
  Directory_Query.php     request -> WP_Query args
  Results_Endpoint.php    REST route
  Facet_Index*.php        index: Table, Writer, Sync, Rebuilder, reader, Admin (see above)
Components/Blocks/
  Faceted_Directory_Controller.php   position class, context key constants
  Filter_Bar_Controller.php          resolves selected facets, grid slots, wrapper attrs
  Directory_Grid_Controller.php      runs the query, pagination
Settings/Facets_Settings.php         admin screen (under Tribe_Settings)
```

Conventions: hooks live in the subscriber and call services through the container; controllers prepare data and templates only print it; `Facet_Renderer` is the only place facet markup is built.

## Theme files

```
themes/core/blocks/tribe/{faceted-directory,filter-bar,directory-grid}/   block.json, edit.js, render.php, styles
themes/core/blocks/tribe/filter-bar/js/
  results.js      AJAX refresh, URL push, pagination links
  dropdown.js     accessible multi-select over a hidden <select multiple>
  flyout.js       mobile dialog (focus trap, Escape, body lock)
  facet-clear.js  per-facet Clear buttons
  selectors.js    all markup hooks in one place
themes/core/components/
  filter-bar/{sidebar,facets}.php   desktop fieldset + mobile flyout, facet loop
  directory-grid/grid.php           cards + pagination (used by block and REST)
```

The filter bar renders every facet twice, once for desktop and once inside the mobile flyout, so each layout can use a different control type. The inactive fieldset is `disabled`, so its inputs are left out of the form data. The layout switches at **992 px container width** (container queries), for both positions.

## Accessibility

- Facet groups use fieldset/legend, every control has a label, and control IDs are namespaced per filter bar (`facet-N-<slug>-<layout>`), so several directories on one page do not collide.
- Dropdown follows the listbox pattern (`aria-haspopup`, `aria-expanded`, `aria-selected`, `aria-multiselectable`) with keyboard support.
- The flyout is `role="dialog"` with `aria-modal` and a focus trap.
- A polite live region announces "N results found" after each refresh.

## Common tasks

**Add a new facet:** add a row in Tribe → Facets, then add it to the Filter Bar block in the editor. It will not appear in the editor list unless its Post Types overlap the directory's.

**Add a new display type:** add a constant in `Facet_Types`, a `render_*` method and a `match` arm in `Facet_Renderer::render()`, add it to `Facet_Types::choices()`, and add the JS behaviour if it needs any.

**Filters not showing up?**
1. Facet saved with all four required fields? (Otherwise ignored.)
2. Facet's Post Types include the directory's post types?
3. Facet added to the Filter Bar block and the page updated?
4. Editor reloaded after saving the facet? The catalog is injected when the editor loads.

**Results look stale or terms are missing:** rebuild the index in Tribe → Facets.

**Slow directory on a large site:** check the index status notice. If it says "Not built yet", rebuild.

## Known limits

- Search plus facets uses `tax_query`, not the index.
- The selected-count label ("N options chosen...") has a single plural form.
- Back/forward reloads the page instead of replaying state into the controls.
- Rate limiting for the public REST route belongs at the edge; the route only sets short cache headers.
