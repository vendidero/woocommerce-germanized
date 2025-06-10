<?php

namespace Vendidero\Shiptastic;

use Exception;
use RuntimeException;

/**
 * @copyright Copyright (c) 2017 VIISON GmbH
 */
class AddressSplitter {
	/**
	 * This function splits an address line like for example "Pallaswiesenstr. 45 App 231" into its individual parts.
	 * Supported parts are additionToAddress1, streetName, houseNumber and additionToAddress2. AdditionToAddress1
	 * and additionToAddress2 contain additional information that is given at the start and the end of the string, respectively.
	 * Unit tests for testing the regular expression that this function uses exist over at https://regex101.com/r/vO5fY7/1.
	 * More information on this functionality can be found at http://blog.viison.com/post/115849166487/shopware-5-from-a-technical-point-of-view#address-splitting.
	 *
	 * @param string $address
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function split_address( $address ) {
		$fixed_street_name = '';

		/**
		 * Enforce whitespace after comma to improve parsing.
		 */
		$address = str_replace( ',', ', ', $address );

		/**
		 * Special case for street names containing numbers, e.g. Straße 50 in Berlin.
		 * Replace the original street name with a placeholder to prevent mixing up house numbers.
		 */
		if ( 1 === preg_match( '/^(Str\.|Strasse|Straße|Street)\s+[0-9]+/', $address, $matches ) ) {
			$fixed_street_name = $matches[0];
			$address           = preg_replace( '/^(Str\.|Strasse|Straße|Street)\s+([0-9]+)/', 'Sample Street', $address );
		}

		/* Matching this group signifies the following text is part of
		 * additionToAddress2.
		 *
		 * See [1] for some of the English language stop words and abbreviations.
		 *
		 * [1] <https://web.archive.org/web/20180410130330/http://maf.directory/zp4/abbrev.html>
		 *
		 */
		$addition_2_introducers = '(?:
            # {{{ Additions relating to who (a natural person) is addressed
            \s+ [Cc] \s* \/ \s* [Oo] \s
            | ℅
            | \s+ care \s+ of \s+
            # German, Swiss, Austrian
            | \s+ (?: p|p.\s*|per\s+ ) (?: A|A.|Adr.|(?<=\s)Adresse ) \s
            | \s+ p. \s* A. \s
            | \s+ (?: z | z.\s* | zu\s+ ) (?: Hd|Hd.|(?<=\s)Händen|(?<=\s)Haenden|(?<=\s)Handen) \s+
            ## o. V. i. A. = oder Vertreter im Amt
            | \s+ (?: o | o.\s* | oder\s+ )
                (?: V | V.\s* | (?<=\s)Vertreter\s+ )
                (?: i | i.\s* | (?<=\s)im\s+ )
                (?: A | A.\s* | (?<=\s)Amt\s+ )
            # }}}
            # {{{ Additions which further specify more precisely the location
            | \s+ (?: Haus ) \s
            | \s+ (?: WG | W\.G\. | WG\. | Wohngemeinschaft ) ($ | \s)
            | \s+ (?: [Aa]partment | APT \.? | Apt \.?  apt \.? ) \s
            | \s+ (?: [Ff]lat ) \s
            | (?: # Numeric-based location specifiers (e.g., "3. Stock"):
                \s+
                (?:
                    [\p{N}]+ # A number, …
                    (?i: st | nd | rd | th)? # …, optionally followed by an English number suffix
                    \.? # …, followed by an optional dot,
                    \s* # …, followed by optional spacing
                )?
                (?: # Specifying category:
                    (?i: Stock | Stockwerk | OG)
                    | App \.? | Apt \.? | apt \.? | (?i: Appartment | Apartment)
                )
                # At the end of the string or followed by a space
                (?: $ | \s)
            )
            | (?:
                \s+ (?:
                    # English language stop words wrt location from source [1]
                    # (extracted only those which may not be _exclusively_ part of
                    # street names):
                    | ANX \.? | (?i: ANNEX)
                    | APT \.? | (?i: APARTMENT)
                    | ARC \.? | (?i: ARCADE)
                    | AVE \.? | (?i: AVENUE)
                    | BSMT \.? | (?i: BASEMENT)
                    | BLDG \.? | (?i: BUILDING)
                    | CP \.? | (?i: CAMP)
                    | COR \.? | (?i: CORNER)
                    | CORS \.? | (?i: CORNERS)
                    | CT \.? | (?i: COURT)
                    | CTS \.? | (?i: COURTS)
                    | DEPT \.? | (?i: DEPARTMENT)
                    | DV \.? | (?i: DIVIDE)
                    | EST \.? | (?i: ESTATE)
                    | EXT \.? | (?i: EXTENSION)
                    | FRY \.? | (?i: FERRY)
                    | FLD \.? | (?i: FIELD)
                    | FLDS \.? | (?i: FIELDS)
                    | FLT \.? | (?i: FLAT)
                    | FL \.? | (?i: FLOOR)
                    | FRNT \.? | (?i: FRONT)
                    | GDNS \.? | (?i: GARDEN)
                    | GDNS \.? | (?i: GARDENS)
                    | GTWY \.? | (?i: GATEWAY)
                    | GRN \.? | (?i: GREEN)
                    | GRV \.? | (?i: GROVE)
                    | HNGR \.? | (?i: HANGER)
                    | HBR \.? | (?i: HARBOR)
                    | HVN \.? | (?i: HAVEN)
                    | KY \.? | (?i: KEY)
                    | LBBY \.? | (?i: LOBBY)
                    | LCKS \.? | (?i: LOCK)
                    | LCKS \.? | (?i: LOCKS)
                    | LDG \.? | (?i: LODGE)
                    | MNR \.? | (?i: MANOR)
                    | OFC \.? | (?i: OFFICE)
                    | PKWY \.? | (?i: PARKWAY)
                    | PH \.? | (?i: PENTHOUSE)
                    | PRT \.? | (?i: PORT)
                    | RADL \.? | (?i: RADIAL)
                    | RM \.? | (?i: ROOM)
                    | SPC \.? | (?i: SPACE)
                    | SQ \.? | (?i: SQUARE)
                    | STA \.? | (?i: STATION)
                    | STE \.? | (?i: SUITE)
                    | TER \.? | (?i: TERRACE)
                    | TRAK \.? | (?i: TRACK)
                    | TRL \.? | (?i: TRAIL)
                    | TRLR \.? | (?i: TRAILER)
                    | TUNL \.? | (?i: TUNNEL)
                    | VW \.? | (?i: VIEW)
                    | VIS \.? | (?i: VISTA)
                    # Custom custom additions:
                    | (?i: Story | Storey)
                    | LVL \.? | (?i: Level)
                )
                # May optionally be followed directly by a number+letter
                # combination (e.g., "LVL3C"):
                (?: [\p{N}]+[\p{L}]* )?
                # Occurs at the end of the string or followed by a space:
                ($ | \s)
            )
            # Heuristic to match location specifiers. These must not be
            # conflated with house number extensions as in "12 AB". Hence
            # our heuristic is at least 3 letters with the first letter being
            # spelled as a capital. E.g., it would match "Haus", "Gebäude" or
            # "Arbeitspl.", but not "AAB".
            | \s+ ( [\p{Lu}\p{Lt}] [\p{Ll}\p{Lo}]{2,}  \.? ) ($ | \s)
            # }}}
        )';
		$regex                  = '
            /\A\s*
            (?: #########################################################################
                # Option A: <House number> <Street name>      #
                # [<Addition to address 2>]                                             #
                #########################################################################
            (?:No\.\s*)?
                (?P<A_House_number_match>
                     (?P<A_House_number_base>
                        \pN+(\s+\d+\/\d+)?
                     )
                     (?:
                        \s*[\-\/\.]?\s*
                        (?P<A_House_number_extension>(?:[a-zA-Z\pN]){1,2})
                        \s+
                     )?
                )
            \s*,?\s*
                (?P<A_Street_name>(?:[a-zA-Z]\s*|\pN\pL{2,}\s\pL)\S[^,#]*?(?<!\s)) # Street name
            \s*(?:(?:[,\/]|(?=\#))\s*(?!\s*No\.)
                (?P<A_Addition_to_address_2>(?!\s).*?))? # Addition to address 2
            |   #########################################################################
                # Option B: [<Addition to address 1>] <Street name> <House number>      #
                # [<Addition to address 2>]                                             #
                #########################################################################
                (?:(?P<B_Addition_to_address_1>.*?),\s*(?=.*[,\/]))? # Addition to address 1
                (?!\s*([Nn]o|[Nn]r)\.)(?P<B_Street_name>[^0-9# ]\s*\S(?:[^,#](?!\b\pN+\s))*?(?<!\s)) # Street name
            \s*[\/,]?\s*(?:\s([Nn]o|[Nn]r)[.:])?\s*
                (?P<B_House_number_match>
                     (?P<B_House_number_base>
                        \pN+
                     )
                     (?:
                        # Match house numbers that are (optionally) amended
                        # by a dash (e.g., 12-13) or slash (e.g., 12\/A):
                        (?: \s*[\-\/]\s* )*
                        (?P<B_House_number_extension>
                            (?:
                                # Do not match "care-of"-like additions as
                                # house numbers:
                                (?!' . $addition_2_introducers . ')
                                \s*[\pL\pN]+
                            )
                            # Match any further slash- or dash-based house
                            # number extensions:
                            (?:
                                # Do not match "care-of"-like additions as
                                # house numbers:
                                (?!' . $addition_2_introducers . ')
                                # Match any (optionally space-separated)
                                # additionals parts of house numbers after
                                # slashes or dashes.
                                \s* [\-\/] \s*
                                [\pL\pN]+
                            )*
                        )
                     )?
                ) # House number
                (?:
                    (?:\s*[-,\/]|(?=\#)|\s)\s*(?!\s*([Nn]o|[Nn]r)\.)\s*
                    (?P<B_Addition_to_address_2>(?!\s).*?)
                )?
            )
            \s*\Z/xu';
		$result                 = preg_match( $regex, $address, $matches );
		if ( 0 === $result ) {
			throw new Exception( esc_html( sprintf( 'Error occurred while trying to split address \'%s\'', $address ) ) );
		} elseif ( false === $result ) {
			throw new RuntimeException( esc_html( sprintf( 'Error occurred while trying to split address \'%s\'', $address ) ) );
		}
		if ( ! empty( $matches['A_Street_name'] ) ) {
			$result = array(
				'additionToAddress1' => '',
				'streetName'         => $matches['A_Street_name'],
				'houseNumber'        => $matches['A_House_number_match'],
				'houseNumberParts'   => array(
					'base'      => $matches['A_House_number_base'],
					'extension' => isset( $matches['A_House_number_extension'] ) ? trim( $matches['A_House_number_extension'] ) : '',
				),
				'additionToAddress2' => ( isset( $matches['A_Addition_to_address_2'] ) ) ? $matches['A_Addition_to_address_2'] : '',
			);
		} else {
			$result = array(
				'additionToAddress1' => '',
				'streetName'         => ( ! empty( $matches['B_Addition_to_address_1'] ) ? $matches['B_Addition_to_address_1'] . ' ' : '' ) . $matches['B_Street_name'],
				'houseNumber'        => $matches['B_House_number_match'],
				'houseNumberParts'   => array(
					'base'      => $matches['B_House_number_base'],
					'extension' => isset( $matches['B_House_number_extension'] ) ? trim( $matches['B_House_number_extension'] ) : '',
				),
				'additionToAddress2' => isset( $matches['B_Addition_to_address_2'] ) ? $matches['B_Addition_to_address_2'] : '',
			);
		}

		if ( $fixed_street_name ) {
			$result['streetName'] = str_replace( 'Sample Street', $fixed_street_name, $result['streetName'] );
		}

		return $result;
	}

	/**
	 * @param string $house_number A house number string to split in base and extension
	 *
	 * @return array
	 * @throws Exception
	 */
	public static function split_house_number( $house_number ) {
		$regex  =
			'/
            \A\s* # Trim white spaces at the beginning
            (?:[nN][oO][\.:]?\s*)? # Trim sth. like No.
            (?:\#\s*)? # Trim #
            (?<House_number_base>
                [\pN]+ # House Number base (only the number)
            )
            \s*[\/\-\.]?\s* # Separator (optional)
            (?<House_number_extension> # House number extension (optional)
                .*? # Here we allow every character. Everything after the separator is interpreted as extension
            ) 
            \s*\Z # Trim white spaces at the end
            /xu'; // Option (u)nicode and e(x)tended syntax
		$result = preg_match( $regex, $house_number, $matches );
		if ( 0 === $result ) {
			throw new Exception( esc_html( sprintf( 'Error occurred while trying to house number \'%s\'', $house_number ) ) );
		} elseif ( false === $result ) {
			throw new RuntimeException( esc_html( sprintf( 'Error occurred while trying to house number \'%s\'', $house_number ) ) );
		}

		return array(
			'base'      => $matches['House_number_base'],
			'extension' => $matches['House_number_extension'],
		);
	}
}
