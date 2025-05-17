<?php

namespace App\Exceptions;

use RuntimeException;

class HttpResponseException extends RuntimeException {
	public function __construct(
		public readonly int     $statusCode,
		public readonly ?string $body     = null,
        string                  $message  = 'Unexpected HTTP response',
        ?\Throwable             $previous = null
	) {
		parent::__construct( "$message (HTTP $statusCode)", $statusCode, $previous );
	}
}
