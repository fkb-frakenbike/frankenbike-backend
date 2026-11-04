# Instructions for coding agents

Read [CONTRIBUTING.md](CONTRIBUTING.md) first: branches, PR size, release flow. This file adds the rules agents must follow.

## TDD is required for the API (requested, not optional)

1. Any change of API behaviour (route, access rule, validation, serialization) starts with a failing test.
2. Run it and read the failure: it must fail for the expected reason. Then write the minimum code to make it pass, then refactor.
3. A bug fix starts with a test that reproduces the bug (red), then the fix (green).
4. In the PR, commit the failing test (`test: ...`) before the code that makes it pass, when practical.
5. Exempt: documentation, pure configuration, behaviour-neutral renames, schema-only migrations.
6. Do not retrofit tests on untouched legacy code; test what you change.
7. Tests live in `tests/Unit` and `tests/Integration` (phpunit, `WebTestCase`, Foundry for data). Run the suite before opening a PR; CI must be green.

## Working rules

- One autonomous change per PR, revertable alone. Base branch: `develop` (`main` only for a hotfix).
- Never commit secrets or `.env` values. Never push to `main` or `develop` directly.
- Do not use cherry-pick to move work between `develop` and `main`.
