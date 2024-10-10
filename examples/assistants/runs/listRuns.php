<?php

/*
 * [License Information]
 */

require_once __DIR__ . '/../../OpenAIFactory.php';

// Call the listRuns method with thread ID and custom headers.
OpenAIFactory::request(
    'listRuns',
    ['thread_id' => 'thread_abc123'],
    ['customHeaders' => ['OpenAI-Beta' => 'assistants=v2']]
);
