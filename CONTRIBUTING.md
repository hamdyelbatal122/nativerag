# Contributing

Contributions are **welcome** and will be fully **credited**.

Please read and understand the contribution guide before creating an issue or pull request.

## Etiquette

This project is open source, and as such, the maintainers give their free time to build and maintain the source code held within. They make the code freely available in the hope that it will be of use to other developers.

## Development

To get started, simply clone the repository, install dependencies using Composer, and run tests.

```bash
git clone git@github.com:hamzi/nativerag.git
cd nativerag
composer install
composer test
```

## Pull Requests

- **PSR-12 Coding Standard** - The easiest way to apply the conventions is to install PHP CS Fixer.
- **Add tests!** - Your patch won't be accepted if it doesn't have tests.
- **Document any change in behaviour** - Make sure the `README.md` and any other relevant documentation are kept up-to-date.
- **Create feature branches** - Don't ask us to pull from your master branch.
- **One pull request per feature** - If you want to do more than one thing, send multiple pull requests.

## Running Tests

We use PHPUnit.

```bash
composer test
```
