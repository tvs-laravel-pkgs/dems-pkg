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
				'display_name' => 'Mobile App',
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
				'parent_id' => 9000,
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

			//MANAGER VERIFICATIONS
			//TRIP VERIFICATION
			9006 => [
				'display_order' => 1,
				'parent_id' => 9000,
				'name' => 'eyatra-mobile-trips-verification',
				'display_name' => 'Trip Verification',
			],

			//TRIP CLAIM VERIFICATION
			9007 => [
				'display_order' => 1,
				'parent_id' => 9000,
				'name' => 'eyatra-mobile-trips-claim-verification',
				'display_name' => 'Claim Verification',
			],
			9008 => [
				'display_order' => 1,
				'parent_id' => 9000,
				'name' => 'eyatra-mobile-trips-claim-verification 2',
				'display_name' => 'Claim Verification 2',
			],
			//PETTY CASH SIGNATURE

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