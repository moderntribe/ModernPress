# PHP – Agent Reference

ModernPress PHP for `wp-content/plugins/core`, `wp-content/themes/core`, and
`wp-content/mu-plugins`. Match existing `core` patterns before inventing new ones.
Prefer the simplest solution that meets the current requirement.

> When this doc and the codebase conflict, the codebase wins.

## Golden rules

1. Follow existing patterns in the `core` plugin/theme before adding a new one.
2. Simplest thing that works; add abstraction only for a concrete reason (see below).
3. Readable over clever: no nested inline ternaries; use early returns.
4. One responsibility per class and per method.
5. Type everything; escape at output; wire hooks in subscribers via closures that resolve services.
6. Comments only when the code is not self-explanatory — short and informative; never comment novels.

## Namespaces

| Namespace | Root Path |
|-----------|----------|
| `Tribe\Plugin\` | `wp-content/plugins/core/src/` |
| `Tribe\Theme\` | `wp-content/themes/core/` |

PSR-4 via Composer. Path mirrors namespace:
`Tribe\Plugin\Blocks\Block_Base` → `wp-content/plugins/core/src/Blocks/Block_Base.php`.

## Baseline style

- Standards: `moderntribe/coding-standards` (`ModernTribe` ruleset) + repo `phpcs.xml.dist`
- Static analysis: PHPStan **level 5**. PHP **8.4**.
- `<?php declare(strict_types=1);` as the first statement in every PHP file.
- Modern PHP: short arrays `[]`, typed properties/params/returns, union types
  (`int|null`, `\WP_Term|null`), namespaces, narrowest visibility.
- Formatting: tabs (width 4); spaces inside `( ... )`; align `=>` and `=` when it aids
  reading; contextual escaping (`esc_html`, `esc_attr`, `esc_url`, `esc_*__`).
- Naming: classes `Pascal_Snake_Case`; methods/properties `snake_case`; constants
  `UPPER_SNAKE`. Predicates `has_*` / `is_*` / `does_*` / `can_*` return `bool`;
  string builders `render_*`; heavy builders `build_*`; cached accessors `get_*`.
- Prefer instance methods + dependency injection. Do **not** add new static methods except
  to match an established framework pattern (e.g. `factory()`, bootstrap singleton).
- Text domain `'tribe'` for i18n.
- For WordPress APIs, treat the installed WP version +
  https://developer.wordpress.org/reference/ as the source of truth.

## Comments

Comments are a last resort, not a default. Prefer clear names and structure so the code
explains itself.

- **When:** only if the intent, constraint, or non-obvious *why* is not obvious from the
  code (workaround, WP quirk, security/ordering invariant, deliberate omission).
- **How:** one short line when possible; a few lines max. Informative, not narrative.
- **Never:** restate what the next line does; leave AI/chat essays in the diff; write a
  wall of comments for a tiny change; keep stale comments that no longer match the code.

**Hard fail in review / done-gates:** a change that is mostly commentary (e.g. ~20 lines of
comments around ~1 line of real code, or a long block explaining obvious logic) is
unacceptable — strip it before proposing the change as done. Needed human context goes in
a short PR/commit note or chat — not a novel in the source, and not an essay-length PR.

Docblocks: keep them minimal and accurate (types/`@param`/`@return` only when they add
information the signature does not already convey). Do not pad with fluff.

## DI Container Pattern

The plugin uses **PHP-DI**. `Core.php` wires **Definers** (bindings) and **Subscribers**
(hook registrations).

### Definers

Implement `Tribe\Plugin\Core\Interfaces\Definer_Interface`. Return PHP-DI definitions
binding interfaces to implementations.

```php
namespace Tribe\Plugin\MyFeature;

use Tribe\Plugin\Core\Interfaces\Definer_Interface;

class My_Feature_Definer implements Definer_Interface {
	public function define(): array {
		return [
			My_Service_Interface::class => \DI\autowire( My_Service::class ),
		];
	}
}
```

Register in `Core.php` `$definers` array:

```php
My_Feature_Definer::class,
```

### Subscribers

Extend `Tribe\Plugin\Core\Abstract_Subscriber`. In `register()`, wire hooks with
**closures** that resolve a service from the container and call it. Do **not** use
`[ $this, 'method' ]` callbacks — that is not the ModernPress pattern.

```php
namespace Tribe\Plugin\MyFeature;

use Tribe\Plugin\Core\Abstract_Subscriber;

class My_Feature_Subscriber extends Abstract_Subscriber {

	public function register(): void {
		add_action( 'init', function (): void {
			$this->container->get( My_Service::class )->run();
		}, 10, 0 );

		add_filter( 'some_filter', function ( $value ) {
			return $this->container->get( My_Service::class )->filter( $value );
		}, 10, 1 );
	}

}
```

Larger subscribers may split related hooks into private/public helper methods that each
call `add_action` / `add_filter` with the same closure style (see
`Training_Subscriber`, `Page_Subscriber`), but the callback itself still resolves work
through `$this->container->get( ... )`.

Register in `Core.php` `$subscribers` array:

```php
My_Feature_Subscriber::class,
```

Mirror nearest existing subscribers under `wp-content/plugins/core/src/**/*_Subscriber.php`.
## Architecture

- **Subscribers** register hooks only. Use closures that resolve services:
  `$this->container->get( Service::class )->do()` — not `[ $this, 'method' ]`.
- **Controllers** prepare template data. Extend `Abstract_Controller` (or
  `Abstract_Block_Controller` for blocks); build with `Controller::factory([...])`
  (resolves through PHP-DI). Expose intent-revealing accessors
  (`has_media()`, `get_media()`, `get_title()`).
- **Render/template files** stay thin: factory a controller, compute wrapper attributes,
  simple loops/conditionals, escaped markup. No business logic here.
- **Config classes** hold configuration; **models** (`Post_Object` subclasses) hold a
  `NAME` constant and post data access.
- Keep each unit single-purpose: a `*validate*` method only validates — it must not also
  normalize or mutate. Extract repeated behavior into a reusable service/controller method
  or focused trait that matches existing structure (e.g. reuse
  `Tribe\Plugin\Components\Traits\Primary_Term` for primary-term lookups).

### Custom Post Types

- Model new CPTs on existing custom CPTs, especially `src/Post_Types/Training` — not on
  Page or Post.
- Use the trio: `Post_Object` subclass with a `NAME` constant; `Config` extending the
  post-type config base; a subscriber extending `Post_Type_Subscriber` with
  `protected string $config_class`.

### Query changes

- Put query behavior (`pre_get_posts`, query-var filters, archive/search filtering, query
  loop filters) in the plugin `Query` area. Do not scatter it through render templates,
  theme templates, or CPT classes. A one-off local `WP_Query` may live in a block
  controller when private to that block.
- Prefer the standard loop when template context is needed:

```php
while ( $query->have_posts() ) {
	$query->the_post();
}
```

- Avoid `get_posts()` + custom `foreach` that overrides global `$post`, and avoid
  `setup_postdata()` in such loops. Inside custom result loops, prefer ID-accepting helpers
  (`get_the_title( $post_id )`, `get_the_content( null, false, $post_id )`).

### Integrations

- Third-party code lives in `src/Integrations`. Hooks go in `Integrations_Subscriber`;
  behavior goes in focused classes (ACF, Yoast SEO, Rank Math). Validate values crossing
  the WP boundary (e.g. `if ( ! is_array( $x ) ) { return []; }`), but do not add redundant
  guards where the type is already guaranteed.

### Blocks (PHP)

Block PHP specifics (thin `render.php`, controllers, wrappers) live in
`docs/ai/blocks.md` — read that on block tasks (and this file for general PHP standards).

## Pragmatic OOP & simplicity

**Prefer:** early returns / guard clauses; small focused classes and methods; composition
over inheritance; constructor injection; explicit dependencies; behavior-rich objects;
clear names; simple conditionals.

**Avoid:** nested inline ternaries; blind getter/setter pairs on data-bag classes; God
classes; unnecessary inheritance/interfaces/traits; factories with a single obvious
implementation; abstractions added "just in case"; long mixed-responsibility methods;
hidden side effects; defensive checks that duplicate guarantees the code already has.

**Getters/setters:** intent-revealing accessors on controllers/read-models are the
ModernPress convention and are fine. Avoid auto-generating a `get_`/`set_` pair for every
private field on a class with no behavior — expose only what callers need.

### Add abstraction only if at least one is true

- multiple implementations exist **now**;
- real duplication already exists;
- it clearly improves testability;
- it matches an existing ModernPress convention;
- it creates a genuine architectural boundary;
- a concrete, known (not hypothetical) future extension requires it.

Real precedents: interfaces with multiple impls (`Rule_Interface`,
`Meta_Box_Handler_Interface`) and factories with real polymorphism (`Filter_Factory`,
meta-box handlers). Do not invent new interfaces/factories/DTOs/value objects without a
reason like these.

## Readability: no nested ternaries

Deeply nested inline ternaries are the top thing to eliminate. Replace with early-return
assignment or an extracted, well-named method.

Avoid:

```php
echo esc_attr( $c->get_link_a11y_label() ?: ( $c->has_link_text() ? sprintf( __( '%1$s: %2$s', 'tribe' ), $c->get_link_text(), $c->get_title() ) : sprintf( __( 'Read more: %s', 'tribe' ), $c->get_title() ) ) );
```

Prefer:

```php
$label = $c->get_link_a11y_label();

if ( ! $label && $c->has_link_text() ) {
	$label = sprintf( __( '%1$s: %2$s', 'tribe' ), $c->get_link_text(), $c->get_title() );
}

if ( ! $label ) {
	$label = sprintf( __( 'Read more: %s', 'tribe' ), $c->get_title() );
}

echo esc_attr( $label );
```

Better still, move that logic into a controller method so the template just echoes one
escaped value.

## Error handling & escaping

- Guard clauses and early returns over deep nesting. Return `null` / `false` / `[]` for
  absent data rather than throwing in template paths.
- Handle WP fallibility explicitly (`is_wp_error()`, `empty()`), and escape every dynamic
  value at the point of output using the correct `esc_*` function for the context.
- `// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped` only for markup that
  is already escaped/trusted (wrapper attributes, rendered inner blocks).

## Complexity limits (guidelines)

- Method length: aim for ~30 lines; extract when a method grows past one clear job.
- Nesting depth: ~2 levels; use guard clauses to flatten.
- Ternaries: avoid nested; a single simple `?:` / `??` is fine.
- Parameters: approximately ~4 params; pass an args array or object beyond that (matches `factory([...])`).
- Class size: if a class needs "and" to describe it, split it.

## PHPCS / PHPStan

- Config: `phpcs.xml.dist`
- PHP version: 8.4
- Indentation: tabs (tab-width 4)
- Scanned paths: `plugins/core`, `themes/core`, `mu-plugins`
- Excluded: `vendor/`, `tests/`, `*-config.php`

```bash
lando composer phpcs          # check
lando composer phpcbf         # auto-fix
lando composer phpstan        # static analysis (level 5)
```

Scoped runs are OK for narrow edits. Never change dependencies without explicit approval.

## Before proposing PHP as done

- [ ] Follows an existing `core` pattern; adds no ungrounded abstraction
- [ ] No nested inline ternaries; early returns; simple conditionals
- [ ] Classes/methods single-purpose with clear names
- [ ] Everything typed; all output escaped; subscriber hooks use container closures (not `[ $this, '…' ]`)
- [ ] Controllers hold logic; templates only present escaped data
- [ ] Comments are sparse, short, and only where code is not self-explanatory (no comment walls)
- [ ] `lando composer phpcs` and `lando composer phpstan` pass (scoped OK)

This file (`docs/ai/php.md`) is the framework-shipped source of truth for ModernPress PHP.
