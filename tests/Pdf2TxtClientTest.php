<?php
/*
 * Copyright 2024 Code Inc. <https://www.codeinc.co>
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CodeInc\Pdf2TxtClient\Tests;

use CodeInc\Pdf2TxtClient\ConvertOptions;
use CodeInc\Pdf2TxtClient\Exception;
use CodeInc\Pdf2TxtClient\Format;
use CodeInc\Pdf2TxtClient\Pdf2TxtClient;
use JsonException;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\StreamInterface;

final class Pdf2TxtClientTest extends TestCase
{
    private const string DEFAULT_PDF2TEXT_BASE_URL = 'http://localhost:3000';
    private const string TEST_PDF_PATH = __DIR__.'/assets/file.pdf';
    private const string TEST_PDF_RESULT_TXT = __DIR__.'/assets/file.txt';
    private const string TEST_PDF_RESULT_JSON = __DIR__.'/assets/file.json';

    /**
     * @throws Exception|JsonException
     */
    public function testConvertLocalFileToText(): void
    {
        $stream = $this->getNewClient()->convertLocalFile(self::TEST_PDF_PATH);
        $this->assertInstanceOf(StreamInterface::class, $stream, "The stream is not valid");

        $text = (string)$stream;
        $this->assertNotEmpty($text, "The stream is empty");
        $this->assertStringEqualsFile(self::TEST_PDF_RESULT_TXT, $text, "The text is not valid");
    }

    /**
     * @throws Exception|JsonException
     */
    public function testConvertLocalFileToRawJson(): void
    {
        $client = $this->getNewClient();
        $stream = $client->convertLocalFile(self::TEST_PDF_PATH, new ConvertOptions(format: Format::json));
        $this->assertInstanceOf(StreamInterface::class, $stream, "The stream is not valid");

        $rawJson = (string)$stream;
        $this->assertJson($rawJson, "The JSON is not valid");
        $this->assertStringEqualsFile(self::TEST_PDF_RESULT_JSON, $rawJson, "The JSON is not valid");
    }

    /**
     * @throws Exception|JsonException
     */
    public function testConvertLocalFileToProcessedJson(): void
    {
        $client = $this->getNewClient();
        $stream = $client->convertLocalFile(self::TEST_PDF_PATH, new ConvertOptions(format: Format::json));
        $this->assertInstanceOf(StreamInterface::class, $stream, "The stream is not valid");

        $json = $client->processJsonResponse($stream);
        $this->assertIsArray($json, "The processed JSON is not valid");

        $expectedJson = json_decode(file_get_contents(self::TEST_PDF_RESULT_JSON), true);
        $this->assertArrayIsEqualToArrayOnlyConsideringListOfKeys(
            $json,
            $expectedJson,
            ["meta", "pages"]
        );
    }

    private function getNewClient(): Pdf2TxtClient
    {
        $apiBaseUrl = self::DEFAULT_PDF2TEXT_BASE_URL;
        if (defined('PDF2TEXT_BASE_URL')) {
            $apiBaseUrl = constant('PDF2TEXT_BASE_URL');
        }
        return new Pdf2TxtClient($apiBaseUrl);
    }
}