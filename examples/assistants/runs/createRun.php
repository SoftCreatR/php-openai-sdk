<?php

/*
 * [License Information]
 */

require_once __DIR__ . '/../../OpenAIFactory.php';

// Call the createRun method with thread ID and options and custom headers.
OpenAIFactory::request(
    'createRun',
    ['thread_id' => 'thread_abc123'],
    [
        'assistant_id' => 'asst_abc123',
        'customHeaders' => ['OpenAI-Beta' => 'assistants=v2'],
    ]
);
