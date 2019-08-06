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
