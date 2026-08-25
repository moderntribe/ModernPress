# Pull Requests & Commit Messages – Agent Reference

Same bar as code comments: short, informative, only what reviewers need.

## PR descriptions

Follow `.github/PULL_REQUEST_TEMPLATE.md` when present. Fill every required section,
but keep each one tight.

- **What does this do/fix?** — A few bullets or 1–3 short sentences. Why + what changed.
  Point at the non-obvious bits; do not narrate the diff line-by-line.
- **QA** — Ticket link(s), plus screenshots/video when UI changed. No fluff.
- **Checklist** — Complete honestly; leave unchecked items that do not apply only if the
  template allows, otherwise note N/A briefly.

**Hard fail:** essay-length PR bodies, AI chat dumps, or padding that restates the entire
diff. If context is long, keep the PR short and put depth in the ticket or a reply in
review — not a wall of markdown in the description.

## Commit messages

- Focus on **why**, not a file laundry list.
- 1–2 short sentences (subject + optional body). Match recent repo style.
- No essay bodies, no pasted chat, no comment novels relocated from the code.

## Done-gate

Do not open or update a PR as "done" until the description passes the brevity bar above
and matches the template structure.
