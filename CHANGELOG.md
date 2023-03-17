# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

## [1.1.0] - 2023-03-17

### Added

- `setProxy()` method added to the `OpenAI` class, allowing users to set a custom proxy for the underlying HTTP client.
- New `TestHelper` class to simplify test code and improve readability. Includes methods to work with private properties and methods using Reflection, as well as a method to load response files for testing purposes.
- Unit tests and optimizations for the new features in `OpenAITest.php`.

### Changed

- Optimized several test methods in `OpenAITest.php` by leveraging the newly created `TestHelper` class.

## [1.0.0] - 2023-03-16

### Added

- Initial release of the OpenAI PHP library.
- Basic implementation for making API calls to the OpenAI API.
- Unit tests for the initial implementation.
