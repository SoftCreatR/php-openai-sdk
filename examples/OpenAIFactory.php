<?php

/*
 * [License Information]
 */

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use SoftCreatR\OpenAI\OpenAI;
use SoftCreatR\OpenAI\OpenAIURLBuilder;

/**
 * Example factory class for creating and using the OpenAI client.
 */
final class OpenAIFactory
{
    /**
     * OpenAI API Key.
     *
     * @see https://platform.openai.com/docs/api-reference/authentication
     * @var string
     */
    private const OPENAI_API_KEY = 'your_api_key';

    /**
     * OpenAI Admin API Key.
     *
     * @see https://platform.openai.com/docs/api-reference/administration
     * @var string
     */
    private const OPENAI_ADMIN_KEY = 'your_admin_api_key';

    /**
     * OpenAI Organization ID (optional).
     *
     * @var string
     */
    private const ORGANIZATION_ID = '';

    /**
     * Prevents instantiation of this class.
     */
    private function __construct()
    {
        // This class should not be instantiated.
    }

    /**
     * Creates an instance of the OpenAI client.
     *
     * @param string $apiKey The OpenAI API key.
     *
     * @return OpenAI The OpenAI client instance.
     */
    public static function create(
        #[SensitiveParameter]
        string $apiKey = self::OPENAI_API_KEY,
        string $organizationID = self::ORGANIZATION_ID
    ): OpenAI {
        $psr17Factory = new HttpFactory();
        $httpClient = new Client(['stream' => true]);

        return new OpenAI(
            requestFactory: $psr17Factory,
            streamFactory: $psr17Factory,
            uriFactory: $psr17Factory,
            httpClient: $httpClient,
            apiKey: $apiKey,
            organization: $organizationID
        );
    }

    /**
     * Sends a request to the specified OpenAI API endpoint.
     *
     * @param string         $method         The name of the API method to call.
     * @param array          $parameters     An associative array of parameters (URL parameters).
     * @param array          $options        An associative array of options (body or query parameters).
     * @param callable|null  $streamCallback Optional callback function for streaming responses.
     * @param bool           $returnResponse Whether to return the response or not.
     * @param bool           $useAdminKey    Whether to use the OPENAI_ADMIN_KEY.
     *
     * @return mixed
     */
    public static function request(
        string $method,
        array $parameters = [],
        array $options = [],
        ?callable $streamCallback = null,
        bool $returnResponse = false,
        bool $useAdminKey = false
    ): mixed {
        $openAI = self::create($useAdminKey ? self::OPENAI_ADMIN_KEY : self::OPENAI_API_KEY);

        try {
            $endpoint = OpenAIURLBuilder::getEndpoint($method);
            $path = $endpoint['path'];

            // Determine if the path contains placeholders
            $hasPlaceholders = \preg_match('/\{(\w+)}/', $path) === 1;

            if ($hasPlaceholders) {
                $urlParameters = $parameters;
                $bodyOptions = $options;
            } else {
                $urlParameters = [];
                $bodyOptions = $parameters + $options; // Merge parameters and options
            }

            if ($streamCallback !== null) {
                $openAI->{$method}($urlParameters, $bodyOptions, $streamCallback);
            } else {
                $response = $openAI->{$method}($urlParameters, $bodyOptions);

                if ($returnResponse) {
                    return $response->getBody()->getContents();
                }

                $contentType = $response->getHeaderLine('Content-Type');

                if (\str_contains($contentType, 'application/json')) {
                    $result = \json_decode(
                        $response->getBody()->getContents(),
                        true,
                        512,
                        \JSON_THROW_ON_ERROR
                    );

                    echo "============\n| Response |\n============\n\n";
                    echo \json_encode($result, \JSON_THROW_ON_ERROR | \JSON_PRETTY_PRINT);
                    echo "\n\n============\n";
                } else {
                    // Handle other content types if necessary
                    echo "Received response with Content-Type: {$contentType}\n";
                    echo $response->getBody()->getContents();
                }

                return null;
            }
        } catch (Exception $e) {
            echo "Error: {$e->getMessage()}\n";
        }

        return null;
    }

    /**
     * Sends a request to the specified OpenAI API endpoint.
     *
     * @param string         $method         The name of the API method to call.
     * @param array          $parameters     An associative array of parameters (URL parameters).
     * @param array          $options        An associative array of options (body or query parameters).
     * @param callable|null  $streamCallback Optional callback function for streaming responses.
     * @param bool           $returnResponse Whether to return the response or not.
     *
     * @return mixed
     */
    public static function adminRequest(
        string $method,
        array $parameters = [],
        array $options = [],
        ?callable $streamCallback = null,
        bool $returnResponse = false
    ): mixed {
        return self::request(
            $method,
            $parameters,
            $options,
            $streamCallback,
            $returnResponse,
            true
        );
    }
}
