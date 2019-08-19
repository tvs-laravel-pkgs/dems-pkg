<?php

namespace Uitoux\EYatra\Database\Seeds;

use Illuminate\Database\Seeder;

class EYatraSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$this->call(EYatraConfigSeeder::class);
		$this->call(EYatraETSeeder::class);
		$this->call(EYatraPermissionSeeder::class);
		$this->call(EYatraMPermissionSeeder::class);
		$this->call(EYatraRoleSeeder::class);
	}
}
