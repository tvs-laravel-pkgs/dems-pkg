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
				'display_name' => 'Admin',
				'fixed_roles' => 0,
				'created_by' => 1,
				'permissions' => [

					// MAIN MENUS
					// 5000,

					//ADMIN PERMISSION
					// 5260,

					//AGENT CLAIM
					5240,

					//TRIPS
					// 5001, 5002, 5003, 5004, 5005, 5480,

					// //TRIPS VERIFICATION
					// 5060, 5061, 5482,

					// //TRIPS BOOKING REQUESTS
					// 5160, 5161,

					//MASTERS
					5020,

					//ROLES
					5620, 5621, 5622, 5623,

					//MASTERS > OUTLETS
					5480, 5481, 5482, 5483, 5484,

					//MASTERS > ALTERNATE APPROVAL
					5490, 5491,

					//MASTERS > EMPLOYEES
					5520, 5521, 5522, 5523, 5524,

					//MASTERS > AGENTS
					5080, 5081, 5082, 5083,

					//MASTERS > GRADES
					5100, 5101, 5102, 5103,

					//MASTERS > STATES
					5120, 5121, 5122, 5123,

					//MASTERS > TRAVEL PURPOSES
					5021, 5022, 5023, 5024,

					//MASTERS > TRAVEL MODES
					5040, 5041, 5042, 5043,

					//MASTERS > LOCAL TRAVEL MODES
					5060, 5061, 5062, 5063,

					//MASTERS > CATEGORY
					5140, 5141, 5142, 5143,

					//MASTERS > CITIES
					5160, 5161, 5162, 5163, 5164,

					//MASTERS > DESIGNATIONS
					5180, 5181, 5182, 5183,

					//MASTERS > REGIONS
					5200, 5201, 5202, 5203,

					//MASTERS > REJECTION REASONS
					5220, 5221, 5222, 5223,

					// //MASTERS > REJECTION REASONS > TRIP REQUEST REJECT
					// 5240, 5241, 5242, 5243,

					// //MASTERS > REJECTION REASONS > TRIP ADVANCE REQUEST REJECT
					// 5260, 5261, 5262, 5263,

					// //MASTERS > REJECTION REASONS > TRIP CLAIM REJECT
					// 5280, 5281, 5282, 5283,

					// //MASTERS > REJECTION REASONS > AGENT CLAIM REJECT
					// 5300, 5301, 5302, 5303,

					// //MASTERS > REJECTION REASONS > VOUCHER CLAIM REJECT
					// 5320, 5321, 5322, 5323,

					//MASTERS > OUTLET REIMBURSEMENT
					5465,

					//MASTERS > IMPORT JOBS
					5464,

					//MASTERS > PETTY CASH EXPENSE TYPES
					5640, 5641, 5642, 5643,

					// //EMPLOYEE CLAIM VERIFICATION 1
					// 5500,

					// // //EMPLOYEE CLAIM VERIFICATION 2
					// // 5520,

					// //AGENT CLAIM VERIFICATION 1
					// 5540,

					//MASTERS > COA CATEGORIES
					5340, 5341, 5342, 5343,

					// //MASTERS > COA CATEGORIES > ACCOUNT TYPES
					// 5360, 5361, 5362, 5363,

					// //MASTERS > COA CATEGORIES > BALANCE TYPES
					// 5380, 5381, 5382, 5383,

					// //MASTERS > COA CATEGORIES > FINAL STATEMENT
					// 5400, 5401, 5402, 5403,

					// //MASTERS > COA CATEGORIES > GROUPS
					// 5420, 5421, 5422, 5423,

					// //MASTERS > COA CATEGORIES > SUB GROUPS
					// 5440, 5441, 5442, 5443,

					//MASTERS > COA CODES
					5460, 5461, 5462, 5463,

					//EXPENSE VOUCHERS
					// 5580,

				],
			],

			//EYATRA EMPLOYEE
			501 => [
				//'company_id' => 1,
				'display_order' => 1,
				'display_name' => 'Employee',
				'fixed_roles' => 0,
				'created_by' => 1,
				'permissions' => [

					// MAIN MENUS
					// 5000,

					//TRIPS
					5500, 5501, 5502, 5503, 5504, 5505, 5506,

					//MOBILE PERMISSIONS
					//TRIPS
					9000, 9001, 9002, 9003, 9004, 9005,
					//LOCAL TRIPS
					5660, 5661, 5662, 5663, 5664, 5665, 5666,

					//EXPENSE VOUCHERS
					5560,

				],
			],

			//EYATRA MANAGER
			502 => [
				//'company_id' => 1,
				'display_order' => 1,
				'display_name' => 'Manager',
				'fixed_roles' => 0,
				'created_by' => 1,
				'permissions' => [

					// MAIN MENUS
					// 5000,

					//TRIPS
					5500, 5501, 5502, 5503, 5504, 5505, 5506,

					//LOCAL TRIPS
					5660, 5661, 5662, 5663, 5664, 5665, 5666,

					//EXPENSE VOUCHERS
					5560,

					//APPROVALS / VERIFICATION
					5600, 5601, 5603, 5607, 5610,

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
				'display_name' => 'Agent',
				'fixed_roles' => 0,
				'created_by' => 1,
				'permissions' => [

					// MAIN MENUS
					// 5000,

					//TRIPS BOOKING REQUESTS
					5541,

					//AGENT CLAIM
					5542,

				],
			],

			//EYATRA CASHIER
			504 => [
				//'company_id' => 1,
				'display_order' => 1,
				'display_name' => 'Cashier',
				'fixed_roles' => 0,
				'created_by' => 1,
				'permissions' => [

					// MAIN MENUS
					// 5000,

					//TRIPS
					5500, 5501, 5502, 5503, 5504, 5505, 5506,

					//LOCAL TRIPS
					5660, 5661, 5662, 5663, 5664, 5665, 5666,

					//EXPENSE VOUCHERS
					5560,

					//APPROVALS / VERIFICATION
					5600, 5608,

					//OUTLET REIMBURSEMENT
					5580,

				],
			],

			//EYATRA FINANCIER
			505 => [
				//'company_id' => 1,
				'display_order' => 1,
				'display_name' => 'Financier',
				'fixed_roles' => 0,
				'created_by' => 1,
				'permissions' => [

					// MAIN MENUS
					// 5000,

					//APPROVALS / VERIFICATION
					5600, 5602, 5605, 5606, 5609, 5611,

					//REPORTS
					5680, 5681, 5682,

				],
			],

			//EYATRA CLAIM VERIFIER
			506 => [
				//'company_id' => 1,
				'display_order' => 1,
				'display_name' => 'Claim Verifier',
				'fixed_roles' => 0,
				'created_by' => 1,
				'permissions' => [

					// MAIN MENUS
					// 5000,

					//TRIPS
					5500, 5501, 5502, 5503, 5504, 5505, 5506,

					//LOCAL TRIPS
					5660, 5661, 5662, 5663, 5664, 5665, 5666,

					//EXPENSE VOUCHERS
					5560,

					//APPROVALS / VERIFICATION
					5600, 5601, 5603, 5604, 5607, 5610,

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

			// dd($permissions);
			//$record->perms()->syncWithoutDetaching($permissions);
			$record->perms()->sync($permissions);
			// dd();
		}

	}
}
