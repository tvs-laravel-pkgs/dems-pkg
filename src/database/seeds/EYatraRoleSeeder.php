<?php

namespace Uitoux\EYatra\Database\Seeds;

use App\Role;
use Illuminate\Database\Seeder;

class EYatraRoleSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		//Role::where('id', '>=', 1)->forceDelete();

		$records = [

			//EYATRA ADMIN
			500 => [
				//'company_id' => 1,
				'display_order' => 1,
				'display_name' => 'eYatra Admin',
				'fixed_roles' => 0,
				'created_by' => 1,
				'permissions' => [
					//MAIN MENUS
					//5000,

					//ADMIN PERMISSION
					5260,

					//AGENT CLAIM
					5240,

					//TRIPS
					5001, 5002, 5003, 5004, 5005, 5480,

					// //TRIPS VERIFICATION
					// 5060, 5061, 5482,

					// //TRIPS BOOKING REQUESTS
					// 5160, 5161,

					//MASTERS
					5080,

					//ROLES
					5010, 5600, 5601, 5602,

					//MASTERS > OUTLETS
					5020, 5021, 5022, 5023,

					//MASTERS > EMPLOYEES
					5040, 5041, 5042, 5043,

					//MASTERS > AGENTS
					5100, 5101, 5102, 5103,

					//MASTERS > GRADES
					5120, 5121, 5122, 5123,

					//MASTERS > STATES
					5140, 5141, 5142, 5143,

					//MASTERS > TRAVEL PURPOSES
					5180, 5181, 5182, 5183,

					//MASTERS > TRAVEL MODES
					5200, 5201, 5202, 5203,

					//MASTERS > LOCAL TRAVEL MODES
					5220, 5221, 5222, 5223,

					//MASTERS > CATEGORY
					5280, 5281, 5282, 5283,

					//MASTERS > CITIES
					5300, 5301, 5302, 5303,

					//MASTERS > DESIGNATIONS
					5320, 5321, 5322, 5323,

					//MASTERS > REGIONS
					5340, 5341, 5342, 5343,

					//MASTERS > REJECTION REASONS
					5360,

					//MASTERS > REJECTION REASONS > TRIP REQUEST REJECT
					5380, 5381, 5382, 5383,

					//MASTERS > REJECTION REASONS > TRIP ADVANCE REQUEST REJECT
					5400, 5401, 5402, 5403,

					//MASTERS > REJECTION REASONS > TRIP CLAIM REJECT
					5420, 5421, 5422, 5423,

					//MASTERS > REJECTION REASONS > AGENT CLAIM REJECT
					5440, 5441, 5442, 5443,

					//MASTERS > REJECTION REASONS > VOUCHER CLAIM REJECT
					5460, 5461, 5462, 5463,

					//MASTERS > OUTLET REIMBURSEMENT
					5761,

					//MASTERS > IMPORT JOBS
					5760,

					// //EMPLOYEE CLAIM VERIFICATION 1
					// 5500,

					// // //EMPLOYEE CLAIM VERIFICATION 2
					// // 5520,

					// //AGENT CLAIM VERIFICATION 1
					// 5540,

					//MASTERS > COA CATEGORIES
					5580,

					//MASTERS > COA CATEGORIES > ACCOUNT TYPES
					5600, 5601, 5602, 5603,

					//MASTERS > COA CATEGORIES > BALANCE TYPES
					5620, 5621, 5622, 5623,

					//MASTERS > COA CATEGORIES > FINAL STATEMENT
					5640, 5641, 5642, 5643,

					//MASTERS > COA CATEGORIES > GROUPS
					5660, 5661, 5662, 5663,

					//MASTERS > COA CATEGORIES > SUB GROUPS
					5680, 5681, 5682, 5683,

					//MASTERS > COA CODES
					5700, 5701, 5702, 5703,

					//EXPENSE VOUCHERS
					5580,

				],
			],

			//EYATRA EMPLOYEE
			501 => [
				//'company_id' => 1,
				'display_order' => 1,
				'display_name' => 'eYatra Employee',
				'fixed_roles' => 0,
				'created_by' => 1,
				'permissions' => [
					//TRIPS
					5001, 5002, 5003, 5004, 5480, 5481,

					//MOBILE PERMISSIONS
					//TRIPS
					9000, 9001, 9002, 9003, 9004, 9005,

					//EXPENSE VOUCHERS
					5721,

				],
			],

			//EYATRA MANAGER
			502 => [
				//'company_id' => 1,
				'display_order' => 1,
				'display_name' => 'eYatra Manager',
				'fixed_roles' => 0,
				'created_by' => 1,
				'permissions' => [

					//TRIPS
					5001, 5002, 5003, 5004, 5480, 5481, 5482,

					//TRIPS VERIFICATION
					5060,

					//CLAIM VERIFICATION 1
					5500,

					//EXPENSE VOUCHERS
					5721,

					//EXPENSE VOUCHERS VERIFICATION
					5722,

					//MOBILE PERMISSIONS
					//TRIPS
					9000, 9001, 9002, 9003, 9004, 9005,

					//TRIPS VERIFICATION
					9006,

					//CLAIM VERIFICATION
					9007,
				],
			],

			//EYATRA AGENT
			503 => [
				//'company_id' => 1,
				'display_order' => 1,
				'display_name' => 'eYatra Agent',
				'fixed_roles' => 0,
				'created_by' => 1,
				'permissions' => [

					//TRIPS BOOKING REQUESTS
					5483,

					//AGENT CLAIM
					5240, 5484,

				],
			],

			//EYATRA CASHIER
			504 => [
				//'company_id' => 1,
				'display_order' => 1,
				'display_name' => 'eYatra Cashier',
				'fixed_roles' => 0,
				'created_by' => 1,
				'permissions' => [

					//TRIPS
					5001, 5002, 5003, 5004, 5480, 5481,

					//AGENT ROLE
					5014,

					//PETTY CASH
					5721,

					//PETTY CASH VERIFICATION
					5723,

					//OUTLET REIMBURSEMENT
					5781,

				],
			],

			//EYATRA FINANCIER
			505 => [
				//'company_id' => 1,
				'display_order' => 1,
				'display_name' => 'eYatra Financier',
				'fixed_roles' => 0,
				'created_by' => 1,
				'permissions' => [

					//TRIPS
					5001, 5002, 5003, 5004, 5480, 5481,

					//AGENT ROLE
					5014,

					//EMPLOYEE CLAIM VERIFICATION AND PAYMENT
					5521,

					//AGENT CLAIM VERIFICATION AND PAYMENT
					5540,

					//ADVANCE REQUESTS
					5560,

					//PETTY CASH
					5721,

					//PETTY CASH VERIFICATION
					5724,

				],
			],

			//EYATRA CLAIM VERIFIER
			506 => [
				//'company_id' => 1,
				'display_order' => 1,
				'display_name' => 'eYatra Claim Verifier',
				'fixed_roles' => 0,
				'created_by' => 1,
				'permissions' => [

					//TRIPS
					5001, 5002, 5003, 5004, 5480, 5481, 5482,

					//TRIPS VERIFICATION
					5060,

					//CLAIM VERIFICATION 1
					5500,

					//CLAIM VERIFICATION 2
					5520,

					//EXPENSE VOUCHERS
					5721,

					//EXPENSE VOUCHERS VERIFICATION
					5722,
				],
			],
		];

		// $sync_type = $this->command->ask("Sync roles completely?", 'y');

		foreach ($records as $id => $record_data) {
			$permissions = $record_data['permissions'];
			unset($record_data['permissions']);
			$record = Role::firstOrNew([
				'id' => $id,
			]);
			$record->fill($record_data);
			if (isset($record_data['name'])) {
				$record->name = $record_data['name'];
			} else {
				$record->name = $record_data['display_name'];
			}

			$record->save();
			//$record->perms()->syncWithoutDetaching($permissions);
			$record->perms()->sync($permissions);
		}

	}
}
