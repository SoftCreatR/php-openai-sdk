<?php

/*
 * Copyright (c) 2023, Sascha Greuel and Contributors
 *
 * Permission to use, copy, modify, and/or distribute this software for any
 * purpose with or without fee is hereby granted, provided that the above
 * copyright notice and this permission notice appear in all copies.
 *
 * THE SOFTWARE IS PROVIDED "AS IS" AND THE AUTHOR DISCLAIMS ALL WARRANTIES
 * WITH REGARD TO THIS SOFTWARE INCLUDING ALL IMPLIED WARRANTIES OF
 * MERCHANTABILITY AND FITNESS. IN NO EVENT SHALL THE AUTHOR BE LIABLE FOR
 * ANY SPECIAL, DIRECT, INDIRECT, OR CONSEQUENTIAL DAMAGES OR ANY DAMAGES
 * WHATSOEVER RESULTING FROM LOSS OF USE, DATA OR PROFITS, WHETHER IN AN
 * ACTION OF CONTRACT, NEGLIGENCE OR OTHER TORTIOUS ACTION, ARISING OUT OF
 * OR IN CONNECTION WITH THE USE OR PERFORMANCE OF THIS SOFTWARE.
 */

require_once __DIR__ . '/../../vendor/autoload.php';

use SoftCreatR\OpenAI\OpenAI;

// Replace 'your-api-key' with your actual OpenAI API key
$apiKey = 'your-api-key';

// Instantiate the OpenAI class
$openAI = OpenAI::getInstance($apiKey);

// Set the model ID you want to delete
$modelId = 'curie:ft-acmeco-2021-03-03-21-44-20';

// Call the deleteModel method
try {
    $response = $openAI->deleteModel($modelId);

    // Check if the response has a 204 status code (success)
    if ($response->getStatusCode() === 200) {
        // Print a success message
        echo "Model (ID: {$modelId}) deleted successfully.\n";
    } else {
        // In case of a non-204 status code, print the response body
        echo "Error: {$response->getBody()}\n";
    }
} catch (Exception $e) {
    // Handle any exceptions during the API call
    echo "Error: {$e->getMessage()}\n";
}
