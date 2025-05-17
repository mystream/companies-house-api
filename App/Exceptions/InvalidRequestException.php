<?php

namespace App\Exceptions;

use InvalidArgumentException;

class InvalidRequestException extends InvalidArgumentException {
	public function __construct( string $message = 'Invalid request parameters', int $code = 0 ) {
		parent::__construct($message, $code);
	}
}
