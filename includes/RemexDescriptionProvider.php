<?php

namespace MediaWiki\Extension\Description2;

use Config;
use Wikimedia\RemexHtml\HTMLData;
use Wikimedia\RemexHtml\Serializer\HtmlFormatter;
use Wikimedia\RemexHtml\Serializer\Serializer;
use Wikimedia\RemexHtml\Serializer\SerializerNode;
use Wikimedia\RemexHtml\Tokenizer\Tokenizer;
use Wikimedia\RemexHtml\TreeBuilder\Dispatcher;
use Wikimedia\RemexHtml\TreeBuilder\TreeBuilder;

class RemexDescriptionProvider implements DescriptionProvider {
	/** @var string[] */
	private array $toRemove;

	/**
	 * @param Config $config
	 */
	public function __construct( Config $config ) {
		$this->toRemove = $config->get( 'DescriptionRemoveElements' );
	}

	/**
	 * Extracts description from the HTML representation of a page.
	 *
	 * This algorithm:
	 * 1. Looks for the first <hN> heading (potentially included in the ToC) and cuts the text to avoid unnecessary
	 *    server load.
	 * 2. Invokes RemexHTML to parse and reserialise the HTML representation.
	 * 3. Comments are excluded.
	 * 4. HTML elements are filtered by tag name and the 'class' attribute. Removals are dictated through the
	 *    $wgDescriptionRemoveElements config variable.
	 * 5. HTML tags are stripped, and only text is preserved.
	 * 6. Strips white-space around the extract.
	 *
	 * This is more costly than the SimpleDescriptionProvider but is far more flexible and easier to manipulate by
	 * editors.
	 *
	 * @param string $text
	 * @return string
	 */
	public function derive( string $text ): ?string {
		$formatter = new class( $options = [], $this->toRemove ) extends HtmlFormatter {

			/** @var string[] */
			private array $toRemove;

			/**
			 * @param array $options
			 * @param array $toRemove
			 */
			public function __construct( $options, array $toRemove ) {
				parent::__construct( $options );
				$this->toRemove = $toRemove;
			}

			/**
			 * Skips comments.
			 *
			 * @param SerializerNode $parent
			 * @param string $text
			 * @return void
			 */
			public function comment( SerializerNode $parent, $text ) {
				return '';
			}

			/**
			 * Strips out HTML tags leaving bare text, and strips out undesirable elements per configuration.
			 *
			 * @param SerializerNode $parent
			 * @param SerializerNode $node
			 * @param string $contents
			 * @return void
			 */
			public function element( SerializerNode $parent, SerializerNode $node, $contents ) {
				// Read CSS classes off the node into an array for later
				$nodeClasses = $node->attrs->getValues()['class'] ?? null;
				if ( $nodeClasses ) {
					$nodeClasses = explode( ' ', $nodeClasses );
				}

				// Strip away elements matching our removal list. This only supports tags and classes.
				foreach ( $this->toRemove as $selectorish ) {
					$split = explode( '.', $selectorish );
					$tagName = array_shift( $split );

					if ( $tagName !== '' && $node->name !== $tagName ) {
						continue;
					}

					if ( $split && ( !$nodeClasses || array_diff( $split, $nodeClasses ) ) ) {
						continue;
					}

					return '';
				}

				return $contents;
			}

			/**
			 * Skips document starter tags.
			 *
			 * @param string $fragmentNamespace
			 * @param string $fragmentName
			 * @return void
			 */
			public function startDocument( $fragmentNamespace, $fragmentName ) {
				return '';
			}
		};

		// Preserve only the first section
		if ( preg_match( '/^.*?(?=<h[1-6]\b(?! id="mw-toc-heading"))/s', $text, $matches ) ) {
			$text = $matches[0];
		}

		$serializer = new Serializer( $formatter );
		$treeBuilder = new TreeBuilder( $serializer );
		$dispatcher = new Dispatcher( $treeBuilder );
		$tokenizer = new Tokenizer( $dispatcher, $text );

		$tokenizer->execute( [
			'fragmentNamespace' => HTMLData::NS_HTML,
			'fragmentName' => 'body',
		] );

		return trim( $serializer->getResult() );
	}
}
