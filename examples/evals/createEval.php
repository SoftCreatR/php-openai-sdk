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

// POST /evals
OpenAIFactory::request(
    'createEval',
    [],
    [
        'name' => 'Sentiment classification',
        'data_source_config' => [
            'type' => 'custom',
            'item_schema' => [
                'type' => 'object',
                'properties' => ['input' => ['type' => 'string']],
                'required' => ['input'],
            ],
        ],
        'testing_criteria' => [[
            'type' => 'label_model',
            'name' => 'Sentiment label',
            'model' => 'gpt-5.4-mini',
            'input' => [[
                'role' => 'user',
                'content' => 'Classify {{item.input}} as positive, neutral, or negative.',
            ]],
            'labels' => ['positive', 'neutral', 'negative'],
            'passing_labels' => ['positive'],
        ]],
    ],
);
