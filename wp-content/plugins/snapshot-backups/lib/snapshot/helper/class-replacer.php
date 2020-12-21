<?php // phpcs:ignore
/**
 * Snapshot helpers: replacer abstraction
 *
 * All replacers will inherit from this.
 *
 * @package snapshot
 */

namespace WPMUDEV\Snapshot4\Helper;

use WPMUDEV\Snapshot4\Helper\Codec;

/**
 * Replacer helper class
 */
abstract class Replacer {

	/**
	 * Transforms the contents
	 *
	 * @param string $source Source to transform.
	 *
	 * @return string Transformed source
	 */
	abstract public function transform( $source );

	/**
	 * Replacement direction - import or export
	 *
	 * @var string
	 */
	private $_direction;

	/**
	 * Holds a list of codecs to apply
	 *
	 * @var array
	 */
	private $_codec_list = array();

	/**
	 * Constructor
	 *
	 * @param string $direction Replacement direction - export (encode) or import (decode).
	 */
	public function __construct( $direction ) {
		$this->_direction = $direction;
	}

	/**
	 * Gets a list of codecs to apply on input string
	 *
	 * If no codecs have been explicitly set, returns default codec list.
	 *
	 * @return array
	 */
	public function get_codec_list() {
		if ( empty( $this->_codec_list ) ) {
			$this->_codec_list = array(
				new Codec\Sql(),
			);
		}
		return $this->_codec_list;
	}

	/**
	 * Sets codec list to be used in replacement
	 *
	 * @param array $list List of codecs.
	 *
	 * @return Helper\Replacer
	 */
	public function set_codec_list( $list = array() ) {
		$this->_codec_list = (array) $list;
		return $this;
	}

	/**
	 * Adds a codec to list to be used in replacement
	 *
	 * @param object $codec Helper\Codec instance.
	 *
	 * @return Helper\Replacer
	 */
	public function add_codec( Codec $codec ) {
		$list   = $this->get_codec_list();
		$list[] = $codec;
		return $this->set_codec_list( $list );
	}

	/**
	 * Returns whether we're encoding or decoding
	 *
	 * @return string
	 */
	public function get_direction() {
		return $this->_direction;
	}

}