<?php declare(strict_types=1);

namespace Tribe\Plugin\Locations;

/**
 * US state abbreviations and normalization helpers.
 */
class Us_States {

	private const array STATES = [
		'AL' => 'Alabama',
		'AK' => 'Alaska',
		'AZ' => 'Arizona',
		'AR' => 'Arkansas',
		'CA' => 'California',
		'CO' => 'Colorado',
		'CT' => 'Connecticut',
		'DE' => 'Delaware',
		'DC' => 'District of Columbia',
		'FL' => 'Florida',
		'GA' => 'Georgia',
		'HI' => 'Hawaii',
		'ID' => 'Idaho',
		'IL' => 'Illinois',
		'IN' => 'Indiana',
		'IA' => 'Iowa',
		'KS' => 'Kansas',
		'KY' => 'Kentucky',
		'LA' => 'Louisiana',
		'ME' => 'Maine',
		'MD' => 'Maryland',
		'MA' => 'Massachusetts',
		'MI' => 'Michigan',
		'MN' => 'Minnesota',
		'MS' => 'Mississippi',
		'MO' => 'Missouri',
		'MT' => 'Montana',
		'NE' => 'Nebraska',
		'NV' => 'Nevada',
		'NH' => 'New Hampshire',
		'NJ' => 'New Jersey',
		'NM' => 'New Mexico',
		'NY' => 'New York',
		'NC' => 'North Carolina',
		'ND' => 'North Dakota',
		'OH' => 'Ohio',
		'OK' => 'Oklahoma',
		'OR' => 'Oregon',
		'PA' => 'Pennsylvania',
		'RI' => 'Rhode Island',
		'SC' => 'South Carolina',
		'SD' => 'South Dakota',
		'TN' => 'Tennessee',
		'TX' => 'Texas',
		'UT' => 'Utah',
		'VT' => 'Vermont',
		'VA' => 'Virginia',
		'WA' => 'Washington',
		'WV' => 'West Virginia',
		'WI' => 'Wisconsin',
		'WY' => 'Wyoming',
	];

	/**
	 * @return array<string, string> Abbreviation => label for ACF select fields.
	 */
	public function get_choices(): array {
		return self::STATES;
	}

	public function normalize_to_code( string $state ): string {
		$state = trim( $state );

		if ( $state === '' ) {
			return '';
		}

		$upper = strtoupper( $state );

		if ( isset( self::STATES[ $upper ] ) ) {
			return $upper;
		}

		foreach ( self::STATES as $code => $name ) {
			if ( 0 === strcasecmp( $name, $state ) ) {
				return $code;
			}
		}

		return $upper;
	}

	public function get_name( string $state ): string {
		$code = $this->normalize_to_code( $state );

		return self::STATES[ $code ] ?? $state;
	}

	/**
	 * @return string[] Values that may be stored in location state meta.
	 */
	public function get_match_values( string $state ): array {
		$code = $this->normalize_to_code( $state );

		if ( $code === '' ) {
			return [];
		}

		$name = self::STATES[ $code ] ?? '';

		return array_values( array_unique( array_filter( [ $code, $name ] ) ) );
	}

}
