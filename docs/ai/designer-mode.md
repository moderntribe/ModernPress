# Designer Mode – Agent Reference

This document is loaded when `user_role = designer` in `local-config.json`. Apply all rules below silently for the entire session.

## Allowed File Changes

These files may be edited without asking for confirmation:

- `theme.json`
- Any `*.pcss` or `*.css` file
- `style.css`
- Component/part `.html` template files under `wp-content/themes/core/parts/` and `wp-content/themes/core/templates/`
- `wp-content/themes/core/assets/fonts/**` (add, remove, replace)
- `wp-content/themes/core/assets/images/**` (add, remove, replace)

## Files That Require Explicit Confirmation

Stop and ask before touching any of these:

- Any `.php` file
- Any `.js` file
- `block.json`
- `composer.json`, `package.json`, `package-lock.json`
- Any file outside `wp-content/themes/core/`

## Git Workflow (enforced every session)

Follow these steps in order. Explain each step in plain English — no git jargon.

1. **Pull latest.** Before doing any work, pull the latest changes from `main`:
   > "I'm updating your local copy with the latest changes from the team."
   ```bash
   git checkout main
   git pull origin main
   ```

2. **Create a branch.** Before making any file changes, create and switch to a new branch named after the work (lowercase, hyphens):
   > "I'm creating a private workspace for your changes — this keeps main safe."
   ```bash
   git checkout -b design/<short-description>
   ```
   Example branch names: `design/update-hero-colors`, `design/new-fonts`

3. **Make changes.** Only edit files in the Allowed list above. Explain what you're changing in plain English as you go.

4. **Commit.** After all changes are complete:
   > "I'm saving a snapshot of your changes."
   ```bash
   git add <allowed-files-only>
   git commit -m "design: <plain-English summary>"
   ```

5. **Push the branch.**
   > "I'm uploading your changes to GitHub so the developer team can see them."
   ```bash
   git push -u origin <branch-name>
   ```

6. **Create a PR.**
   > "I'm opening a pull request — a formal request for the developers to review and merge your design changes."
   ```bash
   gh pr create --title "<plain-English title>" --body "<summary of every file changed and why>"
   ```

## Regression Safety

Before every commit, run this checklist:

1. List every file that was modified in this session.
2. Mark each file as ✅ (in the Allowed list) or ⚠️ (requires confirmation).
3. For any ⚠️ file: stop and ask the designer whether to keep or revert it.
4. Do not proceed with `git add` until the designer has confirmed or out-of-scope files are reverted.

If the designer says "revert it":
```bash
git checkout -- <file>
```
