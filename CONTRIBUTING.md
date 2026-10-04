# Contributing to NativeRAG

Thank you for considering contributing to **NativeRAG**! We appreciate bug reports, feature suggestions, documentation improvements, and code contributions.

## Code of Conduct

Please be respectful and considerate in all interactions within this project.

## Getting Started

1. Fork and clone the repository:

   ```bash
   git clone https://github.com/hamdyelbatal122/nativerag.git
   cd nativerag
   ```

2. Install dependencies via Composer:

   ```bash
   composer install
   ```

3. Run the test suite:

   ```bash
   composer test
   ```

## Development Workflow

Before submitting a pull request, please make sure your changes pass all quality checks:

- **Run tests:**
  ```bash
  composer test
  ```

- **Format code (Laravel Pint):**
  ```bash
  composer lint
  ```

- **Run static analysis (PHPStan):**
  ```bash
  composer analyse
  ```

## Pull Request Guidelines

- **Focus**: Keep each pull request focused on a single bug fix or feature.
- **Tests**: Include automated tests covering new features or bug fixes.
- **Documentation**: Update `README.md` or configuration docblocks if your change adds or alters functionality.
- **Branching**: Create a meaningful branch name (e.g., `fix/sqlite-reconnection` or `feat/vector-search-helper`) rather than working directly on `master`.
