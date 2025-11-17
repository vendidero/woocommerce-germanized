<?php

namespace Vendidero\Shiptastic;

defined( 'ABSPATH' ) || exit;

/**
 * Based on Unicode Filter by imTigger.
 * @see https://github.com/imTigger/unicode-filter
 */
class Encoding {

	const BASIC_LATIN                           = 'BASIC_LATIN';
	const LATIN_1_SUPPLEMENT                    = 'LATIN_1_SUPPLEMENT';
	const LATIN_EXTENDED_A                      = 'LATIN_EXTENDED_A';
	const LATIN_EXTENDED_B                      = 'LATIN_EXTENDED_B';
	const IPA_EXTENSIONS                        = 'IPA_EXTENSIONS';
	const SPACING_MODIFIER_LETTERS              = 'SPACING_MODIFIER_LETTERS';
	const COMBINING_DIACRITICAL_MARKS           = 'COMBINING_DIACRITICAL_MARKS';
	const GREEK_AND_COPTIC                      = 'GREEK_AND_COPTIC';
	const CYRILLIC                              = 'CYRILLIC';
	const CYRILLIC_SUPPLEMENT                   = 'CYRILLIC_SUPPLEMENT';
	const ARMENIAN                              = 'ARMENIAN';
	const HEBREW                                = 'HEBREW';
	const ARABIC                                = 'ARABIC';
	const SYRIAC                                = 'SYRIAC';
	const ARABIC_SUPPLEMENT                     = 'ARABIC_SUPPLEMENT';
	const THAANA                                = 'THAANA';
	const NKO                                   = 'NKO';
	const SAMARITAN                             = 'SAMARITAN';
	const MANDAIC                               = 'MANDAIC';
	const SYRIAC_SUPPLEMENT                     = 'SYRIAC_SUPPLEMENT';
	const ARABIC_EXTENDED_A                     = 'ARABIC_EXTENDED_A';
	const DEVANAGARI                            = 'DEVANAGARI';
	const BENGALI                               = 'BENGALI';
	const GURMUKHI                              = 'GURMUKHI';
	const GUJARATI                              = 'GUJARATI';
	const ORIYA                                 = 'ORIYA';
	const TAMIL                                 = 'TAMIL';
	const TELUGU                                = 'TELUGU';
	const KANNADA                               = 'KANNADA';
	const MALAYALAM                             = 'MALAYALAM';
	const SINHALA                               = 'SINHALA';
	const THAI                                  = 'THAI';
	const LAO                                   = 'LAO';
	const TIBETAN                               = 'TIBETAN';
	const MYANMAR                               = 'MYANMAR';
	const GEORGIAN                              = 'GEORGIAN';
	const HANGUL_JAMO                           = 'HANGUL_JAMO';
	const ETHIOPIC                              = 'ETHIOPIC';
	const ETHIOPIC_SUPPLEMENT                   = 'ETHIOPIC_SUPPLEMENT';
	const CHEROKEE                              = 'CHEROKEE';
	const UNIFIED_CANADIAN_ABORIGINAL_SYLLABICS = 'UNIFIED_CANADIAN_ABORIGINAL_SYLLABICS';
	const OGHAM                                 = 'OGHAM';
	const RUNIC                                 = 'RUNIC';
	const TAGALOG                               = 'TAGALOG';
	const HANUNOO                               = 'HANUNOO';
	const BUHID                                 = 'BUHID';
	const TAGBANWA                              = 'TAGBANWA';
	const KHMER                                 = 'KHMER';
	const MONGOLIAN                             = 'MONGOLIAN';
	const UNIFIED_CANADIAN_ABORIGINAL_SYLLABICS_EXTENDED = 'UNIFIED_CANADIAN_ABORIGINAL_SYLLABICS_EXTENDED';
	const LIMBU                                   = 'LIMBU';
	const TAI_LE                                  = 'TAI_LE';
	const NEW_TAI_LUE                             = 'NEW_TAI_LUE';
	const KHMER_SYMBOLS                           = 'KHMER_SYMBOLS';
	const BUGINESE                                = 'BUGINESE';
	const TAI_THAM                                = 'TAI_THAM';
	const COMBINING_DIACRITICAL_MARKS_EXTENDED    = 'COMBINING_DIACRITICAL_MARKS_EXTENDED';
	const BALINESE                                = 'BALINESE';
	const SUNDANESE                               = 'SUNDANESE';
	const BATAK                                   = 'BATAK';
	const LEPCHA                                  = 'LEPCHA';
	const OL_CHIKI                                = 'OL_CHIKI';
	const CYRILLIC_EXTENDED_C                     = 'CYRILLIC_EXTENDED_C';
	const GEORGIAN_EXTENDED                       = 'GEORGIAN_EXTENDED';
	const SUNDANESE_SUPPLEMENT                    = 'SUNDANESE_SUPPLEMENT';
	const VEDIC_EXTENSIONS                        = 'VEDIC_EXTENSIONS';
	const PHONETIC_EXTENSIONS                     = 'PHONETIC_EXTENSIONS';
	const PHONETIC_EXTENSIONS_SUPPLEMENT          = 'PHONETIC_EXTENSIONS_SUPPLEMENT';
	const COMBINING_DIACRITICAL_MARKS_SUPPLEMENT  = 'COMBINING_DIACRITICAL_MARKS_SUPPLEMENT';
	const LATIN_EXTENDED_ADDITIONAL               = 'LATIN_EXTENDED_ADDITIONAL';
	const GREEK_EXTENDED                          = 'GREEK_EXTENDED';
	const GENERAL_PUNCTUATION                     = 'GENERAL_PUNCTUATION';
	const SUPERSCRIPTS_AND_SUBSCRIPTS             = 'SUPERSCRIPTS_AND_SUBSCRIPTS';
	const CURRENCY_SYMBOLS                        = 'CURRENCY_SYMBOLS';
	const COMBINING_DIACRITICAL_MARKS_FOR_SYMBOLS = 'COMBINING_DIACRITICAL_MARKS_FOR_SYMBOLS';
	const LETTERLIKE_SYMBOLS                      = 'LETTERLIKE_SYMBOLS';
	const NUMBER_FORMS                            = 'NUMBER_FORMS';
	const ARROWS                                  = 'ARROWS';
	const MATHEMATICAL_OPERATORS                  = 'MATHEMATICAL_OPERATORS';
	const MISCELLANEOUS_TECHNICAL                 = 'MISCELLANEOUS_TECHNICAL';
	const CONTROL_PICTURES                        = 'CONTROL_PICTURES';
	const OPTICAL_CHARACTER_RECOGNITION           = 'OPTICAL_CHARACTER_RECOGNITION';
	const ENCLOSED_ALPHANUMERICS                  = 'ENCLOSED_ALPHANUMERICS';
	const BOX_DRAWING                             = 'BOX_DRAWING';
	const BLOCK_ELEMENTS                          = 'BLOCK_ELEMENTS';
	const GEOMETRIC_SHAPES                        = 'GEOMETRIC_SHAPES';
	const MISCELLANEOUS_SYMBOLS                   = 'MISCELLANEOUS_SYMBOLS';
	const DINGBATS                                = 'DINGBATS';
	const MISCELLANEOUS_MATHEMATICAL_SYMBOLS_A    = 'MISCELLANEOUS_MATHEMATICAL_SYMBOLS_A';
	const SUPPLEMENTAL_ARROWS_A                   = 'SUPPLEMENTAL_ARROWS_A';
	const BRAILLE_PATTERNS                        = 'BRAILLE_PATTERNS';
	const SUPPLEMENTAL_ARROWS_B                   = 'SUPPLEMENTAL_ARROWS_B';
	const MISCELLANEOUS_MATHEMATICAL_SYMBOLS_B    = 'MISCELLANEOUS_MATHEMATICAL_SYMBOLS_B';
	const SUPPLEMENTAL_MATHEMATICAL_OPERATORS     = 'SUPPLEMENTAL_MATHEMATICAL_OPERATORS';
	const MISCELLANEOUS_SYMBOLS_AND_ARROWS        = 'MISCELLANEOUS_SYMBOLS_AND_ARROWS';
	const GLAGOLITIC                              = 'GLAGOLITIC';
	const LATIN_EXTENDED_C                        = 'LATIN_EXTENDED_C';
	const COPTIC                                  = 'COPTIC';
	const GEORGIAN_SUPPLEMENT                     = 'GEORGIAN_SUPPLEMENT';
	const TIFINAGH                                = 'TIFINAGH';
	const ETHIOPIC_EXTENDED                       = 'ETHIOPIC_EXTENDED';
	const CYRILLIC_EXTENDED_A                     = 'CYRILLIC_EXTENDED_A';
	const SUPPLEMENTAL_PUNCTUATION                = 'SUPPLEMENTAL_PUNCTUATION';
	const CJK_RADICALS_SUPPLEMENT                 = 'CJK_RADICALS_SUPPLEMENT';
	const KANGXI_RADICALS                         = 'KANGXI_RADICALS';
	const IDEOGRAPHIC_DESCRIPTION_CHARACTERS      = 'IDEOGRAPHIC_DESCRIPTION_CHARACTERS';
	const CJK_SYMBOLS_AND_PUNCTUATION             = 'CJK_SYMBOLS_AND_PUNCTUATION';
	const HIRAGANA                                = 'HIRAGANA';
	const KATAKANA                                = 'KATAKANA';
	const BOPOMOFO                                = 'BOPOMOFO';
	const HANGUL_COMPATIBILITY_JAMO               = 'HANGUL_COMPATIBILITY_JAMO';
	const KANBUN                                  = 'KANBUN';
	const BOPOMOFO_EXTENDED                       = 'BOPOMOFO_EXTENDED';
	const CJK_STROKES                             = 'CJK_STROKES';
	const KATAKANA_PHONETIC_EXTENSIONS            = 'KATAKANA_PHONETIC_EXTENSIONS';
	const ENCLOSED_CJK_LETTERS_AND_MONTHS         = 'ENCLOSED_CJK_LETTERS_AND_MONTHS';
	const CJK_COMPATIBILITY                       = 'CJK_COMPATIBILITY';
	const CJK_UNIFIED_IDEOGRAPHS_EXTENSION_A      = 'CJK_UNIFIED_IDEOGRAPHS_EXTENSION_A';
	const YIJING_HEXAGRAM_SYMBOLS                 = 'YIJING_HEXAGRAM_SYMBOLS';
	const CJK_UNIFIED_IDEOGRAPHS                  = 'CJK_UNIFIED_IDEOGRAPHS';
	const YI_SYLLABLES                            = 'YI_SYLLABLES';
	const YI_RADICALS                             = 'YI_RADICALS';
	const LISU                                    = 'LISU';
	const VAI                                     = 'VAI';
	const CYRILLIC_EXTENDED_B                     = 'CYRILLIC_EXTENDED_B';
	const BAMUM                                   = 'BAMUM';
	const MODIFIER_TONE_LETTERS                   = 'MODIFIER_TONE_LETTERS';
	const LATIN_EXTENDED_D                        = 'LATIN_EXTENDED_D';
	const SYLOTI_NAGRI                            = 'SYLOTI_NAGRI';
	const COMMON_INDIC_NUMBER_FORMS               = 'COMMON_INDIC_NUMBER_FORMS';
	const PHAGS_PA                                = 'PHAGS_PA';
	const SAURASHTRA                              = 'SAURASHTRA';
	const DEVANAGARI_EXTENDED                     = 'DEVANAGARI_EXTENDED';
	const KAYAH_LI                                = 'KAYAH_LI';
	const REJANG                                  = 'REJANG';
	const HANGUL_JAMO_EXTENDED_A                  = 'HANGUL_JAMO_EXTENDED_A';
	const JAVANESE                                = 'JAVANESE';
	const MYANMAR_EXTENDED_B                      = 'MYANMAR_EXTENDED_B';
	const CHAM                                    = 'CHAM';
	const MYANMAR_EXTENDED_A                      = 'MYANMAR_EXTENDED_A';
	const TAI_VIET                                = 'TAI_VIET';
	const MEETEI_MAYEK_EXTENSIONS                 = 'MEETEI_MAYEK_EXTENSIONS';
	const ETHIOPIC_EXTENDED_A                     = 'ETHIOPIC_EXTENDED_A';
	const LATIN_EXTENDED_E                        = 'LATIN_EXTENDED_E';
	const CHEROKEE_SUPPLEMENT                     = 'CHEROKEE_SUPPLEMENT';
	const MEETEI_MAYEK                            = 'MEETEI_MAYEK';
	const HANGUL_SYLLABLES                        = 'HANGUL_SYLLABLES';
	const HANGUL_JAMO_EXTENDED_B                  = 'HANGUL_JAMO_EXTENDED_B';
	const HIGH_SURROGATES                         = 'HIGH_SURROGATES';
	const HIGH_PRIVATE_USE_SURROGATES             = 'HIGH_PRIVATE_USE_SURROGATES';
	const LOW_SURROGATES                          = 'LOW_SURROGATES';
	const PRIVATE_USE_AREA                        = 'PRIVATE_USE_AREA';
	const CJK_COMPATIBILITY_IDEOGRAPHS            = 'CJK_COMPATIBILITY_IDEOGRAPHS';
	const ALPHABETIC_PRESENTATION_FORMS           = 'ALPHABETIC_PRESENTATION_FORMS';
	const ARABIC_PRESENTATION_FORMS_A             = 'ARABIC_PRESENTATION_FORMS_A';
	const VARIATION_SELECTORS                     = 'VARIATION_SELECTORS';
	const VERTICAL_FORMS                          = 'VERTICAL_FORMS';
	const COMBINING_HALF_MARKS                    = 'COMBINING_HALF_MARKS';
	const CJK_COMPATIBILITY_FORMS                 = 'CJK_COMPATIBILITY_FORMS';
	const SMALL_FORM_VARIANTS                     = 'SMALL_FORM_VARIANTS';
	const ARABIC_PRESENTATION_FORMS_B             = 'ARABIC_PRESENTATION_FORMS_B';
	const HALFWIDTH_AND_FULLWIDTH_FORMS           = 'HALFWIDTH_AND_FULLWIDTH_FORMS';
	const SPECIALS                                = 'SPECIALS';
	const LINEAR_B_SYLLABARY                      = 'LINEAR_B_SYLLABARY';
	const LINEAR_B_IDEOGRAMS                      = 'LINEAR_B_IDEOGRAMS';
	const AEGEAN_NUMBERS                          = 'AEGEAN_NUMBERS';
	const ANCIENT_GREEK_NUMBERS                   = 'ANCIENT_GREEK_NUMBERS';
	const ANCIENT_SYMBOLS                         = 'ANCIENT_SYMBOLS';
	const PHAISTOS_DISC                           = 'PHAISTOS_DISC';
	const LYCIAN                                  = 'LYCIAN';
	const CARIAN                                  = 'CARIAN';
	const COPTIC_EPACT_NUMBERS                    = 'COPTIC_EPACT_NUMBERS';
	const OLD_ITALIC                              = 'OLD_ITALIC';
	const GOTHIC                                  = 'GOTHIC';
	const OLD_PERMIC                              = 'OLD_PERMIC';
	const UGARITIC                                = 'UGARITIC';
	const OLD_PERSIAN                             = 'OLD_PERSIAN';
	const DESERET                                 = 'DESERET';
	const SHAVIAN                                 = 'SHAVIAN';
	const OSMANYA                                 = 'OSMANYA';
	const OSAGE                                   = 'OSAGE';
	const ELBASAN                                 = 'ELBASAN';
	const CAUCASIAN_ALBANIAN                      = 'CAUCASIAN_ALBANIAN';
	const LINEAR_A                                = 'LINEAR_A';
	const CYPRIOT_SYLLABARY                       = 'CYPRIOT_SYLLABARY';
	const IMPERIAL_ARAMAIC                        = 'IMPERIAL_ARAMAIC';
	const PALMYRENE                               = 'PALMYRENE';
	const NABATAEAN                               = 'NABATAEAN';
	const HATRAN                                  = 'HATRAN';
	const PHOENICIAN                              = 'PHOENICIAN';
	const LYDIAN                                  = 'LYDIAN';
	const MEROITIC_HIEROGLYPHS                    = 'MEROITIC_HIEROGLYPHS';
	const MEROITIC_CURSIVE                        = 'MEROITIC_CURSIVE';
	const KHAROSHTHI                              = 'KHAROSHTHI';
	const OLD_SOUTH_ARABIAN                       = 'OLD_SOUTH_ARABIAN';
	const OLD_NORTH_ARABIAN                       = 'OLD_NORTH_ARABIAN';
	const MANICHAEAN                              = 'MANICHAEAN';
	const AVESTAN                                 = 'AVESTAN';
	const INSCRIPTIONAL_PARTHIAN                  = 'INSCRIPTIONAL_PARTHIAN';
	const INSCRIPTIONAL_PAHLAVI                   = 'INSCRIPTIONAL_PAHLAVI';
	const PSALTER_PAHLAVI                         = 'PSALTER_PAHLAVI';
	const OLD_TURKIC                              = 'OLD_TURKIC';
	const OLD_HUNGARIAN                           = 'OLD_HUNGARIAN';
	const HANIFI_ROHINGYA                         = 'HANIFI_ROHINGYA';
	const RUMI_NUMERAL_SYMBOLS                    = 'RUMI_NUMERAL_SYMBOLS';
	const OLD_SOGDIAN                             = 'OLD_SOGDIAN';
	const SOGDIAN                                 = 'SOGDIAN';
	const BRAHMI                                  = 'BRAHMI';
	const KAITHI                                  = 'KAITHI';
	const SORA_SOMPENG                            = 'SORA_SOMPENG';
	const CHAKMA                                  = 'CHAKMA';
	const MAHAJANI                                = 'MAHAJANI';
	const SHARADA                                 = 'SHARADA';
	const SINHALA_ARCHAIC_NUMBERS                 = 'SINHALA_ARCHAIC_NUMBERS';
	const KHOJKI                                  = 'KHOJKI';
	const MULTANI                                 = 'MULTANI';
	const KHUDAWADI                               = 'KHUDAWADI';
	const GRANTHA                                 = 'GRANTHA';
	const NEWA                                    = 'NEWA';
	const TIRHUTA                                 = 'TIRHUTA';
	const SIDDHAM                                 = 'SIDDHAM';
	const MODI                                    = 'MODI';
	const MONGOLIAN_SUPPLEMENT                    = 'MONGOLIAN_SUPPLEMENT';
	const TAKRI                                   = 'TAKRI';
	const AHOM                                    = 'AHOM';
	const DOGRA                                   = 'DOGRA';
	const WARANG_CITI                             = 'WARANG_CITI';
	const ZANABAZAR_SQUARE                        = 'ZANABAZAR_SQUARE';
	const SOYOMBO                                 = 'SOYOMBO';
	const PAU_CIN_HAU                             = 'PAU_CIN_HAU';
	const BHAIKSUKI                               = 'BHAIKSUKI';
	const MARCHEN                                 = 'MARCHEN';
	const MASARAM_GONDI                           = 'MASARAM_GONDI';
	const GUNJALA_GONDI                           = 'GUNJALA_GONDI';
	const MAKASAR                                 = 'MAKASAR';
	const CUNEIFORM                               = 'CUNEIFORM';
	const CUNEIFORM_NUMBERS_AND_PUNCTUATION       = 'CUNEIFORM_NUMBERS_AND_PUNCTUATION';
	const EARLY_DYNASTIC_CUNEIFORM                = 'EARLY_DYNASTIC_CUNEIFORM';
	const EGYPTIAN_HIEROGLYPHS                    = 'EGYPTIAN_HIEROGLYPHS';
	const ANATOLIAN_HIEROGLYPHS                   = 'ANATOLIAN_HIEROGLYPHS';
	const BAMUM_SUPPLEMENT                        = 'BAMUM_SUPPLEMENT';
	const MRO                                     = 'MRO';
	const BASSA_VAH                               = 'BASSA_VAH';
	const PAHAWH_HMONG                            = 'PAHAWH_HMONG';
	const MEDEFAIDRIN                             = 'MEDEFAIDRIN';
	const MIAO                                    = 'MIAO';
	const IDEOGRAPHIC_SYMBOLS_AND_PUNCTUATION     = 'IDEOGRAPHIC_SYMBOLS_AND_PUNCTUATION';
	const TANGUT                                  = 'TANGUT';
	const TANGUT_COMPONENTS                       = 'TANGUT_COMPONENTS';
	const KANA_SUPPLEMENT                         = 'KANA_SUPPLEMENT';
	const KANA_EXTENDED_A                         = 'KANA_EXTENDED_A';
	const NUSHU                                   = 'NUSHU';
	const DUPLOYAN                                = 'DUPLOYAN';
	const SHORTHAND_FORMAT_CONTROLS               = 'SHORTHAND_FORMAT_CONTROLS';
	const BYZANTINE_MUSICAL_SYMBOLS               = 'BYZANTINE_MUSICAL_SYMBOLS';
	const MUSICAL_SYMBOLS                         = 'MUSICAL_SYMBOLS';
	const ANCIENT_GREEK_MUSICAL_NOTATION          = 'ANCIENT_GREEK_MUSICAL_NOTATION';
	const MAYAN_NUMERALS                          = 'MAYAN_NUMERALS';
	const TAI_XUAN_JING_SYMBOLS                   = 'TAI_XUAN_JING_SYMBOLS';
	const COUNTING_ROD_NUMERALS                   = 'COUNTING_ROD_NUMERALS';
	const MATHEMATICAL_ALPHANUMERIC_SYMBOLS       = 'MATHEMATICAL_ALPHANUMERIC_SYMBOLS';
	const SUTTON_SIGNWRITING                      = 'SUTTON_SIGNWRITING';
	const GLAGOLITIC_SUPPLEMENT                   = 'GLAGOLITIC_SUPPLEMENT';
	const MENDE_KIKAKUI                           = 'MENDE_KIKAKUI';
	const ADLAM                                   = 'ADLAM';
	const INDIC_SIYAQ_NUMBERS                     = 'INDIC_SIYAQ_NUMBERS';
	const ARABIC_MATHEMATICAL_ALPHABETIC_SYMBOLS  = 'ARABIC_MATHEMATICAL_ALPHABETIC_SYMBOLS';
	const MAHJONG_TILES                           = 'MAHJONG_TILES';
	const DOMINO_TILES                            = 'DOMINO_TILES';
	const PLAYING_CARDS                           = 'PLAYING_CARDS';
	const ENCLOSED_ALPHANUMERIC_SUPPLEMENT        = 'ENCLOSED_ALPHANUMERIC_SUPPLEMENT';
	const ENCLOSED_IDEOGRAPHIC_SUPPLEMENT         = 'ENCLOSED_IDEOGRAPHIC_SUPPLEMENT';
	const MISCELLANEOUS_SYMBOLS_AND_PICTOGRAPHS   = 'MISCELLANEOUS_SYMBOLS_AND_PICTOGRAPHS';
	const EMOTICONS                               = 'EMOTICONS';
	const ORNAMENTAL_DINGBATS                     = 'ORNAMENTAL_DINGBATS';
	const TRANSPORT_AND_MAP_SYMBOLS               = 'TRANSPORT_AND_MAP_SYMBOLS';
	const ALCHEMICAL_SYMBOLS                      = 'ALCHEMICAL_SYMBOLS';
	const GEOMETRIC_SHAPES_EXTENDED               = 'GEOMETRIC_SHAPES_EXTENDED';
	const SUPPLEMENTAL_ARROWS_C                   = 'SUPPLEMENTAL_ARROWS_C';
	const SUPPLEMENTAL_SYMBOLS_AND_PICTOGRAPHS    = 'SUPPLEMENTAL_SYMBOLS_AND_PICTOGRAPHS';
	const CHESS_SYMBOLS                           = 'CHESS_SYMBOLS';
	const CJK_UNIFIED_IDEOGRAPHS_EXTENSION_B      = 'CJK_UNIFIED_IDEOGRAPHS_EXTENSION_B';
	const CJK_UNIFIED_IDEOGRAPHS_EXTENSION_C      = 'CJK_UNIFIED_IDEOGRAPHS_EXTENSION_C';
	const CJK_UNIFIED_IDEOGRAPHS_EXTENSION_D      = 'CJK_UNIFIED_IDEOGRAPHS_EXTENSION_D';
	const CJK_UNIFIED_IDEOGRAPHS_EXTENSION_E      = 'CJK_UNIFIED_IDEOGRAPHS_EXTENSION_E';
	const CJK_UNIFIED_IDEOGRAPHS_EXTENSION_F      = 'CJK_UNIFIED_IDEOGRAPHS_EXTENSION_F';
	const CJK_COMPATIBILITY_IDEOGRAPHS_SUPPLEMENT = 'CJK_COMPATIBILITY_IDEOGRAPHS_SUPPLEMENT';
	const TAGS                                    = 'TAGS';
	const VARIATION_SELECTORS_SUPPLEMENT          = 'VARIATION_SELECTORS_SUPPLEMENT';
	const SUPPLEMENTARY_PRIVATE_USE_AREA_A        = 'SUPPLEMENTARY_PRIVATE_USE_AREA_A';
	const SUPPLEMENTARY_PRIVATE_USE_AREA_B        = 'SUPPLEMENTARY_PRIVATE_USE_AREA_B';

	const UNKNOWN = 'UNKNOWN';

	const FILTERS = array(
		self::BASIC_LATIN                             => array( 0x0000, 0x007F ),
		self::LATIN_1_SUPPLEMENT                      => array( 0x0080, 0x00FF ),
		self::LATIN_EXTENDED_A                        => array( 0x0100, 0x017F ),
		self::LATIN_EXTENDED_B                        => array( 0x0180, 0x024F ),
		self::IPA_EXTENSIONS                          => array( 0x0250, 0x02AF ),
		self::SPACING_MODIFIER_LETTERS                => array( 0x02B0, 0x02FF ),
		self::COMBINING_DIACRITICAL_MARKS             => array( 0x0300, 0x036F ),
		self::GREEK_AND_COPTIC                        => array( 0x0370, 0x03FF ),
		self::CYRILLIC                                => array( 0x0400, 0x04FF ),
		self::CYRILLIC_SUPPLEMENT                     => array( 0x0500, 0x052F ),
		self::ARMENIAN                                => array( 0x0530, 0x058F ),
		self::HEBREW                                  => array( 0x0590, 0x05FF ),
		self::ARABIC                                  => array( 0x0600, 0x06FF ),
		self::SYRIAC                                  => array( 0x0700, 0x074F ),
		self::ARABIC_SUPPLEMENT                       => array( 0x0750, 0x077F ),
		self::THAANA                                  => array( 0x0780, 0x07BF ),
		self::NKO                                     => array( 0x07C0, 0x07FF ),
		self::SAMARITAN                               => array( 0x0800, 0x083F ),
		self::MANDAIC                                 => array( 0x0840, 0x085F ),
		self::SYRIAC_SUPPLEMENT                       => array( 0x0860, 0x086F ),
		self::ARABIC_EXTENDED_A                       => array( 0x08A0, 0x08FF ),
		self::DEVANAGARI                              => array( 0x0900, 0x097F ),
		self::BENGALI                                 => array( 0x0980, 0x09FF ),
		self::GURMUKHI                                => array( 0x0A00, 0x0A7F ),
		self::GUJARATI                                => array( 0x0A80, 0x0AFF ),
		self::ORIYA                                   => array( 0x0B00, 0x0B7F ),
		self::TAMIL                                   => array( 0x0B80, 0x0BFF ),
		self::TELUGU                                  => array( 0x0C00, 0x0C7F ),
		self::KANNADA                                 => array( 0x0C80, 0x0CFF ),
		self::MALAYALAM                               => array( 0x0D00, 0x0D7F ),
		self::SINHALA                                 => array( 0x0D80, 0x0DFF ),
		self::THAI                                    => array( 0x0E00, 0x0E7F ),
		self::LAO                                     => array( 0x0E80, 0x0EFF ),
		self::TIBETAN                                 => array( 0x0F00, 0x0FFF ),
		self::MYANMAR                                 => array( 0x1000, 0x109F ),
		self::GEORGIAN                                => array( 0x10A0, 0x10FF ),
		self::HANGUL_JAMO                             => array( 0x1100, 0x11FF ),
		self::ETHIOPIC                                => array( 0x1200, 0x137F ),
		self::ETHIOPIC_SUPPLEMENT                     => array( 0x1380, 0x139F ),
		self::CHEROKEE                                => array( 0x13A0, 0x13FF ),
		self::UNIFIED_CANADIAN_ABORIGINAL_SYLLABICS   => array( 0x1400, 0x167F ),
		self::OGHAM                                   => array( 0x1680, 0x169F ),
		self::RUNIC                                   => array( 0x16A0, 0x16FF ),
		self::TAGALOG                                 => array( 0x1700, 0x171F ),
		self::HANUNOO                                 => array( 0x1720, 0x173F ),
		self::BUHID                                   => array( 0x1740, 0x175F ),
		self::TAGBANWA                                => array( 0x1760, 0x177F ),
		self::KHMER                                   => array( 0x1780, 0x17FF ),
		self::MONGOLIAN                               => array( 0x1800, 0x18AF ),
		self::UNIFIED_CANADIAN_ABORIGINAL_SYLLABICS_EXTENDED => array( 0x18B0, 0x18FF ),
		self::LIMBU                                   => array( 0x1900, 0x194F ),
		self::TAI_LE                                  => array( 0x1950, 0x197F ),
		self::NEW_TAI_LUE                             => array( 0x1980, 0x19DF ),
		self::KHMER_SYMBOLS                           => array( 0x19E0, 0x19FF ),
		self::BUGINESE                                => array( 0x1A00, 0x1A1F ),
		self::TAI_THAM                                => array( 0x1A20, 0x1AAF ),
		self::COMBINING_DIACRITICAL_MARKS_EXTENDED    => array( 0x1AB0, 0x1AFF ),
		self::BALINESE                                => array( 0x1B00, 0x1B7F ),
		self::SUNDANESE                               => array( 0x1B80, 0x1BBF ),
		self::BATAK                                   => array( 0x1BC0, 0x1BFF ),
		self::LEPCHA                                  => array( 0x1C00, 0x1C4F ),
		self::OL_CHIKI                                => array( 0x1C50, 0x1C7F ),
		self::CYRILLIC_EXTENDED_C                     => array( 0x1C80, 0x1C8F ),
		self::GEORGIAN_EXTENDED                       => array( 0x1C90, 0x1CBF ),
		self::SUNDANESE_SUPPLEMENT                    => array( 0x1CC0, 0x1CCF ),
		self::VEDIC_EXTENSIONS                        => array( 0x1CD0, 0x1CFF ),
		self::PHONETIC_EXTENSIONS                     => array( 0x1D00, 0x1D7F ),
		self::PHONETIC_EXTENSIONS_SUPPLEMENT          => array( 0x1D80, 0x1DBF ),
		self::COMBINING_DIACRITICAL_MARKS_SUPPLEMENT  => array( 0x1DC0, 0x1DFF ),
		self::LATIN_EXTENDED_ADDITIONAL               => array( 0x1E00, 0x1EFF ),
		self::GREEK_EXTENDED                          => array( 0x1F00, 0x1FFF ),
		self::GENERAL_PUNCTUATION                     => array( 0x2000, 0x206F ),
		self::SUPERSCRIPTS_AND_SUBSCRIPTS             => array( 0x2070, 0x209F ),
		self::CURRENCY_SYMBOLS                        => array( 0x20A0, 0x20CF ),
		self::COMBINING_DIACRITICAL_MARKS_FOR_SYMBOLS => array( 0x20D0, 0x20FF ),
		self::LETTERLIKE_SYMBOLS                      => array( 0x2100, 0x214F ),
		self::NUMBER_FORMS                            => array( 0x2150, 0x218F ),
		self::ARROWS                                  => array( 0x2190, 0x21FF ),
		self::MATHEMATICAL_OPERATORS                  => array( 0x2200, 0x22FF ),
		self::MISCELLANEOUS_TECHNICAL                 => array( 0x2300, 0x23FF ),
		self::CONTROL_PICTURES                        => array( 0x2400, 0x243F ),
		self::OPTICAL_CHARACTER_RECOGNITION           => array( 0x2440, 0x245F ),
		self::ENCLOSED_ALPHANUMERICS                  => array( 0x2460, 0x24FF ),
		self::BOX_DRAWING                             => array( 0x2500, 0x257F ),
		self::BLOCK_ELEMENTS                          => array( 0x2580, 0x259F ),
		self::GEOMETRIC_SHAPES                        => array( 0x25A0, 0x25FF ),
		self::MISCELLANEOUS_SYMBOLS                   => array( 0x2600, 0x26FF ),
		self::DINGBATS                                => array( 0x2700, 0x27BF ),
		self::MISCELLANEOUS_MATHEMATICAL_SYMBOLS_A    => array( 0x27C0, 0x27EF ),
		self::SUPPLEMENTAL_ARROWS_A                   => array( 0x27F0, 0x27FF ),
		self::BRAILLE_PATTERNS                        => array( 0x2800, 0x28FF ),
		self::SUPPLEMENTAL_ARROWS_B                   => array( 0x2900, 0x297F ),
		self::MISCELLANEOUS_MATHEMATICAL_SYMBOLS_B    => array( 0x2980, 0x29FF ),
		self::SUPPLEMENTAL_MATHEMATICAL_OPERATORS     => array( 0x2A00, 0x2AFF ),
		self::MISCELLANEOUS_SYMBOLS_AND_ARROWS        => array( 0x2B00, 0x2BFF ),
		self::GLAGOLITIC                              => array( 0x2C00, 0x2C5F ),
		self::LATIN_EXTENDED_C                        => array( 0x2C60, 0x2C7F ),
		self::COPTIC                                  => array( 0x2C80, 0x2CFF ),
		self::GEORGIAN_SUPPLEMENT                     => array( 0x2D00, 0x2D2F ),
		self::TIFINAGH                                => array( 0x2D30, 0x2D7F ),
		self::ETHIOPIC_EXTENDED                       => array( 0x2D80, 0x2DDF ),
		self::CYRILLIC_EXTENDED_A                     => array( 0x2DE0, 0x2DFF ),
		self::SUPPLEMENTAL_PUNCTUATION                => array( 0x2E00, 0x2E7F ),
		self::CJK_RADICALS_SUPPLEMENT                 => array( 0x2E80, 0x2EFF ),
		self::KANGXI_RADICALS                         => array( 0x2F00, 0x2FDF ),
		self::IDEOGRAPHIC_DESCRIPTION_CHARACTERS      => array( 0x2FF0, 0x2FFF ),
		self::CJK_SYMBOLS_AND_PUNCTUATION             => array( 0x3000, 0x303F ),
		self::HIRAGANA                                => array( 0x3040, 0x309F ),
		self::KATAKANA                                => array( 0x30A0, 0x30FF ),
		self::BOPOMOFO                                => array( 0x3100, 0x312F ),
		self::HANGUL_COMPATIBILITY_JAMO               => array( 0x3130, 0x318F ),
		self::KANBUN                                  => array( 0x3190, 0x319F ),
		self::BOPOMOFO_EXTENDED                       => array( 0x31A0, 0x31BF ),
		self::CJK_STROKES                             => array( 0x31C0, 0x31EF ),
		self::KATAKANA_PHONETIC_EXTENSIONS            => array( 0x31F0, 0x31FF ),
		self::ENCLOSED_CJK_LETTERS_AND_MONTHS         => array( 0x3200, 0x32FF ),
		self::CJK_COMPATIBILITY                       => array( 0x3300, 0x33FF ),
		self::CJK_UNIFIED_IDEOGRAPHS_EXTENSION_A      => array( 0x3400, 0x4DBF ),
		self::YIJING_HEXAGRAM_SYMBOLS                 => array( 0x4DC0, 0x4DFF ),
		self::CJK_UNIFIED_IDEOGRAPHS                  => array( 0x4E00, 0x9FFF ),
		self::YI_SYLLABLES                            => array( 0xA000, 0xA48F ),
		self::YI_RADICALS                             => array( 0xA490, 0xA4CF ),
		self::LISU                                    => array( 0xA4D0, 0xA4FF ),
		self::VAI                                     => array( 0xA500, 0xA63F ),
		self::CYRILLIC_EXTENDED_B                     => array( 0xA640, 0xA69F ),
		self::BAMUM                                   => array( 0xA6A0, 0xA6FF ),
		self::MODIFIER_TONE_LETTERS                   => array( 0xA700, 0xA71F ),
		self::LATIN_EXTENDED_D                        => array( 0xA720, 0xA7FF ),
		self::SYLOTI_NAGRI                            => array( 0xA800, 0xA82F ),
		self::COMMON_INDIC_NUMBER_FORMS               => array( 0xA830, 0xA83F ),
		self::PHAGS_PA                                => array( 0xA840, 0xA87F ),
		self::SAURASHTRA                              => array( 0xA880, 0xA8DF ),
		self::DEVANAGARI_EXTENDED                     => array( 0xA8E0, 0xA8FF ),
		self::KAYAH_LI                                => array( 0xA900, 0xA92F ),
		self::REJANG                                  => array( 0xA930, 0xA95F ),
		self::HANGUL_JAMO_EXTENDED_A                  => array( 0xA960, 0xA97F ),
		self::JAVANESE                                => array( 0xA980, 0xA9DF ),
		self::MYANMAR_EXTENDED_B                      => array( 0xA9E0, 0xA9FF ),
		self::CHAM                                    => array( 0xAA00, 0xAA5F ),
		self::MYANMAR_EXTENDED_A                      => array( 0xAA60, 0xAA7F ),
		self::TAI_VIET                                => array( 0xAA80, 0xAADF ),
		self::MEETEI_MAYEK_EXTENSIONS                 => array( 0xAAE0, 0xAAFF ),
		self::ETHIOPIC_EXTENDED_A                     => array( 0xAB00, 0xAB2F ),
		self::LATIN_EXTENDED_E                        => array( 0xAB30, 0xAB6F ),
		self::CHEROKEE_SUPPLEMENT                     => array( 0xAB70, 0xABBF ),
		self::MEETEI_MAYEK                            => array( 0xABC0, 0xABFF ),
		self::HANGUL_SYLLABLES                        => array( 0xAC00, 0xD7AF ),
		self::HANGUL_JAMO_EXTENDED_B                  => array( 0xD7B0, 0xD7FF ),
		self::HIGH_SURROGATES                         => array( 0xD800, 0xDB7F ),
		self::HIGH_PRIVATE_USE_SURROGATES             => array( 0xDB80, 0xDBFF ),
		self::LOW_SURROGATES                          => array( 0xDC00, 0xDFFF ),
		self::PRIVATE_USE_AREA                        => array( 0xE000, 0xF8FF ),
		self::CJK_COMPATIBILITY_IDEOGRAPHS            => array( 0xF900, 0xFAFF ),
		self::ALPHABETIC_PRESENTATION_FORMS           => array( 0xFB00, 0xFB4F ),
		self::ARABIC_PRESENTATION_FORMS_A             => array( 0xFB50, 0xFDFF ),
		self::VARIATION_SELECTORS                     => array( 0xFE00, 0xFE0F ),
		self::VERTICAL_FORMS                          => array( 0xFE10, 0xFE1F ),
		self::COMBINING_HALF_MARKS                    => array( 0xFE20, 0xFE2F ),
		self::CJK_COMPATIBILITY_FORMS                 => array( 0xFE30, 0xFE4F ),
		self::SMALL_FORM_VARIANTS                     => array( 0xFE50, 0xFE6F ),
		self::ARABIC_PRESENTATION_FORMS_B             => array( 0xFE70, 0xFEFF ),
		self::HALFWIDTH_AND_FULLWIDTH_FORMS           => array( 0xFF00, 0xFFEF ),
		self::SPECIALS                                => array( 0xFFF0, 0xFFFF ),
		self::LINEAR_B_SYLLABARY                      => array( 0x10000, 0x1007F ),
		self::LINEAR_B_IDEOGRAMS                      => array( 0x10080, 0x100FF ),
		self::AEGEAN_NUMBERS                          => array( 0x10100, 0x1013F ),
		self::ANCIENT_GREEK_NUMBERS                   => array( 0x10140, 0x1018F ),
		self::ANCIENT_SYMBOLS                         => array( 0x10190, 0x101CF ),
		self::PHAISTOS_DISC                           => array( 0x101D0, 0x101FF ),
		self::LYCIAN                                  => array( 0x10280, 0x1029F ),
		self::CARIAN                                  => array( 0x102A0, 0x102DF ),
		self::COPTIC_EPACT_NUMBERS                    => array( 0x102E0, 0x102FF ),
		self::OLD_ITALIC                              => array( 0x10300, 0x1032F ),
		self::GOTHIC                                  => array( 0x10330, 0x1034F ),
		self::OLD_PERMIC                              => array( 0x10350, 0x1037F ),
		self::UGARITIC                                => array( 0x10380, 0x1039F ),
		self::OLD_PERSIAN                             => array( 0x103A0, 0x103DF ),
		self::DESERET                                 => array( 0x10400, 0x1044F ),
		self::SHAVIAN                                 => array( 0x10450, 0x1047F ),
		self::OSMANYA                                 => array( 0x10480, 0x104AF ),
		self::OSAGE                                   => array( 0x104B0, 0x104FF ),
		self::ELBASAN                                 => array( 0x10500, 0x1052F ),
		self::CAUCASIAN_ALBANIAN                      => array( 0x10530, 0x1056F ),
		self::LINEAR_A                                => array( 0x10600, 0x1077F ),
		self::CYPRIOT_SYLLABARY                       => array( 0x10800, 0x1083F ),
		self::IMPERIAL_ARAMAIC                        => array( 0x10840, 0x1085F ),
		self::PALMYRENE                               => array( 0x10860, 0x1087F ),
		self::NABATAEAN                               => array( 0x10880, 0x108AF ),
		self::HATRAN                                  => array( 0x108E0, 0x108FF ),
		self::PHOENICIAN                              => array( 0x10900, 0x1091F ),
		self::LYDIAN                                  => array( 0x10920, 0x1093F ),
		self::MEROITIC_HIEROGLYPHS                    => array( 0x10980, 0x1099F ),
		self::MEROITIC_CURSIVE                        => array( 0x109A0, 0x109FF ),
		self::KHAROSHTHI                              => array( 0x10A00, 0x10A5F ),
		self::OLD_SOUTH_ARABIAN                       => array( 0x10A60, 0x10A7F ),
		self::OLD_NORTH_ARABIAN                       => array( 0x10A80, 0x10A9F ),
		self::MANICHAEAN                              => array( 0x10AC0, 0x10AFF ),
		self::AVESTAN                                 => array( 0x10B00, 0x10B3F ),
		self::INSCRIPTIONAL_PARTHIAN                  => array( 0x10B40, 0x10B5F ),
		self::INSCRIPTIONAL_PAHLAVI                   => array( 0x10B60, 0x10B7F ),
		self::PSALTER_PAHLAVI                         => array( 0x10B80, 0x10BAF ),
		self::OLD_TURKIC                              => array( 0x10C00, 0x10C4F ),
		self::OLD_HUNGARIAN                           => array( 0x10C80, 0x10CFF ),
		self::HANIFI_ROHINGYA                         => array( 0x10D00, 0x10D3F ),
		self::RUMI_NUMERAL_SYMBOLS                    => array( 0x10E60, 0x10E7F ),
		self::OLD_SOGDIAN                             => array( 0x10F00, 0x10F2F ),
		self::SOGDIAN                                 => array( 0x10F30, 0x10F6F ),
		self::BRAHMI                                  => array( 0x11000, 0x1107F ),
		self::KAITHI                                  => array( 0x11080, 0x110CF ),
		self::SORA_SOMPENG                            => array( 0x110D0, 0x110FF ),
		self::CHAKMA                                  => array( 0x11100, 0x1114F ),
		self::MAHAJANI                                => array( 0x11150, 0x1117F ),
		self::SHARADA                                 => array( 0x11180, 0x111DF ),
		self::SINHALA_ARCHAIC_NUMBERS                 => array( 0x111E0, 0x111FF ),
		self::KHOJKI                                  => array( 0x11200, 0x1124F ),
		self::MULTANI                                 => array( 0x11280, 0x112AF ),
		self::KHUDAWADI                               => array( 0x112B0, 0x112FF ),
		self::GRANTHA                                 => array( 0x11300, 0x1137F ),
		self::NEWA                                    => array( 0x11400, 0x1147F ),
		self::TIRHUTA                                 => array( 0x11480, 0x114DF ),
		self::SIDDHAM                                 => array( 0x11580, 0x115FF ),
		self::MODI                                    => array( 0x11600, 0x1165F ),
		self::MONGOLIAN_SUPPLEMENT                    => array( 0x11660, 0x1167F ),
		self::TAKRI                                   => array( 0x11680, 0x116CF ),
		self::AHOM                                    => array( 0x11700, 0x1173F ),
		self::DOGRA                                   => array( 0x11800, 0x1184F ),
		self::WARANG_CITI                             => array( 0x118A0, 0x118FF ),
		self::ZANABAZAR_SQUARE                        => array( 0x11A00, 0x11A4F ),
		self::SOYOMBO                                 => array( 0x11A50, 0x11AAF ),
		self::PAU_CIN_HAU                             => array( 0x11AC0, 0x11AFF ),
		self::BHAIKSUKI                               => array( 0x11C00, 0x11C6F ),
		self::MARCHEN                                 => array( 0x11C70, 0x11CBF ),
		self::MASARAM_GONDI                           => array( 0x11D00, 0x11D5F ),
		self::GUNJALA_GONDI                           => array( 0x11D60, 0x11DAF ),
		self::MAKASAR                                 => array( 0x11EE0, 0x11EFF ),
		self::CUNEIFORM                               => array( 0x12000, 0x123FF ),
		self::CUNEIFORM_NUMBERS_AND_PUNCTUATION       => array( 0x12400, 0x1247F ),
		self::EARLY_DYNASTIC_CUNEIFORM                => array( 0x12480, 0x1254F ),
		self::EGYPTIAN_HIEROGLYPHS                    => array( 0x13000, 0x1342F ),
		self::ANATOLIAN_HIEROGLYPHS                   => array( 0x14400, 0x1467F ),
		self::BAMUM_SUPPLEMENT                        => array( 0x16800, 0x16A3F ),
		self::MRO                                     => array( 0x16A40, 0x16A6F ),
		self::BASSA_VAH                               => array( 0x16AD0, 0x16AFF ),
		self::PAHAWH_HMONG                            => array( 0x16B00, 0x16B8F ),
		self::MEDEFAIDRIN                             => array( 0x16E40, 0x16E9F ),
		self::MIAO                                    => array( 0x16F00, 0x16F9F ),
		self::IDEOGRAPHIC_SYMBOLS_AND_PUNCTUATION     => array( 0x16FE0, 0x16FFF ),
		self::TANGUT                                  => array( 0x17000, 0x187FF ),
		self::TANGUT_COMPONENTS                       => array( 0x18800, 0x18AFF ),
		self::KANA_SUPPLEMENT                         => array( 0x1B000, 0x1B0FF ),
		self::KANA_EXTENDED_A                         => array( 0x1B100, 0x1B12F ),
		self::NUSHU                                   => array( 0x1B170, 0x1B2FF ),
		self::DUPLOYAN                                => array( 0x1BC00, 0x1BC9F ),
		self::SHORTHAND_FORMAT_CONTROLS               => array( 0x1BCA0, 0x1BCAF ),
		self::BYZANTINE_MUSICAL_SYMBOLS               => array( 0x1D000, 0x1D0FF ),
		self::MUSICAL_SYMBOLS                         => array( 0x1D100, 0x1D1FF ),
		self::ANCIENT_GREEK_MUSICAL_NOTATION          => array( 0x1D200, 0x1D24F ),
		self::MAYAN_NUMERALS                          => array( 0x1D2E0, 0x1D2FF ),
		self::TAI_XUAN_JING_SYMBOLS                   => array( 0x1D300, 0x1D35F ),
		self::COUNTING_ROD_NUMERALS                   => array( 0x1D360, 0x1D37F ),
		self::MATHEMATICAL_ALPHANUMERIC_SYMBOLS       => array( 0x1D400, 0x1D7FF ),
		self::SUTTON_SIGNWRITING                      => array( 0x1D800, 0x1DAAF ),
		self::GLAGOLITIC_SUPPLEMENT                   => array( 0x1E000, 0x1E02F ),
		self::MENDE_KIKAKUI                           => array( 0x1E800, 0x1E8DF ),
		self::ADLAM                                   => array( 0x1E900, 0x1E95F ),
		self::INDIC_SIYAQ_NUMBERS                     => array( 0x1EC70, 0x1ECBF ),
		self::ARABIC_MATHEMATICAL_ALPHABETIC_SYMBOLS  => array( 0x1EE00, 0x1EEFF ),
		self::MAHJONG_TILES                           => array( 0x1F000, 0x1F02F ),
		self::DOMINO_TILES                            => array( 0x1F030, 0x1F09F ),
		self::PLAYING_CARDS                           => array( 0x1F0A0, 0x1F0FF ),
		self::ENCLOSED_ALPHANUMERIC_SUPPLEMENT        => array( 0x1F100, 0x1F1FF ),
		self::ENCLOSED_IDEOGRAPHIC_SUPPLEMENT         => array( 0x1F200, 0x1F2FF ),
		self::MISCELLANEOUS_SYMBOLS_AND_PICTOGRAPHS   => array( 0x1F300, 0x1F5FF ),
		self::EMOTICONS                               => array( 0x1F600, 0x1F64F ),
		self::ORNAMENTAL_DINGBATS                     => array( 0x1F650, 0x1F67F ),
		self::TRANSPORT_AND_MAP_SYMBOLS               => array( 0x1F680, 0x1F6FF ),
		self::ALCHEMICAL_SYMBOLS                      => array( 0x1F700, 0x1F77F ),
		self::GEOMETRIC_SHAPES_EXTENDED               => array( 0x1F780, 0x1F7FF ),
		self::SUPPLEMENTAL_ARROWS_C                   => array( 0x1F800, 0x1F8FF ),
		self::SUPPLEMENTAL_SYMBOLS_AND_PICTOGRAPHS    => array( 0x1F900, 0x1F9FF ),
		self::CHESS_SYMBOLS                           => array( 0x1FA00, 0x1FA6F ),
		self::CJK_UNIFIED_IDEOGRAPHS_EXTENSION_B      => array( 0x20000, 0x2A6DF ),
		self::CJK_UNIFIED_IDEOGRAPHS_EXTENSION_C      => array( 0x2A700, 0x2B73F ),
		self::CJK_UNIFIED_IDEOGRAPHS_EXTENSION_D      => array( 0x2B740, 0x2B81F ),
		self::CJK_UNIFIED_IDEOGRAPHS_EXTENSION_E      => array( 0x2B820, 0x2CEAF ),
		self::CJK_UNIFIED_IDEOGRAPHS_EXTENSION_F      => array( 0x2CEB0, 0x2EBEF ),
		self::CJK_COMPATIBILITY_IDEOGRAPHS_SUPPLEMENT => array( 0x2F800, 0x2FA1F ),
		self::TAGS                                    => array( 0xE0000, 0xE007F ),
		self::VARIATION_SELECTORS_SUPPLEMENT          => array( 0xE0100, 0xE01EF ),
		self::SUPPLEMENTARY_PRIVATE_USE_AREA_A        => array( 0xF0000, 0xFFFFF ),
		self::SUPPLEMENTARY_PRIVATE_USE_AREA_B        => array( 0x100000, 0x10FFFF ),
	);

	public static function to_alphanumeric( $str ) {
		if ( is_array( $str ) ) {
			return array_map( array( __CLASS__, 'to_alphanumeric' ), $str );
		} elseif ( is_scalar( $str ) ) {
			$str = self::decode_html( $str );
			$str = remove_accents( $str );
			$str = preg_replace( '/[^ \w-]/', ' ', $str );
			$str = preg_replace( '/\s+/', ' ', $str );

			return wc_clean( $str );
		} else {
			return $str;
		}
	}

	protected static function decode_html( $str ) {
		if ( ! is_bool( $str ) ) {
			$str = html_entity_decode( $str, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}

		return $str;
	}

	/**
	 * @param string|string[] $str
	 * @param mixed $filters
	 *
	 * @return string|string[]
	 */
	public static function to_utf8( $str, $filters = array() ) {
		if ( is_array( $str ) ) {
			return array_map(
				function ( $str ) use ( $filters ) {
					return self::to_utf8( $str, $filters );
				},
				$str
			);
		} elseif ( is_scalar( $str ) ) {
			$str = self::decode_html( $str );

			if ( ! empty( $filters ) ) {
				/**
				 * Replace common chars with their non utf-8 representation before
				 * removing any illegal chars.
				 */
				if ( in_array( self::BASIC_LATIN, $filters, true ) ) {
					$str = str_replace( array( '–', '—' ), '-', $str );
					$str = str_replace( '’', "'", $str );
				}

				$pattern_str = self::get_utf8_patterns( $filters );

				if ( ! empty( $pattern_str ) ) {
					$str = preg_replace( $pattern_str, '', $str );
				}
			}

			return $str;
		} else {
			return $str;
		}
	}

	protected static function is_utf8_range( $input ) {
		return is_array( $input ) && 2 === count( $input ) && self::is_utf8_codepoint( $input[0] ) && self::is_utf8_codepoint( $input[1] );
	}

	protected static function is_utf8_codepoint( $input ) {
		return is_numeric( $input ) && $input > 0x0 && $input <= 0x10FFFF;
	}

	protected static function get_utf8_pattern( $filter ) {
		if ( self::is_utf8_codepoint( $filter ) ) {
			$point = dechex( $filter );

			return "\\x{{$point}}";
		} elseif ( array_key_exists( $filter, self::FILTERS ) ) {
			$range = self::FILTERS[ $filter ];
			$from  = dechex( $range[0] );
			$to    = dechex( $range[1] );

			return "\\x{{$from}}-\\x{{$to}}";
		} elseif ( self::is_utf8_range( $filter ) ) {
			$from = dechex( $filter[0] );
			$to   = dechex( $filter[1] );

			return "\\x{{$from}}-\\x{{$to}}";
		} else {
			return false;
		}
	}

	protected static function get_utf8_patterns( $filters ) {
		$patterns = array();

		foreach ( $filters as $filter ) {
			if ( $pattern = self::get_utf8_pattern( $filter ) ) {
				$patterns[] = $pattern;
			}
		}

		if ( empty( $patterns ) ) {
			return '';
		}

		return '/[^' . implode( '', $patterns ) . ']/u';
	}
}
