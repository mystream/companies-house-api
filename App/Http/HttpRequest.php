<?php

namespace App\Http;

readonly class HttpRequest {
	public function __construct(
		public string         $url,
		public HttpMethod     $method = HttpMethod::GET,
		public array          $params = [],
		public ResponseFormat $format = ResponseFormat::JSON,
		public ?string        $accept = 'application/json'
	) {}
}
