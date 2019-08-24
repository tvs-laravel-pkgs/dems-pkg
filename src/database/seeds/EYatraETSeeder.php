<?php

namespace Uitoux\EYatra\Database\Seeds;

use App\EntityType;
use Illuminate\Database\Seeder;

class EYatraETSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$entity_types = [
			500 => 'Employee Grades',
			501 => 'Trip Purposes',
			502 => 'Travel Modes',
			503 => 'Local Travel Modes',
			504 => 'Lodging Stay',
			505 => 'Employee Wallet Mode',
			506 => 'City Category',
			507 => 'Trip Request Rejection Reasons',
			508 => 'Trip Advance Rejection Reasons',
			509 => 'Trip Claim Rejection Reasons',
			510 => 'Agent Claim Rejection Reasons',
			511 => 'Voucher Claim Rejection Reasons',
			512 => 'Petty Cash Expense types',
			513 => 'COA Account Types',
			514 => 'COA Balance Types',
			515 => 'COA Final Statement',
			516 => 'COA Groups',
			517 => 'COA Sub Groups',

		];

		foreach ($entity_types as $entity_type_id => $entity_type_name) {
			$entity_type = EntityType::firstOrNew([
				'id' => $entity_type_id,
			]);
			$entity_type->name = $entity_type_name;
			$entity_type->save();
		}
	}
}
