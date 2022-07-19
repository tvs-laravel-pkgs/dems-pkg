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
				'travel_type_id' => 13,
				'amount' => 125.00,
			],
			2 => [
				'name' => 'AC GENERAL (3RD AC & 2ND AC)',
				'travel_type_id' => 13,
				'amount' => 75.00,
			],
			3 => [
				'name' => 'EC',
				'travel_type_id' => 13,
				'amount' => 100.00,
			],
			4 => [
				'name' => '1ST AC',
				'travel_type_id' => 13,
				'amount' => 100.00,
			],
			5 => [
				'name' => 'GENERAL',
				'travel_type_id' => 13,
				'amount' => 100.00,
			],
			6 => [
				'name' => 'TAKAL',
				'travel_type_id' => 13,
				'amount' => 125.00,
			],
			7 => [
				'name' => 'SLEEPER CLASS NON-AC - GENERAL',
				'travel_type_id' => 13,
				'amount' => 30.00,
			],
			8 => [
				'name' => 'SLEEPER CLAS NON-AC – TAKAL',
				'travel_type_id' => 13,
				'amount' => 60.00,
			],
			9 => [
				'name' => 'AC CHAIR CAR – GENERAL',
				'travel_type_id' => 13,
				'amount' => 50.00,
			],
			10 => [
				'name' => 'AC CHAIR CAR -TAKAL',
				'travel_type_id' => 13,
				'amount' => 100.00,
			],
			11 => [
				'name' => 'TAKAL & GENERAL - NON-AC SEATING',
				'travel_type_id' => 13,
				'amount' => 20.00,
			],
			12 => [
				'name' => 'BUS AC',
				'travel_type_id' => 12,
				'amount' => 40.00,
			],
			13 => [
				'name' => 'BUS NON-AC',
				'travel_type_id' => 12,
				'amount' => 30.00,
			],
			14 => [
				'name' => 'FLIGHT',
				'travel_type_id' => 14,
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
