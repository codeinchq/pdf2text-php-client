<?php
/*
 * Copyright 2024 Code Inc. <https://www.codeinc.co>
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CodeInc\Pdf2TxtClient;

use Http\Discovery\Psr17FactoryDiscovery;
use Http\Discovery\Psr18ClientDiscovery;
use Http\Message\MultipartStream\MultipartStreamBuilder;
use JsonException;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Class Pdf2TxtClient provides a simple way to interact with the Pdf2Txt API.
 *
 * @package CodeInc\Pdf2TxtClient
 * @link    https://github.com/codeinchq/pdf2txt
 * @link    https://github.com/codeinchq/pdf2txt-php-client
 * @license https://opensource.org/licenses/MIT MIT
 */
readonly class Pdf2TxtClient
{
    public ClientInterface $client;
    public StreamFactoryInterface $streamFactory;
    public RequestFactoryInterface $requestFactory;

    /**
     * Pdf2TxtClient constructor.
     *
     * @param string $baseUrl
     * @param ClientInterface|null $client
     * @param StreamFactoryInterface|null $streamFactory
     * @param RequestFactoryInterface|null $requestFactory
     */
    public function __construct(
        private string $baseUrl,
        ClientInterface|null $client = null,
        StreamFactoryInterface|null $streamFactory = null,
        RequestFactoryInterface|null $requestFactory = null,
    ) {
        $this->client = $client ?? Psr18ClientDiscovery::find();
        $this->streamFactory = $streamFactory ?? Psr17FactoryDiscovery::findStreamFactory();
        $this->requestFactory ??= $requestFactory ?? Psr17FactoryDiscovery::findRequestFactory();
    }

    /**
     * Converts a PDF to text using streams and the PDF2TEXT API.
     *
     * @param StreamInterface|resource|string $stream The PDF content.
     * @param ConvertOptions $options                 The convert options.
     * @return StreamInterface
     * @throws Exception
     */
    public function extract(mixed $stream, ConvertOptions $options = new ConvertOptions()): StreamInterface
    {
        try {
            // building the multipart stream
            $multipartStreamBuilder = (new MultipartStreamBuilder($this->streamFactory))
                ->addResource(
                    'file',
                    $stream,
                    [
                        'filename' => 'file.pdf',
                        'headers'  => ['Content-Type' => 'application/pdf']
                    ]
                )
                ->addResource('firstPage', (string)$options->firstPage)
                ->addResource('normalizeWhitespace', (string)$options->normalizeWhitespace)
                ->addResource('format', $options->format->name);

            if ($options->lastPage !== null) {
                $multipartStreamBuilder->addResource('lastPage', (string)$options->lastPage);
            }
            if ($options->password !== null) {
                $multipartStreamBuilder->addResource('password', (string)$options->password);
            }

            // sending the request
            $response = $this->client->sendRequest(
                $this->requestFactory
                    ->createRequest("POST", $this->getEndpointUri('/extract'))
                    ->withHeader(
                        "Content-Type",
                        "multipart/form-data; boundary={$multipartStreamBuilder->getBoundary()}"
                    )
                    ->withBody($multipartStreamBuilder->build())
            );
        } catch (ClientExceptionInterface $e) {
            throw new Exception(
                message: "An error occurred while sending the request to the PDF2TEXT API",
                code: Exception::ERROR_REQUEST,
                previous: $e
            );
        }

        // checking the response
        if ($response->getStatusCode() !== 200) {
            throw new Exception(
                message: "The PDF2TEXT API returned an error {$response->getStatusCode()}",
                code: Exception::ERROR_RESPONSE,
                previous: new Exception((string)$response->getBody())
            );
        }

        // returning the response
        return $response->getBody();
    }

    /**
     * Processes a JSON response from the PDF2TEXT API.
     *
     * @param StreamInterface $response
     * @return array
     * @throws JsonException
     */
    public function processJsonResponse(StreamInterface $response): array
    {
        return json_decode(
            json: (string)$response,
            associative: true,
            flags: JSON_THROW_ON_ERROR
        );
    }

    /**
     * Returns an endpoint URI.
     *
     * @param string $endpoint
     * @return string
     */
    private function getEndpointUri(string $endpoint): string
    {
        $url = $this->baseUrl;
        if (str_ends_with($url, '/')) {
            $url = substr($url, 0, -1);
        }
        if (str_starts_with($endpoint, '/')) {
            $endpoint = substr($endpoint, 1);
        }

        return "$url/$endpoint";
    }

    /**
     * Health check to verify the service is running.
     *
     * @return bool Health check response, expected to be "ok".
     */
    public function checkServiceHealth(): bool
    {
        try {
            $response = $this->client->sendRequest(
                $this->requestFactory->createRequest(
                    "GET",
                    $this->getEndpointUri("/health")
                )
            );

            // The response status code should be 200
            if ($response->getStatusCode() !== 200) {
                return false;
            }

            // The response body should be {"status":"up"}
            $responseBody = json_decode((string)$response->getBody(), true);
            return isset($responseBody['status']) && $responseBody['status'] === 'up';
        } catch (ClientExceptionInterface) {
            return false;
        }
    }
}
