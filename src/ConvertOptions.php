<?php
/*
 * Copyright 2024 Code Inc. <https://www.codeinc.co>
 *
 * Use of this source code is governed by an MIT-style
 * license that can be found in the LICENSE file or at
 * https://opensource.org/licenses/MIT.
 */

declare(strict_types=1);

namespace CodeInc\Pdf2TextClient;

/**
 * pdf2text convert options.
 *
 * @see https://github.com/codeinchq/pdf2text?tab=readme-ov-file#usage
 */
final readonly class ConvertOptions
{
    public function __construct(
        public int $firstPage = 1,
        public int|null $lastPage = null,
        public string|null $password = null,
        public bool $normalizeWhitespace = true,
        public Format $format = Format::text,
    ) {
    }
}