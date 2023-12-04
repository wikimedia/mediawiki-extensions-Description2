<?php

namespace MediaWiki\Extension\Description2;

class SimpleDescriptionProvider implements DescriptionProvider {

	/**
	 * Extracts description from the HTML representation of a page.
	 *
	 * The algorithm:
	 * 1. Removes all <table> elements and their contents.
	 * 2. Selects all <p> elements.
	 * 3. Iterates over those paragraphs, strips out all HTML tags and trims white-space around.
	 * 4. Then the first non-empty paragraph is picked as the result.
	 *
	 * @param string $text
	 * @return string
	 */
	public function derive( string $text ): ?string {
		$pattern = '%<table\b[^>]*+>(?:(?R)|[^<]*+(?:(?!</?table\b)<[^<]*+)*+)*+</table>%i';
		$myText = preg_replace( $pattern, '', $text );

		$paragraphs = [];
		if ( preg_match_all( '#<p>.*?</p>#is', $myText, $paragraphs ) ) {
			foreach ( $paragraphs[0] as $paragraph ) {
				$paragraph = trim( strip_tags( $paragraph ) );
				if ( !$paragraph ) {
					continue;
				}
				return $paragraph;
			}
		}

		return null;
	}
}
