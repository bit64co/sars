<?php declare(strict_types=1);
/*
 * This file is part of the Bit 64 SARS component
 *
 * Copyright (c) 2021-current Bit 64 Solutions Pty Ltd (https://bit64.co)
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Bit64\Sars\Scope;

use Bit64\Sars\Api;
use Bit64\Sars\Exception\DataRangeException;
use DateTime;

/**
 * Income tax calculator
 *
 * @author Warren Heyneke <warren@bit64.co>
 *
 */
class IncomeTax {

	/**
	 * @var array Income tax table data
	 */
	private $data;

	/**
	 * Constructor
	 */
	public function __construct(array $data) {
		$this->data = $data;
	}

	/**
	 * Calculates daily tax deduction
	 *
	 * @param float $dailyTaxableIncome - Total daily taxable income
	 * @param int $age - Tax payer’s age
	 * @param mixed $context - Date context
	 * @return float Total daily tax deduction
	 * @throws DataRangeException If there is no data to support the context
	 */
	public function calculateDailyTax(float $dailyTaxableIncome, $age = 30, $context = null): float {
		return $this->calculateAnnualTax($dailyTaxableIncome * 365, $age, $context) / 365;
	}

	/**
	 * Calculates weekly tax deduction
	 *
	 * @param float $weeklyTaxableIncome - Total weekly taxable income
	 * @param int $age - Tax payer’s age
	 * @param mixed $context - Date context
	 * @return float Total weekly tax deduction
	 * @throws DataRangeException If there is no data to support the context
	 */
	public function calculateWeeklyTax(float $weeklyTaxableIncome, $age = 30, $context = null): float {
		return $this->calculateAnnualTax($weeklyTaxableIncome * 52, $age, $context) / 52;
	}

	/**
	 * Calculates monthly tax deduction
	 *
	 * @param float $monthlyTaxableIncome - Total monthly taxable income
	 * @param int $age - Tax payer’s age
	 * @param mixed $context - Date context
	 * @return float Total monthly tax deduction
	 * @throws DataRangeException If there is no data to support the context
	 */
	public function calculateMonthlyTax(float $monthlyTaxableIncome, $age = 30, $context = null): float {
		return $this->calculateAnnualTax($monthlyTaxableIncome * 12, $age, $context) / 12;
	}

	/**
	 * Calculates annual tax deduction
	 *
	 * @param float $annualTaxableIncome - Total annual taxable income
	 * @param int $age - Tax payer’s age
	 * @param mixed $context - Date context
	 * @return float Total annual tax deduction
	 * @throws DataRangeException If there is no data to support the context
	 */
	public function calculateAnnualTax(float $annualTaxableIncome, int $age = 30, $context = null): float {

		if (null === $context) {
			$context = new DateTime();
		} elseif (!$context instanceof DateTime) {
			$context = new DateTime($context);
		}

		$data = $this->extractDataMatrix($context);

		// Below applicable threshold, therefore tax exempt
		if ($this->extractAnnualTaxThreshold($data, $age) >= $annualTaxableIncome) {
			return 0;
		}

		$annualTaxDeduction = 0;

		// Calculate applicable tax deduction from table
		$above = 0;
		foreach ($data['table'] as $table) {
			if (
				(null === $table['low'] || $table['low'] <= $annualTaxableIncome) &&
				(null === $table['high'] || $table['high'] >= $annualTaxableIncome)
			) {
				$annualTaxDeduction = $table['fixed'] + ( ($annualTaxableIncome - $above) * ($table['percent'] / 100));
				break;
			}
			$above = $table['high'];
		}

		$annualTaxDeduction -= $this->extractAnnualTaxRebate($data, $age);

		return (float) $annualTaxDeduction;
	}

	/**
	 * Extracts applicable data matrix from date context
	 *
	 * @param DateTime $context - Date context
	 * @return array Income tax data matrix
	 * @throws DataRangeException If there is no data to support the context
	 */
	private function extractDataMatrix(DateTime $context): array {

		$ix = Api::getDataIndex($context);

		if (!array_key_exists($ix, $this->data)) {
			throw new DataRangeException(sprintf('No data found for index \'%s\'', $ix));
		}

		$data = $this->data[$ix];

		// Sort thresholds by min age
		$ages = array_column($data['threshold'], 'age-min');
		array_multisort($ages, SORT_ASC, $data['threshold']);

		// Sort rebates by min age
		$ages = array_column($data['rebate'], 'age-min');
		array_multisort($ages, SORT_ASC, $data['rebate']);

		return $data;
	}

	/**
	 * Extract annual tax rebate
	 *
	 * @param array $data - Data matrix
	 * @param int $age - Tax payer’s age
	 * @return float Annual tax rebate
	 */
	private function extractAnnualTaxRebate(array $data, int $age): ?float {

		$rebate = 0;

		foreach ($data['rebate'] as $r) {
			if ($r['age-min'] <= $age) {
				$rebate += $r['rebate'];
			}
		}

		return (float) $rebate;
	}

	/**
	 * Extract annual tax threshold
	 *
	 * @param array $data - Data matrix
	 * @param int $age - Tax payer’s age
	 * @return float Annual tax threshold
	 */
	private function extractAnnualTaxThreshold(array $data, int $age): ?float {

		$threshold = 0;

		foreach ($data['threshold'] as $t) {
			if ($t['age-min'] <= $age) {
				$threshold = (float) $t['threshold'];
			}
		}

		return $threshold;
	}

}
