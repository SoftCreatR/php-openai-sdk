<?php

/*
 * [License Information]
 */

require_once __DIR__ . '/../../OpenAIFactory.php';

// Call the createThreadAndRun method with options and custom headers.
OpenAIFactory::request(
    'createThreadAndRun',
    [],
    [
        'assistant_id' => 'asst_abc123',
        'thread' => [
            'messages' => [
                [
                    'role' => 'user',
                    'content' => 'Explain deep learning to a 5 year old.',
                ],
            ],
        ],
        'customHeaders' => ['OpenAI-Beta' => 'assistants=v2'],
    ]
);
