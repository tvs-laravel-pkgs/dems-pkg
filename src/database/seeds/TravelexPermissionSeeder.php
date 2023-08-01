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
			5710 => [
				'display_order' => 3,
				'parent_id' => 5680,
				'name' => 'eyatra-trip-report',
				'display_name' => 'Trip Report',
			],
			5711 => [
				'display_order' => 5,
				'parent_id' => 5500,
				'name' => 'trip-claim-employee-return-payment-detail',
				'display_name' => 'Employee Return Payment Detail',
			],

			5712 => [
				'display_order' => 102,
				'parent_id' => 5020,
				'name' => 'trip-oracle-sync',
				'display_name' => 'Trip Oracle Sync',
			],
			5713 => [
				'display_order' => 1,
				'parent_id' => 5560,
				'name' => 'eyatra-pcv',
				'display_name' => 'PCV',
			],
			5714 => [
				'display_order' => 2,
				'parent_id' => 5560,
				'name' => 'eyatra-pcv-add',
				'display_name' => 'PCV Add',
			],
			5715 => [
				'display_order' => 3,
				'parent_id' => 5560,
				'name' => 'eyatra-pcv-edit',
				'display_name' => 'PCV Edit',
			],
			5716 => [
				'display_order' => 4,
				'parent_id' => 5560,
				'name' => 'eyatra-pcv-view',
				'display_name' => 'PCV View',
			],

			5717 => [
				'display_order' => 5,
				'parent_id' => 5560,
				'name' => 'eyatra-advance-pcv',
				'display_name' => 'Advance PCV',
			],
			5718 => [
				'display_order' => 6,
				'parent_id' => 5560,
				'name' => 'eyatra-advance-pcv-add',
				'display_name' => 'Advance PCV Add',
			],
			5719 => [
				'display_order' => 7,
				'parent_id' => 5560,
				'name' => 'eyatra-advance-pcv-edit',
				'display_name' => 'Advance PCV Edit',
			],
			5720 => [
				'display_order' => 8,
				'parent_id' => 5560,
				'name' => 'eyatra-advance-pcv-delete',
				'display_name' => 'Advance PCV Delete',
			],
			5721 => [
				'display_order' => 9,
				'parent_id' => 5560,
				'name' => 'eyatra-advance-pcv-view',
				'display_name' => 'Advance PCV View',
			],
			5722 => [
				'display_order' => 10,
				'parent_id' => 5560,
				'name' => 'eyatra-advance-pcv-employee-return-payment-detail',
				'display_name' => 'Advance PCV Employee Return Payment Detail',
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
