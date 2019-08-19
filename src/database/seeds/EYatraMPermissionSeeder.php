<?php
namespace Uitoux\EYatra\Database\Seeds;
use App\Permission;
use Illuminate\Database\Seeder;

class EYatraMPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [

			//TOP MENU
			9000 => [
				'display_order' => 1,
				'parent_id' => NULL,
				'name' => 'eyatra-mobile-app',
				'display_name' => 'eYatra Mobile App',
			],

			//DASHBOARD
			9001 => [
				'display_order' => 1,
				'parent_id' => 9000,
				'name' => 'eyatra-mobile-dashboard',
				'display_name' => 'Dashboard',
			],

			//TRIPS
			9002 => [
				'display_order' => 1,
				'parent_id' => 9001,
				'name' => 'eyatra-mobile-trips',
				'display_name' => 'Trips',
			],
			9003 => [
				'display_order' => 2,
				'parent_id' => 9002,
				'name' => 'eyatra-mobile-trip-add',
				'display_name' => 'Add',
			],
			9004 => [
				'display_order' => 3,
				'parent_id' => 9002,
				'name' => 'eyatra-mobile-trip-edit',
				'display_name' => 'Edit',
			],
			9005 => [
				'display_order' => 4,
				'parent_id' => 9002,
				'name' => 'eyatra-mobile-trip-delete',
				'display_name' => 'Delete',
			],

		];

		foreach ($permissions as $permission_id => $permsion) {
			$permission = Permission::firstOrNew([
				'id' => $permission_id,
			]);
			$permission->fill($permsion);
			$permission->mobile_menu = 1;
			$permission->save();
		}
	}
}