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

// POST /oauth/token
OpenAIFactory::request(
    'exchangeX509WorkloadIdentityToken',
    [
        'grant_type' => 'urn:ietf:params:oauth:grant-type:token-exchange',
        'subject_token_type' => 'urn:openai:params:oauth:token-type:x509',
        'identity_provider_id' => $_ENV['OPENAI_IDENTITY_PROVIDER_ID'] ?? '',
        'service_account_id' => $_ENV['OPENAI_SERVICE_ACCOUNT_ID'] ?? '',
    ],
);
