# ModernPress – Agent Instructions

ModernPress WordPress framework. PHP 8.4. Full-site editing theme with custom Gutenberg blocks. Maintained by Modern Tribe.

## Key Paths

```
Theme:       wp-content/themes/core/
Blocks:      wp-content/themes/core/blocks/{core,tribe,outermost}/
Components:  wp-content/themes/core/components/
Plugin:      wp-content/plugins/core/src/
Mu-plugins:  wp-content/mu-plugins/
Plugins:     wp-content/plugins/
Assets:      wp-content/themes/core/assets/
```

## Stack

- PHP deps: Composer (`lando composer <cmd>` in Lando, or `composer <cmd>` directly)
- JS/CSS deps + build: NPM (`npm run build` / `npm run dev`)
- Local env: Lando (`lando start`, `lando wp`, `lando db-import`)

## Persona Governance

A user persona must be defined in local-config.json before proceeding further. Never proceed without first following the steps below.

1. Read `local-config.json` at the repo root.
2. If `user_role` key is present, apply that persona's rules silently.
3. If absent or unreadable, you must ask before proceeding: *"Are you working as a developer or designer? (Set `user_role` in `local-config.json` to skip this question.)"*
4. Once the user answers the question, add the new `user_role` key with value to local-config.json.

Valid values: `developer`, `designer`

## Domain Doc: Read File Triggers

| Trigger | Action | Examples |
|---------|--------|--------|
| Any block task | Read `docs/ai/blocks.md` | "Create a new custom block", "Add a new control to the paragraph block" |
| Any PHP task | Read `docs/ai/php.md` | "Add a new plugin integration", "Create a new ACF global settings group for admins" |
| Run / build / lint / test | Read `docs/ai/commands.md` | "Rebuild frontend assets", "lint the latest code changes before committing" |
| Any CSS task | Read `docs/ai/css.md` | "Add a new class to the heading block" | "Add extra margin above this element" |
| `user_role = designer` | Read `docs/ai/designer-mode.md` | |

## Hard Rules

- Never edit `vendor/`, `node_modules/`, or `dist/` directly
- Run linting before considering any task done (`npm run lint`, `lando composer phpcs`)
- When fixing a build/CI/deploy error, verify the fix locally (run the relevant build/lint/test) and confirm CI/the deploy goes green yourself — don't hand verification back to the user
- Use `lando wp` for WP-CLI commands — never bare `wp`
- Do not add composer or npm packages without asking first
- Match existing block naming conventions (block folder name = block slug)

## Critical Gotchas

A collection of gotchas, critical project constraints, helpful syntax tips and other notes to help future agents.

### Recursive Improvement

When encountering a new issue that has missing documentation, extended run time, extended tool calls or lookups, recommend to the user a concise addition to this add to the "Critical Gotchas" section.
