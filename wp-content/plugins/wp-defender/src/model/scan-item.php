<?php

namespace WP_Defender\Model;

use WP_Defender\DB;

class Scan_Item extends DB {
	const TYPE_INTEGRITY = 'core_integrity', TYPE_VULNERABILITY = 'vulnerability', TYPE_SUSPICIOUS = 'malware';
	const STATUS_ACTIVE = 'active', STATUS_IGNORE = 'ignore';

	protected $table = 'defender_scan_item';
	/**
	 * @var int
	 * @defender_property
	 */
	public $id;
	/**
	 * @var int
	 * @defender_property
	 */
	public $parent_id;
	/**
	 * Type of the issue, base on this we will load the behavior
	 *
	 * @var string
	 * @defender_property
	 */
	public $type;
	/**
	 * Contain generic data
	 * @var array
	 * @defender_property
	 */
	public $raw_data = [];

	/**
	 * @var string
	 * @defender_property
	 */
	public $status;
}