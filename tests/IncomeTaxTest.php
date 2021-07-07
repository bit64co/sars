<?php declare(strict_types=1);
/*
 * This file is part of the Bit 64 SARS component
 *
 * Copyright (c) 2021-current Bit 64 Solutions Pty Ltd (https://bit64.co)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Bit64\Common\FileSystem\File;
use Bit64\Common\FileSystem\JsonFile;
use Bit64\Sars\Api;
use PHPUnit\Framework\TestCase;

/**
 * Income tax test
 *
 * @author Warren Heyneke <warren@bit64.co>
 *
 */
final class IncomeTaxTest extends TestCase {

	private $calculator;

	protected function setUp(): void {
		if (!$this->calculator) {
			$this->calculator = (new Api())->IncomeTax();
		}
	}

	/**
	 * Checks that the last 2-years worth of data is available
	 */
	public function testDataAvailable(): void {

		$fromYear = date('Y') - 1;
		$toYear = date('Y');

		try {
			for ($i = $fromYear; $i <= $toYear; $i++) {
				$this->calculator->calculateAnnualTax(1, 25, sprintf('%u-01-01', $i));
			}
			$this->assertTrue(true);
		}
		catch (\Exception $e) {
			$this->fail(sprintf("Failed asserting data is available: %s", $e->getMessage()));
		}

	}

	/**
	 * Verify calculations are correct
	 */
	public function testCalculations(): void {

		$testData = (new JsonFile(sprintf('%s/IncomeTaxTestData.json', __DIR__)))->readData();

		foreach ($testData as $test) {

			$method = sprintf('calculate%sTax', ucfirst($test['frequency']));
			$result = round($this->calculator->$method($test['income'], $test['age'], $test['context']), 2);
			$this->assertEquals($result, $test['expected']);

		}

	}

}
