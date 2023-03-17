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

// Call the listModels method
try {
    $response = $openAI->listModels();

    // Check if the response has a 200 status code (success)
    if ($response->getStatusCode() === 200) {
        // Decode the response body
        $models = \json_decode($response->getBody(), true, 512, \JSON_THROW_ON_ERROR);

        // Print the list of models
        echo "============\n| Response |\n============\n\n";
        echo \json_encode($models, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
    } else {
        // In case of a non-200 status code, print the response body
        echo "Error: {$response->getBody()}\n";
    }
} catch (Exception $e) {
    // Handle any exceptions during the API call
    echo "Error: {$e->getMessage()}\n";
}