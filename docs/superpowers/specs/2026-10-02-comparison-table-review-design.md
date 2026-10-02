# Comparison Table Review Follow-up

## Goal

Address Dave's review feedback on PR #363 by keeping the existing comparison
table behavior while avoiding the Swiper bundle on pages where carousel mode is
not enabled and making the relevant CSS easier to read.

## Design

- Remove the comparison table's automatic `viewScript` registration from
  `block.json`.
- Enqueue its generated `view.js` and `view.asset.php` only on the public
  frontend when the rendered block has mobile carousel enabled. Keep the
  existing script and Swiper initialization unchanged.
- Flatten nested CSS at-rules/selectors in the reviewed layout and carousel
  rules without changing their matching elements, specificity, or behavior.
  Keep mode-qualified selectors where they are required to scope styles to an
  opted-in block variant.

## Validation

Build the theme assets and run the targeted CSS and JavaScript linters. Confirm
that the frontend script is enqueued for carousel markup and omitted when
carousel mode is disabled.
