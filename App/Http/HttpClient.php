<?php

namespace App\Http;

use App\Exceptions\HttpTransportException;
use App\Http\Enums\HttpMethod;
use App\Http\Enums\ResponseFormat;
use App\Http\Attributes\AllowsRequestBody;

readonly class HttpClient {
	public function __construct(
		private string  $apiKey         = '',
		private bool    $verbose        = false,
		private ?string $cookieFile     = null,
		private array   $defaultHeaders = [],
		private array   $defaultOptions = []
	) {}

	public function send( HttpRequest $request ): array|string|null {
		$url   = $request->url;
		$query = http_build_query( $request->params );

		$acceptHeader = $request->accept ?? 'application/json';
		$contentType  = $this->detectContentType( $this->defaultHeaders );

		if ( HttpMethod::GET === $request->method && $request->params ) {
			$url .= '?' . $query;
		}

		$headers = array_merge(
			[ 'Accept: ' . $acceptHeader ],
			$this->defaultHeaders
		);

		$opts = [
			CURLOPT_URL            => $url,
			CURLOPT_RETURNTRANSFER => true,
			CURLOPT_USERPWD        => "{$this->apiKey}:",
			CURLOPT_HTTPHEADER     => $headers,
			CURLOPT_CUSTOMREQUEST  => $request->method->value,
			CURLOPT_FOLLOWLOCATION => true,
		];

		if ( $this->verbose ) {
			$opts[ CURLOPT_VERBOSE ] = true;
		}

		if ( $this->cookieFile ) {
			$opts[ CURLOPT_COOKIEJAR ]  = $this->cookieFile;
			$opts[ CURLOPT_COOKIEFILE ] = $this->cookieFile;
		}

		if ( !empty( $request->params ) && $this->allowsBody( $request->method ) ) {
			if ( 'application/json' === $contentType ) {
				$json = json_encode( $request->params, JSON_THROW_ON_ERROR );
				$opts[ CURLOPT_POSTFIELDS ] = $json;
				$headers[] = 'Content-Type: application/json';
				$headers[] = 'Content-Length: ' . strlen( $json );
			} else {
				$opts[ CURLOPT_POSTFIELDS ] = $query;
			}
		}

		$opts[ CURLOPT_HTTPHEADER ] = $headers;
		$ch = curl_init();
		curl_setopt_array( $ch, array_replace_recursive( $opts, $this->defaultOptions ) );

		$response = curl_exec($ch);
		$status   = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$error    = curl_error($ch);

		curl_close( $ch );

		if ( $error ) {
			throw new HttpTransportException( "cURL error: $error" );
        }

		if ( $status >= 400 ) {
			throw new HttpTransportException( "HTTP error $status from {$url}", $status );
		}

		if ( ResponseFormat::JSON === $request->format ) {
			try {
				return json_decode( $response, true, 512, JSON_THROW_ON_ERROR );
			} catch (\JsonException $e) {
				throw new HttpTransportException( "JSON decode error: " . $e->getMessage(), 0, $e );
			}
		}

		return $response;
    }

	private function allowsBody( HttpMethod $method ): bool {
		$reflection = new \ReflectionEnumUnitCase( HttpMethod::class, $method->name );
		return !empty( $reflection->getAttributes( AllowsRequestBody::class ) );
	}

	private function detectContentType( array $headers ): string {
		foreach ( $headers as $header ) {
			if ( stripos( $header, 'Content-Type:' ) === 0 ) {
				return trim( substr( $header, strlen( 'Content-Type:' ) ) );
			}
		}
		return 'application/x-www-form-urlencoded';
	}
}
