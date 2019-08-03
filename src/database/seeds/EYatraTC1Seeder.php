<?php

namespace Uitoux\EYatra\Database\Seeds;

use Illuminate\Database\Seeder;

class EYatraTC1Seeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$delete_company = $this->command->ask("Do you want to delete company", 'n');
		echo $delete_company;
	}
}
