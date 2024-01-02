<?php

use MediaWiki\Extension\Description2\DescriptionProvider;
use MediaWiki\MediaWikiServices;

return [
	'Description2.DescriptionProvider' => static function (
		MediaWikiServices $services
	): DescriptionProvider {
		// The provider implementation is determined by the $wgDescriptionAlgorithm variable.
		$class = $services->getMainConfig()->get( 'DescriptionAlgorithm' );
		return new $class( $services->getMainConfig() );
	},
];
