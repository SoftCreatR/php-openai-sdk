# OpenAI API Wrapper for PHP

[![Build](https://img.shields.io/github/actions/workflow/status/SoftCreatR/php-openai-sdk/.github/workflows/create-release.yml?branch=main)](https://github.com/SoftCreatR/php-openai-sdk/actions/workflows/create-release.yml) [![Latest Release](https://img.shields.io/packagist/v/SoftCreatR/php-openai-sdk?color=blue&label=Latest%20Release)](https://packagist.org/packages/softcreatr/php-openai-sdk) [![ISC licensed](https://img.shields.io/badge/license-ISC-blue.svg)](./LICENSE.md) [![Plant Tree](https://img.shields.io/badge/dynamic/json?color=brightgreen&label=Plant%20Tree&query=%24.total&url=https%3A%2F%2Fpublic.offset.earth%2Fusers%2Fsoftcreatr%2Ftrees)](https://ecologi.com/softcreatr?r=61212ab3fc69b8eb8a2014f4) [![Codecov branch](https://img.shields.io/codecov/c/github/SoftCreatR/php-openai-sdk)](https://codecov.io/gh/SoftCreatR/php-openai-sdk) [![Code Climate maintainability](https://img.shields.io/codeclimate/maintainability-percentage/SoftCreatR/php-openai-sdk)](https://codeclimate.com/github/SoftCreatR/php-openai-sdk)

This PHP library provides a simple wrapper for the OpenAI API, allowing you to easily integrate the OpenAI API into your PHP projects.


## Features

-   Easy integration with OpenAI API
-   Supports all OpenAI API endpoints
-   Utilizes PSR-17 and PSR-18 compliant HTTP clients, and factories for making API requests

## Requirements

-   PHP 7.4 or higher
-   A PSR-17 HTTP Factory implementation (e.g., [guzzle/psr7](https://github.com/guzzle/psr7) or [nyholm/psr7](https://github.com/Nyholm/psr7))
-   A PSR-18 HTTP Client implementation (e.g., [guzzlehttp/guzzle](https://github.com/guzzle/guzzle) or [symfony/http-client](https://github.com/symfony/http-client))

## Installation

You can install the library via [Composer](https://getcomposer.org/):

```bash
composer require softcreatr/php-openai-sdk
```

## Usage

First, include the library in your project:

```php
<?php

require_once 'vendor/autoload.php';
```

Then, create an instance of the `OpenAI` class with your API key, organization (optional), an HTTP client, an HTTP request factory, and an HTTP stream factory:

```php
use SoftCreatR\OpenAI\OpenAI;

$apiKey = 'your_api_key';
$organisation = 'your_organisation_id'; // optional

// Replace these lines with your chosen PSR-17 and PSR-18 compatible HTTP client and factories
$httpClient = new YourChosenHttpClient();
$requestFactory = new YourChosenRequestFactory();
$streamFactory = new YourChosenStreamFactory();
$uriFactory = new YourChosenUriFactory();

$openAI = new OpenAI($requestFactory, $streamFactory, $uriFactory, $httpClient, $apiKey, $organisation);
```

Now you can call any supported OpenAI API endpoint using the magic method `__call`:

```php
$response = $openAI->listModels();

// Process the API response
if ($response->getStatusCode() === 200) {
    $models = json_decode($response->getBody()->getContents(), true);
    
    print_r($models);
} else {
    echo "Error: " . $response->getStatusCode();
}
```

For more details on how to use each endpoint, refer to the [OpenAI API documentation](https://platform.openai.com/docs/api-reference), and the [examples](https://github.com/SoftCreatR/php-openai-sdk/tree/main/examples) provided in the repository.

## Supported Methods

### Models
-   [List Models](https://platform.openai.com/docs/api-reference/models/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/models/listModels.php)
    -   `listModels()`
-   [Retrieve Model](https://platform.openai.com/docs/api-reference/models/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/models/retrieveModel.php)
    -   `retrieveModel(string $id)`

### Completions
-   [Create Completion](https://platform.openai.com/docs/api-reference/completions/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/completions/createCompletion.php)
    -   `createCompletion(array $options = [])`

### Chat Completions
-   [Create Chat Completion](https://platform.openai.com/docs/api-reference/chat/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/chat/createChatCompletion.php)
    -   `createChatCompletion(array $options = [])`

### Edits
-   [Create Edit](https://platform.openai.com/docs/api-reference/edits/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/edits/createEdit.php)
    -   `createEdit(array $options = [])`

### Images
-   [Create Image](https://platform.openai.com/docs/api-reference/images/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/images/createImage.php)
    -   `createImage(array $options = [])`
-   [Create Image Edit](https://platform.openai.com/docs/api-reference/images/create-edit) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/images/createImageEdit.php)
    -   `createImageEdit(array $options = [])`
-   [Create Image Variation](https://platform.openai.com/docs/api-reference/images/create-variation) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/images/createImageVariation.php)
    -   `createImageVariation(array $options = [])`

### Embeddings
-   [Create Embedding](https://platform.openai.com/docs/api-reference/embeddings/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/embeddings/createEmbedding.php)
    -   `createEmbedding(array $options = [])`

### Audio
-   [Create Transcription](https://platform.openai.com/docs/api-reference/audio/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/audio/createTranscription.php)
    -   `createTranscription(array $options = [])`
-   [Create Translation](https://platform.openai.com/docs/api-reference/audio/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/audio/createTranslation.php)
    -   `createTranslation(array $options = [])`

### Files
-   [List Files](https://platform.openai.com/docs/api-reference/files/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/files/listFiles.php)
    -   `listFiles()`
-   [Create File](https://platform.openai.com/docs/api-reference/files/upload) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/files/createFile.php)
    -   `createFile(array $options = [])`
-   [Delete File](https://platform.openai.com/docs/api-reference/files/delete) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/files/deleteFile.php)
    -   `deleteFile(string $id)`
-   [Retrieve File](https://platform.openai.com/docs/api-reference/files/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/files/retrieveFile.php)
    -   `retrieveFile(string $id)`
-   [Download File](https://platform.openai.com/docs/api-reference/files/retrieve-content) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/files/downloadFile.php)
    -   `downloadFile(string $id)`

### Fine-tunes
-   [Create Fine-tune](https://platform.openai.com/docs/api-reference/fine-tunes/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/fine-tunes/createFineTune.php)
    -   `createFineTune(array $options = [])`
-   [List Fine-tunes](https://platform.openai.com/docs/api-reference/fine-tunes/list) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/fine-tunes/listFineTunes.php)
    -   `listFineTunes()`
-   [Retrieve Fine-tune](https://platform.openai.com/docs/api-reference/fine-tunes/retrieve) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/fine-tunes/retrieveFineTune.php)
    -   `retrieveFineTune(string $id)`
-   [Cancel Fine-tune](https://platform.openai.com/docs/api-reference/fine-tunes/cancel) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/fine-tunes/cancelFineTune.php)
    -   `cancelFineTune(string $id)`
-   [List Fine-tune Events](https://platform.openai.com/docs/api-reference/fine-tunes/events) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/fine-tunes/listFineTuneEvents.php)
    -   `listFineTuneEvents(string $id, array $options = [])`
-   [Delete fine-tune model](https://platform.openai.com/docs/api-reference/fine-tunes/delete-model) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/fine-tunes/deleteModel.php)
    -   `deleteModel(string $id)`

### Moderations
-   [Create moderation](https://platform.openai.com/docs/api-reference/moderations/create) - [Example](https://github.com/SoftCreatR/php-openai-sdk/blob/main/examples/moderations/createModeration.php)
    -   `createModeration(array $options = [])`

## Changelog

For a detailed list of changes and updates, please refer to the [CHANGELOG.md](https://github.com/SoftCreatR/php-openai-sdk/blob/main/CHANGELOG.md) file. We adhere to [Semantic Versioning](https://semver.org/spec/v2.0.0.html) and document notable changes for each release.

## Known Problems and limitations

### Streaming Support
Currently, streaming is not supported in the `createCompletion` and `createChatCompletion` methods. It's planned to address this limitation asap. For now, please be aware that these methods cannot be used for streaming purposes.

If you require streaming functionality, consider using an alternative implementation or keep an eye out for future updates to this library.

## License

This library is licensed under the ISC License. See the [LICENSE](https://github.com/SoftCreatR/php-openai-sdk/blob/main/LICENSE.md) file for more information.

## Maintainers 🛠️

<table>
<tr>
    <td style="text-align:center;word-wrap:break-word;width:150px;height: 150px">
        <a href=https://github.com/SoftCreatR>
            <img src=https://avatars.githubusercontent.com/u/81188?v=4 width="100;" alt="Sascha Greuel"/>
            <br />
            <sub style="font-size:14px"><b>Sascha Greuel</b></sub>
        </a>
    </td>
</tr>
</table>

## Contributors ✨

<table>
<tr>
</tr>
</table>
