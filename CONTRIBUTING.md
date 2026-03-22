# CONTRIBUTING

Contributions are welcome, and are accepted via pull requests.
Please review these guidelines before submitting any pull requests.

## Process

1. Fork the project
2. Create a new branch
3. Code, test, commit and push
4. Open a pull request detailing your changes. Make sure to follow the [template](.github/PULL_REQUEST_TEMPLATE.md)

## Guidelines

* Please ensure the coding style running `composer code-style`.
* Commits must follow [conventional commits](https://www.conventionalcommits.org/en/v1.0.0/).
    * This is enforced using [Lefthook](https://lefthook.dev) at commit time.
* Send a coherent commit history, making sure each individual commit in your pull request is meaningful.
* You may need to [rebase](https://git-scm.com/book/en/v2/Git-Branching-Rebasing) to avoid merge conflicts.
* Please remember that we follow [SemVer](http://semver.org/).

## Setup

Requirements:

- PHP 8.2+
- NodeJS

Clone your fork, then install the dev dependencies:

```bash
composer install
pnpm install
```

## Lint

Lint your code:

```bash
composer code-style
```

## Tests

Run all tests:

```bash
composer test
```
