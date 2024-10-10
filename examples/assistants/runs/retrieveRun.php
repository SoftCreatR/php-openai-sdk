<?php

/*
 * [License Information]
 */

require_once __DIR__ . '/../../OpenAIFactory.php';

// Call the retrieveRun method with thread ID and run ID and custom headers.
OpenAIFactory::request(
    'retrieveRun',
    [
        'thread_id' => 'thread_abc123',
        'run_id' => 'run_abc123',
    ],
    ['customHeaders' => ['OpenAI-Beta' => 'assistants=v2']]
);
