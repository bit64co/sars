<?php declare(strict_types=1);
/*
 * This file is part of the Bit 64 SARS component
 *
 * Copyright (c) 2021-current Bit 64 Solutions Pty Ltd (https://bit64.co)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bit64\Sars;

use Bit64\Common\FileSystem\JsonFile;
use Bit64\Sars\Scope\IncomeTax;

/**
 * SARS API
 *
 * @author Warren Heyneke <warren@bit64.co>
 *
 */
class Api {

	/**
	 * @var array Data matrix
	 */
	private $data;

	public function __construct() {

		foreach (glob(sprintf('%s/../data/*.json', __DIR__)) as $jsonFile) {
			if (preg_match('/(?<year>[0-9]{4})\.json$/', $jsonFile, $matched)) {
				$this->data[(string) $matched['year']] = (new JsonFile($jsonFile))->readData();
			}
		}

	}

	/**
	 * Returns the Income Tax calculator
	 */
	public function IncomeTax(): IncomeTax {
		return new IncomeTax($this->extractDataPack('income-tax'));
	}

	/**
	 * Returns the data index based on context date
	 *
	 * @param mixed $context - Date context
	 * @return string Data index
	 */
	public static function getDataIndex(\DateTime $context): string {

		$changeOver = new \DateTime(sprintf('%s-03-01', $context->format('Y')));
		if ($changeOver->format('U') > time()) {
			$changeOver->modify('-1 year');
		}
		return (string) ($changeOver->format('Y') + 1);

	}

	/**
	 * Extracts a specific pack from the data matrix
	 */
	private function extractDataPack(string $node): array {
		return array_combine(
			array_keys($this->data),
			array_column($this->data, $node)
		);
	}

}
