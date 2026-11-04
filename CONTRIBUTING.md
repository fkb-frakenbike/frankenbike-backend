# Contributing

## Branches

| Branch | Role | Deploys to |
|---|---|---|
| `main` | What runs in production. Only receives release merges and hotfixes. | PROD |
| `develop` | Integration branch. Everything on it must be shippable. | STAGING |
| `feature/FKB-<n>-short-name` | One change, cut from `develop`. | - |
| `hotfix/FKB-<n>-short-name` | Urgent production fix, cut from `main`. | - |

`main` and `develop` are protected: no direct push, CI must pass, at least one review.

## Daily flow

1. `git switch develop && git pull`, then `git switch -c feature/FKB-<n>-short-name`.
2. Commit with Conventional Commits and the ticket: `feat(FKB-73): add public routes`. Co-author lines are welcome.
3. Open a PR **to `develop`**. Keep it small and self-contained: one change, its tests, no hidden dependency on another open PR. A reviewer must be able to revert it alone.
4. **Squash merge** into `develop` (one commit per PR, so a revert is a single commit).
5. `develop` is deployed to staging. Migrations run there first. Test on staging.

## Release (`develop` to `main`)

1. Open a PR `develop` -> `main` once staging is validated.
2. Merge it with a **merge commit**. Never squash it, never cherry-pick: both rewrite commit ids, `develop` and `main` then diverge and the next release conflicts.
3. To undo a release: `git revert -m 1 <merge-commit>` (one command, whole release).
4. Merging only part of `develop` is not a release: keep unfinished work out of `develop`, or behind a feature flag.

## Hotfix

1. Cut `hotfix/...` from `main`, PR to `main`, merge commit or squash.
2. Immediately merge `main` back into `develop` so the fix is not lost and the branches do not diverge.

## Database migrations

- One migration per PR that changes the schema, committed with the PR.
- A migration must work on a copy of production data before it is merged to `main` (staging is for that).
- Never edit a migration that has reached `main`; add a new one.

## PR checklist

- [ ] One autonomous change; can be reverted alone
- [ ] Tests added or updated, CI green
- [ ] Migration included if the schema changes
- [ ] No secret or `.env` value committed
