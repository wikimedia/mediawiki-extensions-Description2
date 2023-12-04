<?php

namespace MediaWiki\Extension\Description2;

interface DescriptionProvider {
	/**
	 * Extracts description from the HTML representation of a page.
	 *
	 * @param string $text HTML to extract the description from.
	 * @return ?string Plain text, or null if no description could have been generated
	 */
	public function derive( string $text ): ?string;
}
