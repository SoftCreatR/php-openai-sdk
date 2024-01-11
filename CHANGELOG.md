# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.1.1] - 2024-01-11

### Added

- Added missing properties to OpenAI class (apiKey, organization, origin)
- Fixed spelling of "organization"
- Sensitive Parameter value redaction (API Key)

## [2.1.0] - 2023-11-06

### Added

- Added support for the new createSpeech-endpoint (https://platform.openai.com/docs/api-reference/audio/createSpeech)
- Added support for the new fine-tuning-endpoints (https://platform.openai.com/docs/api-reference/fine-tuning)
- Marked fine-tunes-endpoints as deprecated (soft)
- Updated existing examples

## [2.0.0] - 2023-03-28

### Added

- Added support for any PSR-17 and PSR-18 compatible HTTP client and factory.
- Refactored all examples to use the updated `OpenAI` class.
- Added `$origin` parameter to allow overriding the default origin (api.openai.com) if necessary.

### Changed

- Removed Guzzle dependency from the project (it's still there, but just as dev-dependency for the examples, and for the unit tests)
- Removed the singleton pattern from the `OpenAI` class.
- Major refactoring of the `OpenAI` class to support any PSR-17 and PSR-18 compatible HTTP client and factory.
- Refactored and optimized the test cases in `OpenAITest`.
- Optimized the `OpenAIException` class.
- Updated the README.md to reflect changes in the project structure and requirements.

### Removed

The individual methods `createChatCompletion` and `createCompletion` have been eliminated to decrease the overall complexity.
Although these methods can still be invoked, it is now necessary to explicitly set the `method` option.

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
