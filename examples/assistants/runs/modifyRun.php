<?php

/*
 * [License Information]
 */

require_once __DIR__ . '/../../OpenAIFactory.php';

// Call the modifyRun method with thread ID, run ID, and options and custom headers.
OpenAIFactory::request(
    'modifyRun',
    [
        'thread_id' => 'thread_abc123',
        'run_id' => 'run_abc123',
    ],
    [
        'metadata' => ['user' => 'user-abc123'],
        'customHeaders' => ['OpenAI-Beta' => 'assistants=v2'],
    ]
);
