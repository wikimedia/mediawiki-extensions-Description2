<?php

use MediaWiki\Extension\Description2\DescriptionProvider;
use MediaWiki\Extension\Description2\SimpleDescriptionProvider;
use MediaWiki\MediaWikiServices;

return [
	'Description2.DescriptionProvider' => static function (
		MediaWikiServices $services
	): DescriptionProvider {
		return new SimpleDescriptionProvider();
	},
];
