<?php
/**
 * Description2.php -- Adds meaningful description <meta> tag to MW pages and into the parser output
 * Copyright 2010 Daniel Friesen
 *
 * @file
 * @ingroup Extensions
 * @author Daniel Friesen (http://mediawiki.org/wiki/User:Dantman) <mediawiki@danielfriesen.name>
 * @license http://www.gnu.org/copyleft/gpl.html GNU General Public License 2.0 or later
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
 */

if ( !defined( 'MEDIAWIKI' ) ) die( "This is an extension to the MediaWiki package and cannot be run standalone." );

$wgExtensionCredits['other'][] = array(
	'path' => __FILE__,
	'name' => 'Description2',
	'version' => "0.1",
	'author' => 'Daniel Friesen',
	'url' => 'http://www.mediawiki.org/wiki/Extension:Description2',
	'description' => 'Adds a description meta-tag to MW pages and into the ParserOutput for other extensions to use',
);

$wgHooks['ParserAfterTidy'][] = 'egDescription2ParserAfterTidy';
function egDescription2ParserAfterTidy( &$parser, &$text ) {
	global $wgContLang;
	$desc = '';
	
	$myText = preg_replace('%<table\b[^>]*+>(?:(?R)|[^<]*+(?:(?!</?table\b)<[^<]*+)*+)*+</table>%i', '', $text);
	
	$paragraphs = array();
	if ( preg_match_all('#<p>.*?</p>#is', $myText, $paragraphs) ) {
		foreach ( $paragraphs[0] as $paragraph ) {
			$paragraph = trim(strip_tags($paragraph));
			if ( !$paragraph )
				continue;
			$desc = $paragraph;
			break;
		}
	}
	
	if ( $desc ) {
		$pOut = $parser->getOutput();
		$pOut->setProperty("description", $desc);
		$pOut->addOutputHook("setdescription");
	}
	
	return true;
}

$wgParserOutputHooks['setdescription'] = 'egDescription2ParserOutputSetDescription';
function egDescription2ParserOutputSetDescription( $out, $parserOutput, $data ) {
	// Export the description from the main parser output into the OutputPage
	$out->mDescription = $parserOutput->getProperty("description");
}

$wgHooks['BeforePageDisplay'][] = 'egDescription2PageHook';
function egDescription2PageHook( &$out, &$sk ) {
	if ( isset($out->mDescription) && $out->mDescription )
		$out->addMeta("description", $out->mDescription);
	return true;
}

