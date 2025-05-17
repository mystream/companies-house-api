<?php

namespace App\Services;

use App\Http\HttpClient;
use App\Http\HttpRequest;
use App\Http\Enums\HttpMethod;
use App\Http\Enums\ResponseFormat;
use App\Exceptions\InvalidRequestException;
use Attribute;

#[Attribute(Attribute::TARGET_METHOD)]
class ApiEndpoint {
    public function __construct(
        public string $path,
        public string $description,
        public array  $queryParams           = [],
        public bool   $requiresCompanyNumber = false
    ) {}
}

readonly class CompaniesHouseService {

	public function __construct(
        private HttpClient     $client,
        private CacheInterface $cache,
        private string         $baseUrl = 'https://api.company-information.service.gov.uk'
    ) {}

    private function cachedRequest( string $cacheKey, HttpRequest $request, int $ttl = 3600 ): array|string|null {
        if ( $result = $this->cache->get( $cacheKey ) ) {
            return $result;
        }

        $result = $this->client->send( $request );
        if ( $result !== null ) {
            $this->cache->set( $cacheKey, $result, $ttl );
        }

        return $result;
    }

	#[ApiEndpoint(
		path		: '/search',
		description	: 'Search across companies, officers, and disqualified officers.',
		queryParams	: [ 'q' ]
	)]
	public function search( array $params ): ?array {
		if ( empty( $params[ 'q' ] ) ) {
			throw new InvalidRequestException( 'Search query parameter "q" is required.' );
		}

		$key = 'search_' . md5( json_encode( $params ) );
		return $this->cachedRequest( $key, new HttpRequest(
			url		: "$this->baseUrl/search",
			method	: HttpMethod::GET,
			params	: $params
		));
	}

    #[ApiEndpoint(
        path		: '/search/companies',
        description	: 'Search for companies by name or number.',
        queryParams	: [ 'q' ]
    )]
	public function searchCompanies( string $query ): ?array {
		return $this->cachedRequest( "search_companies_$query", new HttpRequest(
			url		: "$this->baseUrl/search/companies",
			method	: HttpMethod::GET,
			params	: [ 'q' => $query ]
		));
	}

	#[ApiEndpoint(
		path		: '/search/officers',
		description	: 'Search for company officers by name.',
		queryParams	: [ 'q' ]
    )]
	public function searchOfficers( string $query ): ?array {
		return $this->cachedRequest( "search_officers_$query", new HttpRequest(
			url		: "$this->baseUrl/search/officers",
			method	: HttpMethod::GET,
			params	: [ 'q' => $query ]
		));
	}

	#[ApiEndpoint(
		path		: '/search/disqualified-officers',
		description	: 'Search for disqualified officers.',
		queryParams	: [ 'q' ]
	)]
	public function searchDisqualifiedOfficers( string $query ): ?array {
		return $this->cachedRequest( "search_disqualified_$query", new HttpRequest(
			url		: "$this->baseUrl/search/disqualified-officers",
			method	: HttpMethod::GET,
			params	: [ 'q' => $query ]
		));
	}

	#[ApiEndpoint(
		path		: '/alphabetical-search/companies',
		description	: 'Search companies alphabetically.',
		queryParams	: [ 'q' ]
	)]
	public function alphabeticalSearchCompanies( string $query ): ?array {
		return $this->cachedRequest( "search_alpha_$query", new HttpRequest(
			url		: "$this->baseUrl/alphabetical-search/companies",
			method	: HttpMethod::GET,
			params	: [ 'q' => $query ]
		));
	}

	#[ApiEndpoint(
		path		: '/dissolved-search/companies',
		description	: 'Search for dissolved companies.',
		queryParams	: [ 'q' ]
	)]
	public function dissolvedSearchCompanies( string $query ): ?array {
		return $this->cachedRequest( "search_dissolved_$query", new HttpRequest(
			url		: "$this->baseUrl/dissolved-search/companies",
			method	: HttpMethod::GET,
			params	: [ 'q' => $query ]
		));
	}

	#[ApiEndpoint(
		path		: '/advanced-search/companies',
		description	: 'Perform advanced searches with filters like location, incorporation date, etc.',
		queryParams	: [ 'company_name_includes', 'location', 'incorporated_from', 'incorporated_to' ]
	)]
	public function advancedSearch( array $filters ): ?array {
		$key = 'advanced_' . md5( json_encode( $filters ) );
		return $this->cachedRequest( $key, new HttpRequest(
			url		: "$this->baseUrl/advanced-search/companies",
			method	: HttpMethod::GET,
			params	: $filters
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}',
		description				: 'Retrieve the company profile.',
		requiresCompanyNumber	: true
	)]
	public function getCompany( string $companyNumber ): ?array {
		return $this->cachedRequest( "company_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/registered-office-address',
		description				: 'Get the registered office address.',
		requiresCompanyNumber	: true
	)]
	public function getCompanyRegisteredOffice( string $companyNumber ): ?array {
		return $this->cachedRequest( "company_office_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/registered-office-address",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/officers',
		description				: 'List company officers.',
		requiresCompanyNumber	: true
	)]
	public function getCompanyOfficers( string $companyNumber ): ?array {
		return $this->cachedRequest( "company_officers_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/officers",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/appointments/{appointment_id}',
		description				: 'Get details of a specific officer appointment.',
		requiresCompanyNumber	: true
	)]
	public function getOfficerAppointment( string $companyNumber, string $appointmentId ): ?array {
		return $this->cachedRequest( "officer_appointment_{$companyNumber}_{$appointmentId}", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/appointments/{$appointmentId}",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/filing-history',
		description				: 'List the company\'s filing history.',
        requiresCompanyNumber	: true
	)]
	public function getFilingHistory( string $companyNumber ): ?array {
		return $this->cachedRequest( "filing_history_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/filing-history",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/filing-history/{transaction_id}',
		description				: 'Retrieve a specific filing history item.',
		requiresCompanyNumber	: true
	)]
	public function getFilingHistoryItem( string $companyNumber, string $transactionId ): ?array {
		return $this->cachedRequest( "filing_item_{$companyNumber}_{$transactionId}", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/filing-history/{$transactionId}",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/charges',
		description				: 'List charges (mortgages) registered against the company.',
		requiresCompanyNumber	: true
	)]
	public function getCharges( string $companyNumber ): ?array {
		return $this->cachedRequest( "charges_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/charges",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/charges/{charge_id}',
		description				: 'Get details of a specific charge.',
		requiresCompanyNumber	: true
	)]
	public function getChargeDetails( string $companyNumber, string $chargeId ): ?array {
		return $this->cachedRequest( "charge_detail_{$companyNumber}_{$chargeId}", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/charges/{$chargeId}",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/insolvency',
		description				: 'Retrieve insolvency information.',
		requiresCompanyNumber	: true
	)]
	public function getInsolvency( string $companyNumber ): ?array {
		return $this->cachedRequest( "insolvency_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/insolvency",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/exemptions',
		description				: 'Get information on company exemptions.',
		requiresCompanyNumber	: true
	)]
	public function getExemptions( string $companyNumber ): ?array {
		return $this->cachedRequest( "exemptions_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/exemptions",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/charges',
		description				: 'List charges (mortgages) registered against the company.',
		requiresCompanyNumber	: true
	)]
	public function getCharges( string $companyNumber ): ?array {
		return $this->cachedRequest( "charges_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/charges",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/charges/{charge_id}',
		description				: 'Get details of a specific charge.',
		requiresCompanyNumber	: true
	)]
	public function getChargeDetails( string $companyNumber, string $chargeId ): ?array {
		return $this->cachedRequest( "charge_detail_{$companyNumber}_{$chargeId}", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/charges/{$chargeId}",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/insolvency',
		description				: 'Retrieve insolvency information.',
		requiresCompanyNumber	: true
	)]
	public function getInsolvency( string $companyNumber ): ?array {
		return $this->cachedRequest( "insolvency_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/insolvency",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/exemptions',
		description				: 'Get information on company exemptions.',
		requiresCompanyNumber	: true
	)]
	public function getExemptions( string $companyNumber ): ?array {
		return $this->cachedRequest( "exemptions_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/exemptions",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/registers',
		description				: 'Access the company\'s statutory registers.',
		requiresCompanyNumber	: true
	)]
	public function getRegisters( string $companyNumber ): ?array {
		return $this->cachedRequest( "registers_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/registers",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/uk-establishments',
		description				: 'List UK establishments associated with the company.',
		requiresCompanyNumber	: true
	)]
	public function getUkEstablishments( string $companyNumber ): ?array {
		return $this->cachedRequest( "uk_establishments_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/uk-establishments",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/persons-with-significant-control',
		description				: 'List persons with significant control (PSC).',
		requiresCompanyNumber	: true
	)]
	public function getPSC( string $companyNumber ): ?array {
		return $this->cachedRequest( "psc_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/persons-with-significant-control",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/persons-with-significant-control-statements',
		description				: 'List PSC statements.',
		requiresCompanyNumber	: true
	)]
	public function getPSCStatements( string $companyNumber ): ?array {
		return $this->cachedRequest( "psc_statements_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/persons-with-significant-control-statements",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/persons-with-significant-control/individual/{psc_id}',
		description				: 'Get details of an individual PSC.',
		requiresCompanyNumber	: true
	)]
	public function getIndividualPSC( string $companyNumber, string $pscId ): ?array {
		return $this->cachedRequest( "psc_individual_{$companyNumber}_{$pscId}", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/persons-with-significant-control/individual/{$pscId}",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/charges',
		description				: 'List charges (mortgages) registered against the company.',
        requiresCompanyNumber	: true
	)]
	public function getCharges( string $companyNumber ): ?array {
		return $this->cachedRequest( "charges_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/charges",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/charges/{charge_id}',
		description				: 'Get details of a specific charge.',
		requiresCompanyNumber	: true
	)]
	public function getChargeDetails( string $companyNumber, string $chargeId ): ?array {
		return $this->cachedRequest( "charge_detail_{$companyNumber}_{$chargeId}", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/charges/{$chargeId}",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/insolvency',
		description				: 'Retrieve insolvency information.',
		requiresCompanyNumber	: true
	)]
	public function getInsolvency( string $companyNumber ): ?array {
		return $this->cachedRequest( "insolvency_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/insolvency",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/exemptions',
		description				: 'Get information on company exemptions.',
		requiresCompanyNumber	: true
	)]
	public function getExemptions( string $companyNumber ): ?array {
		return $this->cachedRequest( "exemptions_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/exemptions",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/registers',
		description				: 'Access the company\'s statutory registers.',
		requiresCompanyNumber	: true
	)]
	public function getRegisters( string $companyNumber ): ?array {
		return $this->cachedRequest( "registers_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/registers",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/uk-establishments',
		description				: 'List UK establishments associated with the company.',
		requiresCompanyNumber	: true
	)]
	public function getUkEstablishments( string $companyNumber ): ?array {
		return $this->cachedRequest( "uk_establishments_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/uk-establishments",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/persons-with-significant-control',
		description				: 'List persons with significant control (PSC).',
		requiresCompanyNumber	: true
	)]
	public function getPSC( string $companyNumber ): ?array {
		return $this->cachedRequest( "psc_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/persons-with-significant-control",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/persons-with-significant-control-statements',
		description				: 'List PSC statements.',
		requiresCompanyNumber	: true
	)]
	public function getPSCStatements( string $companyNumber ): ?array {
		return $this->cachedRequest( "psc_statements_$companyNumber", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/persons-with-significant-control-statements",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/persons-with-significant-control/individual/{psc_id}',
		description				: 'Get details of an individual PSC.',
		requiresCompanyNumber	: true
	)]
	public function getIndividualPSC( string $companyNumber, string $pscId ): ?array {
		return $this->cachedRequest( "psc_individual_{$companyNumber}_{$pscId}", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/persons-with-significant-control/individual/{$pscId}",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/persons-with-significant-control/corporate-entity/{psc_id}',
		description				: 'Get details of a corporate PSC.',
		requiresCompanyNumber	: true
	)]
	public function getCorporatePSC( string $companyNumber, string $pscId ): ?array {
		return $this->cachedRequest( "psc_corporate_{$companyNumber}_{$pscId}", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/persons-with-significant-control/corporate-entity/{$pscId}",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/persons-with-significant-control/legal-person/{psc_id}',
		description				: 'Get details of a legal person PSC.',
		requiresCompanyNumber	: true
	)]
	public function getLegalPersonPSC( string $companyNumber, string $pscId ): ?array {
		return $this->cachedRequest( "psc_legal_{$companyNumber}_{$pscId}", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/persons-with-significant-control/legal-person/{$pscId}",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path					: '/company/{company_number}/persons-with-significant-control/super-secure/{super_secure_id}',
        description				: 'Get details of a super secure PSC.',
        requiresCompanyNumber	: true
	)]
	public function getSuperSecurePSC( string $companyNumber, string $secureId ): ?array {
		return $this->cachedRequest( "psc_secure_{$companyNumber}_{$secureId}", new HttpRequest(
			url		: "$this->baseUrl/company/{$companyNumber}/persons-with-significant-control/super-secure/{$secureId}",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path		: '/officers/{officer_id}/appointments',
		description	: 'List appointments for a specific officer.'
	)]
	public function getOfficerAppointments( string $officerId ): ?array {
		return $this->cachedRequest( "officer_appointments_$officerId", new HttpRequest(
			url		: "$this->baseUrl/officers/{$officerId}/appointments",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path		: '/disqualified-officers/natural/{officer_id}',
		description	: 'Get details of a disqualified natural person.'
	)]
	public function getDisqualifiedNaturalOfficer( string $officerId ): ?array {
		return $this->cachedRequest( "disqualified_natural_$officerId", new HttpRequest(
			url		: "$this->baseUrl/disqualified-officers/natural/{$officerId}",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path		: '/disqualified-officers/corporate/{officer_id}',
		description	: 'Get details of a disqualified corporate officer.'
	)]
	public function getDisqualifiedCorporateOfficer( string $officerId ): ?array {
		return $this->cachedRequest("disqualified_corporate_$officerId", new HttpRequest(
			url		: "$this->baseUrl/disqualified-officers/corporate/{$officerId}",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path		: '/document/{document_id}',
		description	: 'Retrieve metadata for a document.'
	)]
	public function getDocumentMetadata( string $documentId ): ?array {
 		return $this->cachedRequest( "document_metadata_$documentId", new HttpRequest(
			url		: "$this->baseUrl/document/{$documentId}",
			method	: HttpMethod::GET
		));
	}

	#[ApiEndpoint(
		path		: '/document/{document_id}/content',
		description	: 'Download the document content.'
	)]
	public function downloadDocument( string $documentId ): ?string {
		return $this->cachedRequest( "document_pdf_$documentId", new HttpRequest(
			url		: "$this->baseUrl/document/{$documentId}/content",
			method	: HttpMethod::GET,
			format	: ResponseFormat::BINARY,
			accept	: 'application/pdf'
		));
	}
}
