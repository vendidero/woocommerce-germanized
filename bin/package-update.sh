#!/bin/sh

# Output colorized strings
#
# Color codes:
# 0 - black
# 1 - red
# 2 - green
# 3 - yellow
# 4 - blue
# 5 - magenta
# 6 - cian
# 7 - white
output() {
	echo "$(tput setaf "$1")$2$(tput sgr0)"
}

# Merge a package's wpml-config.xml into the plugin's wpml-config.xml (WPML only reads the one within the plugin root).
#
# Usage: merge_wpml_config <source.xml> <target.xml>
#
# Line-based (expects pretty-printed XML with one tag per line, as wpml-config.xml files are):
# - Entries (direct children of a section like <admin-texts> or <custom-fields>) missing in the target section are
#   appended to it, sections missing in the target are appended to <wpml-config>. Entries are identified by their
#   opening line, e.g. <key name="..."> or <custom-field action="copy">_unit</custom-field>.
# - Existing entries are never changed. In case an existing entry differs from the package version, a warning is shown.
# - The target is only written in case something changed, so running this multiple times is safe.
merge_wpml_config() {
	merge_source="$1"
	merge_target="$2"
	merge_tmp="$(mktemp)"

	awk '
		function trim( s ) {
			gsub( /^[ \t\r]+|[ \t\r]+$/, "", s )
			return s
		}

		function tag_name( s ) {
			if ( match( s, /^<\/?[A-Za-z0-9_:.-]+/ ) ) {
				s = substr( s, RSTART, RLENGTH )
				gsub( /[<\/]/, "", s )
				return s
			}
			return ""
		}

		# Classify the current line and update the element depth. Sets "level" (depth of the line itself) and "type".
		function parse( line, t ) {
			t = trim( line )

			if ( t == "" ) {
				type = "blank"
			} else if ( t ~ /^<\?/ || t ~ /^<!--/ ) {
				type = "other"
			} else if ( t ~ /^<\// ) {
				type = "close"
			} else if ( t ~ /\/>$/ || t ~ /<\/[^>]+>$/ ) {
				type = "leaf"
			} else {
				type = "open"
			}

			if ( type == "close" ) {
				depth--
			}

			level = depth

			if ( type == "open" ) {
				depth++
			}
		}

		# Source file: collect sections and their entries.
		FNR == NR {
			parse( $0 )

			if ( type == "blank" || type == "other" ) {
				next
			}

			if ( level == 1 ) {
				if ( type == "open" ) {
					sec = tag_name( trim( $0 ) )
					src_sections[ ++src_section_count ] = sec
					src_section_open[ sec ] = $0
				} else if ( type == "close" ) {
					src_section_close[ sec ] = $0
				}
			} else if ( level >= 2 ) {
				if ( level == 2 && type != "close" ) {
					idx = ++src_entry_count[ sec ]
					src_entry_id[ sec, idx ] = trim( $0 )
					src_entry_body[ sec, idx ] = $0
				} else {
					src_entry_body[ sec, idx ] = src_entry_body[ sec, idx ] "\n" $0
				}
			}
			next
		}

		# Target file: remember lines, existing entries and where sections are closed.
		FNR == 1 {
			depth = 0
		}

		{
			lines[ ++line_count ] = $0
			parse( $0 )

			if ( type == "blank" || type == "other" ) {
				next
			}

			if ( level == 0 && type == "close" ) {
				root_close = line_count
			} else if ( level == 1 ) {
				if ( type == "open" ) {
					sec = tag_name( trim( $0 ) )
					tgt_has_section[ sec ] = 1
				} else if ( type == "close" ) {
					tgt_section_close[ line_count ] = sec
				}
			} else if ( level >= 2 ) {
				if ( level == 2 && type != "close" ) {
					cur = trim( $0 )
					tgt_entry_body[ sec, cur ] = $0
				} else {
					tgt_entry_body[ sec, cur ] = tgt_entry_body[ sec, cur ] "\n" $0
				}
			}
		}

		function missing_entries( sec,    i, id, out ) {
			out = ""

			for ( i = 1; i <= src_entry_count[ sec ]; i++ ) {
				id = src_entry_id[ sec, i ]

				if ( ( sec, id ) in tgt_entry_body ) {
					if ( tgt_entry_body[ sec, id ] != src_entry_body[ sec, i ] ) {
						print "Warning: " sec " " id " differs from the package version - not updated." > "/dev/stderr"
					}
					continue
				}

				out = out src_entry_body[ sec, i ] "\n"
				print "  + " sec " " id > "/dev/stderr"
			}

			return out
		}

		END {
			for ( n = 1; n <= line_count; n++ ) {
				if ( n in tgt_section_close ) {
					printf "%s", missing_entries( tgt_section_close[ n ] )
				}

				if ( n == root_close ) {
					for ( s = 1; s <= src_section_count; s++ ) {
						sec = src_sections[ s ]

						if ( ! ( sec in tgt_has_section ) ) {
							entries = missing_entries( sec )

							if ( entries != "" ) {
								print src_section_open[ sec ]
								printf "%s", entries
								print src_section_close[ sec ]
							}
						}
					}
				}

				print lines[ n ]
			}
		}
	' "$merge_source" "$merge_target" > "$merge_tmp" || { rm -f "$merge_tmp"; return 1; }

	if cmp -s "$merge_tmp" "$merge_target"; then
		output 2 "$merge_target is up to date."
	else
		cat "$merge_tmp" > "$merge_target"
		output 2 "Merged $merge_source into $merge_target."
	fi

	rm -f "$merge_tmp"
}

if [ ! -d "packages/" ]; then
	output 1 "./packages doesn't exist!"
	output 1 "run \"composer install\" before proceed."
fi

# Autoloader
output 3 "Updating autoloader classmaps..."
composer dump-autoload
output 2 "Done"

# Convert textdomains
output 3 "Updating package textdomains..."

# Replace text domains within packages with woocommerce
find ./packages/eu-order-withdrawal-button-for-woocommerce -iname '*.php' -exec sed -i.bak -e "s/, 'eu-order-withdrawal-button-for-woocommerce'/, 'woocommerce-germanized'/g" {} \;
find ./packages/woocommerce-eu-tax-helper -iname '*.php' -exec sed -i.bak -e "s/, 'woocommerce-eu-tax-helper'/, 'woocommerce-germanized'/g" {} \;

output 3 "Updating package JS textdomains..."
find ./packages/eu-order-withdrawal-button-for-woocommerce \( -iname '*.js' -o -iname '*.json' \) -exec sed -i.bak -e "s/'eu-order-withdrawal-button-for-woocommerce'/'woocommerce-germanized'/g" -e "s/\"eu-order-withdrawal-button-for-woocommerce\"/\"woocommerce-germanized\"/g" {} \;

rm -rf ./i18n/languages/*.json

# WPML only reads the wpml-config.xml within the plugin root - merge package configs into it
output 3 "Merging package wpml-config.xml..."
if [ -f ./packages/eu-order-withdrawal-button-for-woocommerce/wpml-config.xml ]; then
	merge_wpml_config ./packages/eu-order-withdrawal-button-for-woocommerce/wpml-config.xml ./wpml-config.xml
fi

if [ $COMPOSER_DEV_MODE -eq 0 ]; then
    output 3 "Removing POT files"
    rm -f ./packages/eu-order-withdrawal-button-for-woocommerce/i18n/eu-order-withdrawal-button-for-woocommerce.pot
fi

rm -rf ./packages/eu-order-withdrawal-button-for-woocommerce/readme.txt
rm -rf ./packages/eu-order-withdrawal-button-for-woocommerce/composer.json
rm -rf ./packages/eu-order-withdrawal-button-for-woocommerce/composer.lock
rm -rf ./packages/eu-order-withdrawal-button-for-woocommerce/vendor
rm -rf ./packages/woocommerce-eu-tax-helper/vendor

output 3 "Clean vendor dirs to save space..."

rm -rf ./vendor/digitick/sepa-xml/doc/*
rm -rf ./vendor/digitick/sepa-xml/.github

output 2 "Done!"

# Cleanup backup files
find ./packages -name "*.bak" -type f -delete
output 2 "Done!"