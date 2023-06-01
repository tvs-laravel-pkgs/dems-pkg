<?php
namespace Uitoux\EYatra\Database\Seeds;
use App\Permission;
use Illuminate\Database\Seeder;

class TravelexPermissionSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$permissions = [
			5525 => [
				'display_order' => 5,
				'parent_id' => 5520,
				'name' => 'hrms-to-travelex-employee-addition',
				'display_name' => 'HRMS To Travelex Employee Addition',
			],
			5526 => [
				'display_order' => 6,
				'parent_id' => 5520,
				'name' => 'hrms-to-travelex-employee-updation',
				'display_name' => 'HRMS To Travelex Employee Updation',
			],
			5527 => [
				'display_order' => 7,
				'parent_id' => 5520,
				'name' => 'hrms-to-travelex-employee-deletion',
				'display_name' => 'HRMS To Travelex Employee Deletion',
			],
			5528 => [
				'display_order' => 8,
				'parent_id' => 5520,
				'name' => 'hrms-to-travelex-employee-reporting-to-updation',
				'display_name' => 'HRMS To Travelex Employee Reporting To Updation',
			],
			5529 => [
				'display_order' => 9,
				'parent_id' => 5520,
				'name' => 'hrms-to-travelex-employee-manual-addition',
				'display_name' => 'HRMS To Travelex Employee Manual Addition',
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
