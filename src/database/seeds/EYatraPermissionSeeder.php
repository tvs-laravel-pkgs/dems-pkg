<?php
namespace Uitoux\EYatra\Database\Seeds;
use App\Permission;
use Illuminate\Database\Seeder;

class EYatraPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [

			//ADMIN MAIN MENUS
			5000 => [
				'display_order' => 100,
				'parent_id' => NULL,
				'name' => 'eyatra',
				'display_name' => 'eYatra',
			],

			//MASTERS
			5080 => [
				'display_order' => 1,
				'parent_id' => 5000,
				'name' => 'eyatra-masters',
				'display_name' => 'Masters',
			],

			//TRIPS
			5001 => [
				'display_order' => 1,
				'parent_id' => 5000,
				'name' => 'trips',
				'display_name' => 'Trips',
			],
			5002 => [
				'display_order' => 1,
				'parent_id' => 5001,
				'name' => 'trip-add',
				'display_name' => 'Add',
			],
			5003 => [
				'display_order' => 2,
				'parent_id' => 5001,
				'name' => 'trip-edit',
				'display_name' => 'Edit',
			],
			5004 => [
				'display_order' => 3,
				'parent_id' => 5001,
				'name' => 'trip-delete',
				'display_name' => 'Delete',
			],
			5005 => [
				'display_order' => 4,
				'parent_id' => 5001,
				'name' => 'view-all-trips',
				'display_name' => 'View All',
			],

			//OUTLETS
			5020 => [
				'display_order' => 1,
				'parent_id' => 5080,
				'name' => 'eyatra-outlets',
				'display_name' => 'Outlets',
			],
			5021 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-outlet-add',
				'display_name' => 'Add',
			],
			5022 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-outlet-edit',
				'display_name' => 'Edit',
			],
			5023 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-outlet-delete',
				'display_name' => 'Delete',
			],

			//EMPLOYEES
			5040 => [
				'display_order' => 1,
				'parent_id' => 5080,
				'name' => 'eyatra-employees',
				'display_name' => 'Employees',
			],
			5041 => [
				'display_order' => 1,
				'parent_id' => 5040,
				'name' => 'eyatra-employee-add',
				'display_name' => 'Add',
			],
			5042 => [
				'display_order' => 1,
				'parent_id' => 5040,
				'name' => 'eyatra-employee-edit',
				'display_name' => 'Edit',
			],
			5043 => [
				'display_order' => 1,
				'parent_id' => 5040,
				'name' => 'eyatra-employee-delete',
				'display_name' => 'Delete',
			],

			//TRIPS VERIFICATION
			5060 => [
				'display_order' => 1,
				'parent_id' => 5000,
				'name' => 'trips-verification',
				'display_name' => 'Trips Verification',
			],
			5061 => [
				'display_order' => 1,
				'parent_id' => 5001,
				'name' => 'trip-verification-all',
				'display_name' => 'All',
			],

			//MASTERS > AGENTS
			5100 => [
				'display_order' => 1,
				'parent_id' => 5080,
				'name' => 'eyatra-agents',
				'display_name' => 'Agents',
			],
			5101 => [
				'display_order' => 1,
				'parent_id' => 5100,
				'name' => 'eyatra-agent-add',
				'display_name' => 'Add',
			],
			5102 => [
				'display_order' => 2,
				'parent_id' => 5100,
				'name' => 'eyatra-agent-edit',
				'display_name' => 'Edit',
			],
			5103 => [
				'display_order' => 3,
				'parent_id' => 5100,
				'name' => 'eyatra-agent-delete',
				'display_name' => 'Delete',
			],

			//MASTERS > GRADES
			5120 => [
				'display_order' => 1,
				'parent_id' => 5080,
				'name' => 'eyatra-grades',
				'display_name' => 'Grades',
			],
			5121 => [
				'display_order' => 1,
				'parent_id' => 5120,
				'name' => 'eyatra-grade-add',
				'display_name' => 'Add',
			],
			5122 => [
				'display_order' => 2,
				'parent_id' => 5120,
				'name' => 'eyatra-grade-edit',
				'display_name' => 'Edit',
			],
			5123 => [
				'display_order' => 3,
				'parent_id' => 5120,
				'name' => 'eyatra-grade-delete',
				'display_name' => 'Delete',
			],

			//MASTERS > STATES
			5140 => [
				'display_order' => 1,
				'parent_id' => 5080,
				'name' => 'eyatra-states',
				'display_name' => 'States',
			],
			5141 => [
				'display_order' => 1,
				'parent_id' => 5140,
				'name' => 'eyatra-state-add',
				'display_name' => 'Add',
			],
			5142 => [
				'display_order' => 2,
				'parent_id' => 5140,
				'name' => 'eyatra-state-edit',
				'display_name' => 'Edit',
			],
			5143 => [
				'display_order' => 3,
				'parent_id' => 5140,
				'name' => 'eyatra-state-delete',
				'display_name' => 'Delete',
			],
		];

		foreach ($permissions as $permission_id => $permsion) {
			$permission = Permission::firstOrNew([
				'id' => $permission_id,
			]);
			$permission->fill($permsion);
			$permission->save();
		}
	}
}