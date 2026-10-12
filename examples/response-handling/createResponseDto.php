<?php

/*
 * Copyright (c) 2023-present, Sascha Greuel and Contributors
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

require_once __DIR__ . '/../OpenAIFactory.php';

use SoftCreatR\OpenAI\Response\ModelResponse;

$openAI = OpenAIFactory::create($_ENV['OPENAI_API_KEY'] ?? '');
$httpResponse = $openAI->createResponse([
    'model' => 'gpt-6-luna',
    'input' => 'Give me a one-sentence summary of PSR-18.',
    'reasoning' => ['effort' => 'none'],
]);

if ($httpResponse === null) {
    throw new RuntimeException('No HTTP response was returned.');
}

$response = ModelResponse::fromResponse($httpResponse);

echo json_encode([
    'id' => $response->id,
    'model' => $response->model,
    'status' => $response->status,
    'request_id' => $response->getRequestId(),
    'text' => $response->getOutputText(),
    'refusals' => $response->getRefusals(),
    'usage' => $response->usage,
], JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR) . PHP_EOL;
