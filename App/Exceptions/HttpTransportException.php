<?php

namespace App\Exceptions;

use RuntimeException;

class HttpTransportException extends RuntimeException {
	public function __construct(
		string      $message  = 'Transport error during HTTP request',
		int         $code     = 0,
		?\Throwable $previous = null
	) {
		parent::__construct( $message, $code, $previous );
	}
}
