# pdf2txt PHP client

This repository contains a PHP 8.2+ library for converting PDF files to text using the [pdf2txt](https://github.com/codeinchq/pdf2txt) service.

## Installation

The recommended way to install the library is through [Composer](http://getcomposer.org):

```bash
composer require codeinc/pdf2txt-client
```

## Usage

This client requires a running instance of the [pdf2txt](https://github.com/codeinchq/pdf2txt) service. The service can be run locally [using Docker](https://hub.docker.com/r/codeinchq/pdf2txt) or deployed to a server.

### Extracting text from a local file:
```php
use CodeInc\Pdf2TxtClient\Pdf2TxtClient;
use CodeInc\Pdf2TxtClient\Exception;

$apiBaseUri = 'http://localhost:3000/';
$localPdfPath = '/path/to/local/file.pdf';

try {
    // convert
    $client = new Pdf2TxtClient($apiBaseUri);
    $stream = $client->extract($localPdfPath);
    
    // display the text
    echo (string)$stream;
}
catch (Exception $e) {
    // handle exception
}
```

### Extracting text from a stream:
```php
use CodeInc\Pdf2TxtClient\Pdf2TxtClient;
use CodeInc\Pdf2TxtClient\Exception;

$apiBaseUri = 'http://localhost:3000/';
$pdfStream = '...'; // an instance of `Psr\Http\Message\StreamInterface`

try {
    // convert
    $client = new Pdf2TxtClient($apiBaseUri);
    $textStream = $client->extract($pdfStream);
    
    // display the text
    echo (string)$textStream;
}
catch (Exception $e) {
    // handle exception
}
```

### With additional options:
```php
use CodeInc\Pdf2TxtClient\Pdf2TxtClient;
use CodeInc\Pdf2TxtClient\ConvertOptions;
use CodeInc\Pdf2TxtClient\Format;

$apiBaseUri = 'http://localhost:3000/';
$localPdfPath = '/path/to/local/file.pdf';
$convertOption = new ConvertOptions(
    firstPage: 2,
    lastPage: 3,
    format: Format::json
);

try {
    // convert 
    $client = new Pdf2TxtClient($apiBaseUri);
    $jsonResponse = $client->extractFromLocalFile($localPdfPath, $convertOption);
    $decodedJson = $client->processJsonResponse($jsonResponse);
    
   // display the text in a JSON format
   var_dump($decodedJson); 
}
catch (Exception $e) {
    // handle exception
}
```

## License

The library is published under the MIT license (see [`LICENSE`](LICENSE) file).