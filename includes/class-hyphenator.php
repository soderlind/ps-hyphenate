<?php
/**
 * Soft hyphen exception engine.
 *
 * @package PS_Hyphenate
 */

namespace PS_Hyphenate;

use Org\Heigl\Hyphenator\Hyphenator as Tex_Hyphenator;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Hyphenator {
	const SOFT_HYPHEN = "\xC2\xAD";

	/**
	 * Settings service.
	 *
	 * @var Settings
	 */
	private $settings;

	/**
	 * Parsed exception cache.
	 *
	 * @var array<string,array<string,string>>|null
	 */
	private $exceptions = null;

	/**
	 * TeX hyphenator instances keyed by dictionary locale and minimum length.
	 *
	 * @var array<string,Tex_Hyphenator>
	 */
	private $pattern_engines = array();

	/**
	 * Constructor.
	 *
	 * @param Settings $settings Settings service.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Hyphenate eligible words in a text node.
	 *
	 * @param string $text Text node value.
	 * @param string $locale Current locale.
	 * @return string
	 */
	public function hyphenate_text( $text, $locale ) {
		if ( '' === $text || false !== strpos( $text, self::SOFT_HYPHEN ) ) {
			return $text;
		}

		$parts = preg_split( '/([^\p{L}\p{M}]+)/u', $text, -1, PREG_SPLIT_DELIM_CAPTURE );

		if ( ! is_array( $parts ) ) {
			return $text;
		}

		$options         = $this->settings->get_options();
		$min_word_length = absint( $options['min_word_length'] );

		foreach ( $parts as $index => $part ) {
			if ( '' === $part || $this->string_length( $part ) < $min_word_length ) {
				continue;
			}

			if ( $this->looks_like_machine_token( $part ) ) {
				continue;
			}

			$parts[ $index ] = $this->hyphenate_word( $part, $locale );
		}

		return implode( '', $parts );
	}

	/**
	 * Hyphenate one word.
	 *
	 * @param string $word Word.
	 * @param string $locale Current locale.
	 * @return string
	 */
	private function hyphenate_word( $word, $locale ) {
		$normalized = $this->normalize_word( $word );
		$exceptions = $this->get_exceptions();
		$locale     = $this->normalize_locale( $locale );

		$replacement = null;

		if ( isset( $exceptions[ $locale ][ $normalized ] ) ) {
			$replacement = $exceptions[ $locale ][ $normalized ];
		} elseif ( isset( $exceptions['global'][ $normalized ] ) ) {
			$replacement = $exceptions['global'][ $normalized ];
		}

		$hyphenated = null === $replacement ? $this->hyphenate_word_with_patterns( $word, $locale ) : $this->match_original_casing( $word, $replacement );
		$hyphenated = $this->normalize_soft_hyphens( $hyphenated );

		/**
		 * Filters a hyphenated word before it is written to rendered HTML.
		 *
		 * Return the original word to disable automatic handling, or a string with
		 * soft hyphens to provide a custom pattern-based engine.
		 *
		 * @param string $hyphenated Hyphenated word.
		 * @param string $word Original word.
		 * @param string $locale Normalized locale.
		 */
		return (string) \apply_filters( 'ps_hyphenate_hyphenated_word', $hyphenated, $word, $locale );
	}

	/**
	 * Get parsed exceptions.
	 *
	 * @return array<string,array<string,string>>
	 */
	private function get_exceptions() {
		if ( null !== $this->exceptions ) {
			return $this->exceptions;
		}

		$options          = $this->settings->get_options();
		$raw_exceptions   = isset( $options['exceptions'] ) ? (string) $options['exceptions'] : '';
		$this->exceptions = array( 'global' => array() );

		$lines = preg_split( '/\r\n|\r|\n/', $raw_exceptions );

		if ( ! is_array( $lines ) ) {
			return $this->exceptions;
		}

		foreach ( $lines as $line ) {
			$line = trim( $line );
			$uses_shorthand = false;

			if ( '' === $line || 0 === strpos( $line, '#' ) ) {
				continue;
			}

			if ( false === strpos( $line, '=' ) ) {
				$uses_shorthand = true;
				$word        = str_replace( '-', '', $line );
				$replacement = $line;
			} else {
				list( $word, $replacement ) = array_map( 'trim', explode( '=', $line, 2 ) );
			}

			$locale                    = 'global';

			if ( false !== strpos( $word, ':' ) ) {
				list( $locale, $word ) = array_map( 'trim', explode( ':', $word, 2 ) );
				$locale               = $this->normalize_locale( $locale );

				if ( $uses_shorthand && false !== strpos( $replacement, ':' ) ) {
					$replacement = trim( explode( ':', $replacement, 2 )[1] );
				}
			}

			$word        = $this->normalize_word( $word );
			$replacement = $this->parse_exception_replacement( $replacement );

			if ( '' === $word || '' === $replacement ) {
				continue;
			}

			if ( ! isset( $this->exceptions[ $locale ] ) ) {
				$this->exceptions[ $locale ] = array();
			}

			$this->exceptions[ $locale ][ $word ] = $replacement;
		}

		return $this->exceptions;
	}

	/**
	 * Normalize a word key.
	 *
	 * @param string $word Word.
	 * @return string
	 */
	private function normalize_word( $word ) {
		$word = str_replace( self::SOFT_HYPHEN, '', trim( $word ) );

		return $this->lower( $word );
	}

	/**
	 * Parse exception replacement syntax.
	 *
	 * Single hyphens mark soft hyphen positions. Double hyphens mark a soft
	 * hyphen opportunity followed by a visible compound hyphen.
	 *
	 * @param string $replacement Replacement pattern.
	 * @return string
	 */
	private function parse_exception_replacement( $replacement ) {
		$visible_hyphen = "\0PS_HYPHENATE_VISIBLE_HYPHEN\0";
		$replacement    = str_replace( '--', $visible_hyphen, $replacement );
		$replacement    = str_replace( '-', self::SOFT_HYPHEN, $replacement );

		return str_replace( $visible_hyphen, self::SOFT_HYPHEN . '-', $replacement );
	}

	/**
	 * Normalize locale names.
	 *
	 * @param string $locale Locale.
	 * @return string
	 */
	private function normalize_locale( $locale ) {
		return strtolower( str_replace( '-', '_', trim( $locale ) ) );
	}

	/**
	 * Hyphenate a word with TeX dictionaries when a supported locale is available.
	 *
	 * @param string $word Word.
	 * @param string $locale Normalized locale.
	 * @return string
	 */
	private function hyphenate_word_with_patterns( $word, $locale ) {
		$options = $this->settings->get_options();

		if ( empty( $options['pattern_enabled'] ) || ! class_exists( Tex_Hyphenator::class ) ) {
			return $word;
		}

		$dictionary_locale = $this->get_dictionary_locale( $locale );

		if ( null === $dictionary_locale ) {
			return $word;
		}

		try {
			$hyphenated = $this->get_pattern_engine( $dictionary_locale, \absint( $options['min_word_length'] ) )->hyphenate( $word );
		} catch ( \Throwable $throwable ) {
			return $word;
		}

		return is_string( $hyphenated ) && '' !== $hyphenated ? $this->match_original_casing( $word, $hyphenated ) : $word;
	}

	/**
	 * Get a configured TeX hyphenator for a dictionary locale.
	 *
	 * @param string $dictionary_locale Dictionary locale.
	 * @param int    $min_word_length Minimum word length.
	 * @return Tex_Hyphenator
	 */
	private function get_pattern_engine( $dictionary_locale, $min_word_length ) {
		$key = $dictionary_locale . ':' . $min_word_length;

		if ( isset( $this->pattern_engines[ $key ] ) ) {
			return $this->pattern_engines[ $key ];
		}

		$engine  = new Tex_Hyphenator();
		$options = $engine->getOptions();
		$options->setDefaultLocale( $dictionary_locale );
		$options->setHyphen( self::SOFT_HYPHEN );
		$options->setMinWordLength( $min_word_length );
		$engine->setOptions( $options );

		$this->pattern_engines[ $key ] = $engine;

		return $engine;
	}

	/**
	 * Map WordPress locales to dictionaries shipped by org_heigl/hyphenator.
	 *
	 * @param string $locale Normalized locale.
	 * @return string|null
	 */
	private function get_dictionary_locale( $locale ) {
		$locale = $this->normalize_locale( $locale );
		$map    = array(
			'af' => 'af_ZA',
			'af_za' => 'af_ZA',
			'bg' => 'bg_BG',
			'bg_bg' => 'bg_BG',
			'ca' => 'ca',
			'ca_es' => 'ca',
			'cs' => 'cs_CZ',
			'cs_cz' => 'cs_CZ',
			'da' => 'da_DK',
			'da_dk' => 'da_DK',
			'de' => 'de_DE',
			'de_at' => 'de_AT',
			'de_ch' => 'de_CH',
			'de_de' => 'de_DE',
			'el' => 'el_GR',
			'el_gr' => 'el_GR',
			'en' => 'en_US',
			'en_au' => 'en_GB',
			'en_ca' => 'en_US',
			'en_gb' => 'en_GB',
			'en_nz' => 'en_GB',
			'en_us' => 'en_US',
			'es' => 'es',
			'es_ar' => 'es',
			'es_cl' => 'es',
			'es_co' => 'es',
			'es_es' => 'es',
			'es_mx' => 'es',
			'es_pe' => 'es',
			'es_ve' => 'es',
			'et' => 'et_EE',
			'et_ee' => 'et_EE',
			'fr' => 'fr',
			'fr_be' => 'fr',
			'fr_ca' => 'fr',
			'fr_ch' => 'fr',
			'fr_fr' => 'fr',
			'gl' => 'gl',
			'gl_es' => 'gl',
			'hr' => 'hr_HR',
			'hr_hr' => 'hr_HR',
			'hu' => 'hu_HU',
			'hu_hu' => 'hu_HU',
			'id' => 'id_ID',
			'id_id' => 'id_ID',
			'is' => 'is',
			'is_is' => 'is',
			'it' => 'it_IT',
			'it_it' => 'it_IT',
			'lt' => 'lt_LT',
			'lt_lt' => 'lt_LT',
			'lv' => 'lv_LV',
			'lv_lv' => 'lv_LV',
			'nb' => 'nb_NO',
			'nb_no' => 'nb_NO',
			'nl' => 'nl_NL',
			'nl_be' => 'nl_NL',
			'nl_nl' => 'nl_NL',
			'nn' => 'nn_NO',
			'nn_no' => 'nn_NO',
			'pl' => 'pl_PL',
			'pl_pl' => 'pl_PL',
			'pt' => 'pt_PT',
			'pt_br' => 'pt_BR',
			'pt_pt' => 'pt_PT',
			'ro' => 'ro_RO',
			'ro_ro' => 'ro_RO',
			'ru' => 'ru_RU',
			'ru_ru' => 'ru_RU',
			'sh' => 'sh',
			'sk' => 'sk_SK',
			'sk_sk' => 'sk_SK',
			'sl' => 'sl_SI',
			'sl_si' => 'sl_SI',
			'sr' => 'sr',
			'sr_latn' => 'sr-Latn',
			'sr_rs' => 'sr',
			'sv' => 'sv',
			'sv_se' => 'sv',
			'te' => 'te_IN',
			'te_in' => 'te_IN',
			'uk' => 'uk_UA',
			'uk_ua' => 'uk_UA',
			'zu' => 'zu_ZA',
			'zu_za' => 'zu_ZA',
		);

		return isset( $map[ $locale ] ) ? $map[ $locale ] : null;
	}

	/**
	 * Get string length safely.
	 *
	 * @param string $value String.
	 * @return int
	 */
	private function string_length( $value ) {
		if ( function_exists( 'mb_strlen' ) ) {
			return mb_strlen( $value, 'UTF-8' );
		}

		return strlen( $value );
	}

	/**
	 * Lowercase text safely.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function lower( $value ) {
		if ( function_exists( 'mb_strtolower' ) ) {
			return mb_strtolower( $value, 'UTF-8' );
		}

		return strtolower( $value );
	}

	/**
	 * Extract a substring safely.
	 *
	 * @param string   $value Value.
	 * @param int      $start Start offset.
	 * @param int|null $length Length.
	 * @return string
	 */
	private function substring( $value, $start, $length = null ) {
		if ( function_exists( 'mb_substr' ) ) {
			return mb_substr( $value, $start, $length, 'UTF-8' );
		}

		return substr( $value, $start, $length );
	}

	/**
	 * Avoid words that are likely not prose.
	 *
	 * @param string $word Word.
	 * @return bool
	 */
	private function looks_like_machine_token( $word ) {
		return (bool) preg_match( '/\d|_|@|\.|\//', $word );
	}

	/**
	 * Preserve casing from the rendered word after a case-insensitive lookup.
	 *
	 * @param string $original Original word.
	 * @param string $replacement Replacement with soft hyphens.
	 * @return string
	 */
	private function match_original_casing( $original, $replacement ) {
		if ( $this->is_uppercase( $original ) ) {
			return $this->uppercase( $replacement );
		}

		$first_original = $this->substring( $original, 0, 1 );

		if ( $first_original === $this->lower( $first_original ) ) {
			return $replacement;
		}

		return $this->uppercase( $this->substring( $replacement, 0, 1 ) ) . $this->substring( $replacement, 1 );
	}

	/**
	 * Collapse duplicate soft hyphen opportunities from pattern engines or input.
	 *
	 * @param string $value Hyphenated value.
	 * @return string
	 */
	private function normalize_soft_hyphens( $value ) {
		$soft_hyphen = preg_quote( self::SOFT_HYPHEN, '/' );
		$normalized  = preg_replace( '/(?:' . $soft_hyphen . '){2,}/u', self::SOFT_HYPHEN, $value );

		return is_string( $normalized ) ? $normalized : $value;
	}

	/**
	 * Check whether a word is uppercase.
	 *
	 * @param string $word Word.
	 * @return bool
	 */
	private function is_uppercase( $word ) {
		return $word === $this->uppercase( $word ) && $word !== $this->lower( $word );
	}

	/**
	 * Uppercase text safely.
	 *
	 * @param string $value Value.
	 * @return string
	 */
	private function uppercase( $value ) {
		if ( function_exists( 'mb_strtoupper' ) ) {
			return mb_strtoupper( $value, 'UTF-8' );
		}

		return strtoupper( $value );
	}
}