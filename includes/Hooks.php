<?php

namespace MediaWiki\Extension\Description2;

use Config;
use ConfigFactory;
use OutputPage;
use Parser;
use ParserOutput;

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

class Hooks implements
	\MediaWiki\Hook\ParserAfterTidyHook,
	\MediaWiki\Hook\ParserFirstCallInitHook,
	\MediaWiki\Hook\OutputPageParserOutputHook
{

	private Config $config;

	/**
	 * @param ConfigFactory $configFactory
	 */
	public function __construct(
		ConfigFactory $configFactory
	) {
		$this->config = $configFactory->makeConfig( 'Description2' );
	}

	/**
	 * @link https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
	 * @param Parser $parser The parser.
	 * @param string &$text The page text.
	 * @return bool
	 */
	public function onParserAfterTidy( $parser, &$text ) {
		$desc = '';

		$pattern = '%<table\b[^>]*+>(?:(?R)|[^<]*+(?:(?!</?table\b)<[^<]*+)*+)*+</table>%i';
		$myText = preg_replace( $pattern, '', $text );

		$paragraphs = [];
		if ( preg_match_all( '#<p>.*?</p>#is', $myText, $paragraphs ) ) {
			foreach ( $paragraphs[0] as $paragraph ) {
				$paragraph = trim( strip_tags( $paragraph ) );
				if ( !$paragraph ) {
					continue;
				}
				$desc = $paragraph;
				break;
			}
		}

		if ( $desc ) {
			Description2::setDescription( $parser, $desc );
		}

		return true;
	}

	/**
	 * @param Parser $parser The parser.
	 * @return bool
	 */
	public function onParserFirstCallInit( $parser ) {
		if ( !$this->config->get( 'EnableMetaDescriptionFunctions' ) ) {
			// Functions and tags are disabled
			return true;
		}
		$parser->setFunctionHook(
			'description2',
			[ Description2::class, 'parserFunctionCallback' ],
			Parser::SFH_OBJECT_ARGS
		);
		return true;
	}

	/**
	 * @param OutputPage $out The output page to add the meta element to.
	 * @param ParserOutput $parserOutput The parser output to get the description from.
	 */
	public function onOutputPageParserOutput( $out, $parserOutput ): void {
		// Export the description from the main parser output into the OutputPage
		if ( method_exists( $parserOutput, 'getPageProperty' ) ) {
			// MW 1.38+
			$description = $parserOutput->getPageProperty( 'description' );
		} else {
			$description = $parserOutput->getProperty( 'description' );
			if ( $description === false ) {
				$description = null;
			}
		}
		if ( $description !== null ) {
			$out->addMeta( 'description', $description );
		}
	}
}
