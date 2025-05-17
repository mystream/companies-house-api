<?php

enum HttpMethod: string {
	case GET	= 'GET';
	case HEAD	= 'HEAD';
	case OPTIONS= 'OPTIONS';
	case TRACE	= 'TRACE';
    case POST	= 'POST';
    case PUT	= 'PUT';
    case DELETE	= 'DELETE';
	case PATCH	= 'PATCH';
	case CONNECT= 'CONNECT';
}
