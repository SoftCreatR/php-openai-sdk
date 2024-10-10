<?php

/*
 * [License Information]
 */

require_once __DIR__ . '/../../OpenAIFactory.php';

// Call the submitToolOutputsToRun method with thread ID, run ID, and options and custom headers.
OpenAIFactory::request(
    'submitToolOutputsToRun',
    [
        'thread_id' => 'thread_abc123',
        'run_id' => 'run_abc123',
    ],
    [
        'tool_outputs' => [
            [
                'tool_call_id' => 'call_001',
                'output' => '70 degrees and sunny.',
            ],
        ],
        'customHeaders' => ['OpenAI-Beta' => 'assistants=v2'],
    ]
);
