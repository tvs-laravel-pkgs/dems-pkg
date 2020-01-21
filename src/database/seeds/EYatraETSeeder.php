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
			503 => 'Other Expense',
			504 => 'Lodging Stay',
			505 => 'Employee Wallet Mode',
			506 => 'City Category',
			507 => 'Trip Request',
			508 => 'Trip Advance Request',
			509 => 'Trip Claim Request',
			510 => 'Agent Claim Request',
			511 => 'Voucher Claim Request',
			512 => 'Petty Cash Expense types',
			513 => 'COA Account Types',
			514 => 'COA Balance Types',
			515 => 'COA Final Statement',
			516 => 'COA Groups',
			517 => 'COA Sub Groups',
			518 => 'Railway Ticket Types',

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
