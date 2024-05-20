<?php

namespace MediaWiki\Extension\Description2;

use Parser;
use PPFrame;

/**
 * Description2 – Adds meaningful description <meta> tag to MW pages and into the parser output
 *
 * @file
 * @ingroup Extensions
 * @author Daniel Friesen (http://danf.ca/mw/)
 * @copyright Copyright 2010 – Daniel Friesen
 * @license GPL-2.0-or-later
 * @link https://www.mediawiki.org/wiki/Extension:Description2 Documentation
 */

class Description2 {
	/**
	 * @param Parser $parser The parser.
	 * @param string $desc The description text.
	 */
	public static function setDescription( Parser $parser, $desc ) {
		$parserOutput = $parser->getOutput();
		if ( $parserOutput->getPageProperty( 'description' ) !== null ) {
			return;
		}
		$parserOutput->setPageProperty( 'description', $desc );
	}

	/**
	 * @param Parser $parser The parser.
	 * @param PPFrame $frame The frame.
	 * @param string[] $args The arguments of the parser function call.
	 * @return string
	 */
	public static function parserFunctionCallback( Parser $parser, PPFrame $frame, $args ) {
		$desc = isset( $args[0] ) ? $frame->expand( $args[0] ) : '';
		self::setDescription( $parser, $desc );
		return '';
	}

	/**
	 * Returns no more than a requested number of characters, preserving words.
	 *
	 * Borrowed from TextExtracts.
	 *
	 * @param string $text Source plain text to extract from. HTML tags should be removed by the description provider.
	 * @param int $requestedLength Maximum number of characters to return
	 * @return string
	 */
	public static function getFirstChars( string $text, int $requestedLength ) {
		if ( $requestedLength <= 0 ) {
			return '';
		}

		$length = mb_strlen( $text );
		if ( $length <= $requestedLength ) {
			return $text;
		}

		// The following (although in somewhat backwards order) cuts the text at given length and restores the end if it
		// has been cut, with the ungreedy pattern always matching a single word built of word characters (no
		// punctuation) and/or forward slashes.
		$pattern = '/^[\w\/]*/su';
		preg_match( $pattern, mb_substr( $text, $requestedLength ), $m );
		$truncatedText = mb_substr( $text, 0, $requestedLength ) . $m[0];
		if ( $truncatedText === $text ) {
			return $text;
		}

		return trim( $truncatedText );
	}
}
