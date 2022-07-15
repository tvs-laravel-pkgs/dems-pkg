<?php

namespace Uitoux\EYatra\Database\Seeds;

use Illuminate\Database\Seeder;
use Uitoux\EYatra\BookingMethod;

class BookingMethodSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$bookingMethods = [
			1 => [
				'name' => 'AC TAKAL',
				'amount' => 125.00,
			],
			2 => [
				'name' => 'AC GENERAL (3RD AC & 2ND AC)',
				'amount' => 75.00,
			],
			3 => [
				'name' => 'EC',
				'amount' => 100.00,
			],
			4 => [
				'name' => '1ST AC',
				'amount' => 100.00,
			],
			5 => [
				'name' => 'GENERAL',
				'amount' => 100.00,
			],
			6 => [
				'name' => 'TAKAL',
				'amount' => 125.00,
			],
			7 => [
				'name' => 'SLEEPER CLASS NON-AC - GENERAL',
				'amount' => 30.00,
			],
			8 => [
				'name' => 'SLEEPER CLAS NON-AC – TAKAL',
				'amount' => 60.00,
			],
			9 => [
				'name' => 'AC CHAIR CAR – GENERAL',
				'amount' => 50.00,
			],
			10 => [
				'name' => 'AC CHAIR CAR -TAKAL',
				'amount' => 100.00,
			],
			11 => [
				'name' => 'TAKAL & GENERAL - NON-AC SEATING',
				'amount' => 20.00,
			],
			12 => [
				'name' => 'BUS AC',
				'amount' => 40.00,
			],
			13 => [
				'name' => 'BUS NON-AC',
				'amount' => 30.00,
			],
			14 => [
				'name' => 'FLIGHT',
				'amount' => 100.00,
			],
		];

		foreach ($bookingMethods as $id => $val) {
			$bookingMethod = BookingMethod::withTrashed()->firstOrNew([
				'id' => $id,
			]);
			$bookingMethod->fill($val);
			$bookingMethod->save();
		}
	}
}
