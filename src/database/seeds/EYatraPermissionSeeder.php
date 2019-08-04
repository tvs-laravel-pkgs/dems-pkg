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
				'parent_id' => 5000,
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
				'parent_id' => 5000,
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