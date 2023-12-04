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

	/** @var Config */
	private Config $config;

	/** @var DescriptionProvider */
	private DescriptionProvider $descriptionProvider;

	/** @var int */
	private int $maxChars;

	/**
	 * @param ConfigFactory $configFactory
	 */
	public function __construct(
		ConfigFactory $configFactory,
		DescriptionProvider $descriptionProvider
	) {
		$this->config = $configFactory->makeConfig( 'Description2' );
		$this->descriptionProvider = $descriptionProvider;
		$this->maxChars = $this->config->get( 'DescriptionMaxChars' );
	}

	/**
	 * @link https://www.mediawiki.org/wiki/Manual:Hooks/ParserAfterTidy
	 * @param Parser $parser The parser.
	 * @param string &$text The page text.
	 * @return bool
	 */
	public function onParserAfterTidy( $parser, &$text ) {
		$parserOutput = $parser->getOutput();

		// Avoid running the algorithm on interface messages which may waste time
		if ( $parser->getOptions()->getInterfaceMessage() ) {
			return true;
		}

		// Avoid running the algorithm multiple times if we already have determined the description. This may happen
		// on file pages.
		if ( method_exists( $parserOutput, 'getPageProperty' ) ) {
			// MW 1.38+
			$description = $parserOutput->getPageProperty( 'description' );
		} else {
			$description = $parserOutput->getProperty( 'description' );
		}
		if ( $description ) {
			return true;
		}

		$desc = $this->descriptionProvider->derive( $text );
		if ( !$desc ) {
			return true;
		}

		if ( $this->maxChars > 0 ) {
			$truncated = Description2::getFirstChars( $desc, $this->maxChars );
			if ( $truncated !== $desc ) {
				$desc = $truncated;
				if ( !preg_match( '/\p{P}$/u', $truncated ) ) {
					$desc = $truncated . wfMessage( 'ellipsis' )->text();
				}
			}
		}

		Description2::setDescription( $parser, $desc );
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
