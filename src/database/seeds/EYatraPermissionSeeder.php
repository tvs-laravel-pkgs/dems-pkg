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
				'display_name' => 'Web Portal',
			],
			// 5001 => [
			// 	'display_order' => 101,
			// 	'parent_id' => NULL,
			// 	'name' => 'eyatra-top-level-menus',
			// 	'display_name' => 'eYatra Top Level Menus',
			// ],

			// //ROLES
			// 5010 => [
			// 	'display_order' => 101,
			// 	'parent_id' => NULL,
			// 	'name' => 'eyatra-roles',
			// 	'display_name' => 'eYatra Roles',
			// ],
			// 5011 => [
			// 	'display_order' => 101,
			// 	'parent_id' => 5010,
			// 	'name' => 'eyatra-employee',
			// 	'display_name' => 'Employee',
			// ],
			// 5012 => [
			// 	'display_order' => 101,
			// 	'parent_id' => 5010,
			// 	'name' => 'eyatra-manager',
			// 	'display_name' => 'Manager',
			// ],
			// 5013 => [
			// 	'display_order' => 101,
			// 	'parent_id' => 5010,
			// 	'name' => 'eyatra-agent',
			// 	'display_name' => 'Agent',
			// ],
			// 5014 => [
			// 	'display_order' => 101,
			// 	'parent_id' => 5010,
			// 	'name' => 'eyatra-cashier',
			// 	'display_name' => 'Cashier',
			// ],
			// 5015 => [
			// 	'display_order' => 101,
			// 	'parent_id' => 5010,
			// 	'name' => 'eyatra-financier',
			// 	'display_name' => 'Cashier',
			// ],
			// 5016 => [
			// 	'display_order' => 101,
			// 	'parent_id' => 5010,
			// 	'name' => 'eYatra-claim-verifier',
			// 	'display_name' => 'Claim Verifier',
			// ],

			//MASTERS
			5020 => [
				'display_order' => 1,
				'parent_id' => 5000,
				'name' => 'eyatra-masters',
				'display_name' => 'Masters',
			],

			//MASTERS > TRAVEL PURPOSES
			5021 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-travel-purposes',
				'display_name' => 'Travel Purposes',
			],
			5022 => [
				'display_order' => 1,
				'parent_id' => 5021,
				'name' => 'eyatra-travel-purposes-add',
				'display_name' => 'Add',
			],
			5023 => [
				'display_order' => 2,
				'parent_id' => 5021,
				'name' => 'eyatra-travel-purposes-edit',
				'display_name' => 'Edit',
			],
			5024 => [
				'display_order' => 3,
				'parent_id' => 5021,
				'name' => 'eyatra-travel-purposes-delete',
				'display_name' => 'Delete',
			],

			//MASTERS > TRAVEL MODES
			5040 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-travel-modes',
				'display_name' => 'Travel Modes',
			],
			5041 => [
				'display_order' => 1,
				'parent_id' => 5040,
				'name' => 'eyatra-travel-modes-add',
				'display_name' => 'Add',
			],
			5042 => [
				'display_order' => 2,
				'parent_id' => 5040,
				'name' => 'eyatra-travel-modes-edit',
				'display_name' => 'Edit',
			],
			5043 => [
				'display_order' => 3,
				'parent_id' => 5040,
				'name' => 'eyatra-travel-modes-delete',
				'display_name' => 'Delete',
			],

			//MASTERS > LOCAL TRAVEL MODES
			5060 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-local-travel-modes',
				'display_name' => 'Local Travel Modes',
			],
			5061 => [
				'display_order' => 1,
				'parent_id' => 5060,
				'name' => 'eyatra-local-travel-modes-add',
				'display_name' => 'Add',
			],
			5062 => [
				'display_order' => 2,
				'parent_id' => 5060,
				'name' => 'eyatra-local-travel-modes-edit',
				'display_name' => 'Edit',
			],
			5063 => [
				'display_order' => 3,
				'parent_id' => 5060,
				'name' => 'eyatra-local-travel-modes-delete',
				'display_name' => 'Delete',
			],

			//MASTERS > AGENTS
			5080 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-agents',
				'display_name' => 'Agents',
			],
			5081 => [
				'display_order' => 1,
				'parent_id' => 5080,
				'name' => 'eyatra-agent-add',
				'display_name' => 'Add',
			],
			5082 => [
				'display_order' => 2,
				'parent_id' => 5080,
				'name' => 'eyatra-agent-edit',
				'display_name' => 'Edit',
			],
			5083 => [
				'display_order' => 3,
				'parent_id' => 5080,
				'name' => 'eyatra-agent-delete',
				'display_name' => 'Delete',
			],

			//MASTERS > GRADES
			5100 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-grades',
				'display_name' => 'Grades',
			],
			5101 => [
				'display_order' => 1,
				'parent_id' => 5100,
				'name' => 'eyatra-grade-add',
				'display_name' => 'Add',
			],
			5102 => [
				'display_order' => 2,
				'parent_id' => 5100,
				'name' => 'eyatra-grade-edit',
				'display_name' => 'Edit',
			],
			5103 => [
				'display_order' => 3,
				'parent_id' => 5100,
				'name' => 'eyatra-grade-delete',
				'display_name' => 'Delete',
			],

			//MASTERS > STATES
			5120 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-states',
				'display_name' => 'States',
			],
			5121 => [
				'display_order' => 1,
				'parent_id' => 5120,
				'name' => 'eyatra-state-add',
				'display_name' => 'Add',
			],
			5122 => [
				'display_order' => 2,
				'parent_id' => 5120,
				'name' => 'eyatra-state-edit',
				'display_name' => 'Edit',
			],
			5123 => [
				'display_order' => 3,
				'parent_id' => 5120,
				'name' => 'eyatra-state-delete',
				'display_name' => 'Delete',
			],

			//MASTERS > CATEGORIES
			5140 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-category',
				'display_name' => 'Category',
			],
			5141 => [
				'display_order' => 1,
				'parent_id' => 5140,
				'name' => 'eyatra-category-add',
				'display_name' => 'Add',
			],
			5142 => [
				'display_order' => 2,
				'parent_id' => 5140,
				'name' => 'eyatra-category-edit',
				'display_name' => 'Edit',
			],
			5143 => [
				'display_order' => 3,
				'parent_id' => 5140,
				'name' => 'eyatra-category-delete',
				'display_name' => 'Delete',
			],

			//MASTERS > CITIES
			5160 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-cities',
				'display_name' => 'Cities',
			],
			5161 => [
				'display_order' => 1,
				'parent_id' => 5160,
				'name' => 'eyatra-city-add',
				'display_name' => 'Add',
			],
			5162 => [
				'display_order' => 2,
				'parent_id' => 5160,
				'name' => 'eyatra-city-edit',
				'display_name' => 'Edit',
			],
			5163 => [
				'display_order' => 3,
				'parent_id' => 5160,
				'name' => 'eyatra-city-delete',
				'display_name' => 'Delete',
			],
			5164 => [
				'display_order' => 3,
				'parent_id' => 5160,
				'name' => 'eyatra-import-city',
				'display_name' => 'Import City',
			],

			//MASTERS > DESIGNATIONS
			5180 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-designation',
				'display_name' => 'Designation',
			],
			5181 => [
				'display_order' => 1,
				'parent_id' => 5180,
				'name' => 'eyatra-designation-add',
				'display_name' => 'Add',
			],
			5182 => [
				'display_order' => 2,
				'parent_id' => 5180,
				'name' => 'eyatra-designation-edit',
				'display_name' => 'Edit',
			],
			5183 => [
				'display_order' => 3,
				'parent_id' => 5180,
				'name' => 'eyatra-designation-delete',
				'display_name' => 'Delete',
			],

			//MASTERS > REGIONS
			5200 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-region',
				'display_name' => 'Region',
			],
			5201 => [
				'display_order' => 1,
				'parent_id' => 5200,
				'name' => 'eyatra-region-add',
				'display_name' => 'Add',
			],
			5202 => [
				'display_order' => 2,
				'parent_id' => 5200,
				'name' => 'eyatra-region-edit',
				'display_name' => 'Edit',
			],
			5203 => [
				'display_order' => 3,
				'parent_id' => 5200,
				'name' => 'eyatra-region-delete',
				'display_name' => 'Delete',
			],

			//MASTERS > REJECTION REASONS
			5220 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-rejection',
				'display_name' => 'Rejection',
			],
			5221 => [
				'display_order' => 1,
				'parent_id' => 5220,
				'name' => 'eyatra-rejection-add',
				'display_name' => 'Add',
			],
			5222 => [
				'display_order' => 2,
				'parent_id' => 5220,
				'name' => 'eyatra-rejection-edit',
				'display_name' => 'Edit',
			],
			5223 => [
				'display_order' => 3,
				'parent_id' => 5220,
				'name' => 'eyatra-rejection-delete',
				'display_name' => 'Delete',
			],

			// //MASTERS > REJECTION REASONS > TRIP REQUEST REJECTION REASONS
			// 5240 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5220,
			// 	'name' => 'eyatra-trip-request-reject',
			// 	'display_name' => 'Trip Request Reject',
			// ],
			// 5241 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5240,
			// 	'name' => 'eyatra-trip-request-reject-add',
			// 	'display_name' => 'Add',
			// ],
			// 5242 => [
			// 	'display_order' => 2,
			// 	'parent_id' => 5240,
			// 	'name' => 'eyatra-trip-request-reject-edit',
			// 	'display_name' => 'Edit',
			// ],
			// 5243 => [
			// 	'display_order' => 3,
			// 	'parent_id' => 5240,
			// 	'name' => 'eyatra-trip-request-reject-delete',
			// 	'display_name' => 'Delete',
			// ],

			// //MASTERS > REJECTION REASONS > TRIP ADVANCE REJECTION REASONS
			// 5260 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5220,
			// 	'name' => 'eyatra-trip-advance-reject',
			// 	'display_name' => 'Trip Advance Reject',
			// ],
			// 5261 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5260,
			// 	'name' => 'eyatra-trip-advance-reject-add',
			// 	'display_name' => 'Add',
			// ],
			// 5262 => [
			// 	'display_order' => 2,
			// 	'parent_id' => 5260,
			// 	'name' => 'eyatra-trip-advance-reject-edit',
			// 	'display_name' => 'Edit',
			// ],
			// 5263 => [
			// 	'display_order' => 3,
			// 	'parent_id' => 5260,
			// 	'name' => 'eyatra-trip-advance-reject-delete',
			// 	'display_name' => 'Delete',
			// ],

			// //MASTERS > REJECTION REASONS > TRIP CLAIM REJECTION REASONS
			// 5280 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5220,
			// 	'name' => 'eyatra-trip-claim-reject',
			// 	'display_name' => 'Trip Claim Reject',
			// ],
			// 5281 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5280,
			// 	'name' => 'eyatra-trip-claim-reject-add',
			// 	'display_name' => 'Add',
			// ],
			// 5282 => [
			// 	'display_order' => 2,
			// 	'parent_id' => 5280,
			// 	'name' => 'eyatra-trip-claim-reject-edit',
			// 	'display_name' => 'Edit',
			// ],
			// 5283 => [
			// 	'display_order' => 3,
			// 	'parent_id' => 5280,
			// 	'name' => 'eyatra-trip-claim-reject-delete',
			// 	'display_name' => 'Delete',
			// ],

			// //MASTERS > REJECTION REASONS > AGENT CLAIM REJECTION REASONS
			// 5300 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5220,
			// 	'name' => 'eyatra-agent-claim-reject',
			// 	'display_name' => 'Trip Claim Reject',
			// ],
			// 5301 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5300,
			// 	'name' => 'eyatra-agent-claim-reject-add',
			// 	'display_name' => 'Add',
			// ],
			// 5302 => [
			// 	'display_order' => 2,
			// 	'parent_id' => 5300,
			// 	'name' => 'eyatra-agent-claim-reject-edit',
			// 	'display_name' => 'Edit',
			// ],
			// 5303 => [
			// 	'display_order' => 3,
			// 	'parent_id' => 5300,
			// 	'name' => 'eyatra-agent-claim-reject-delete',
			// 	'display_name' => 'Delete',
			// ],

			// //MASTERS > REJECTION REASONS > VOUCHER CLAIM REJECTION REASONS
			// 5320 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5220,
			// 	'name' => 'eyatra-voucher-claim-reject',
			// 	'display_name' => 'Voucher Claim Reject',
			// ],
			// 5321 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5320,
			// 	'name' => 'eyatra-voucher-claim-reject-add',
			// 	'display_name' => 'Add',
			// ],
			// 5322 => [
			// 	'display_order' => 2,
			// 	'parent_id' => 5320,
			// 	'name' => 'eyatra-voucher-claim-reject-edit',
			// 	'display_name' => 'Edit',
			// ],
			// 5323 => [
			// 	'display_order' => 3,
			// 	'parent_id' => 5320,
			// 	'name' => 'eyatra-voucher-claim-reject-delete',
			// 	'display_name' => 'Delete',
			// ],

			//MASTERS > COA CATEGORIES
			5340 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-coa-categories',
				'display_name' => 'COA Categories',
			],
			5341 => [
				'display_order' => 1,
				'parent_id' => 5340,
				'name' => 'eyatra-coa-categories-add',
				'display_name' => 'Add',
			],
			5342 => [
				'display_order' => 1,
				'parent_id' => 5340,
				'name' => 'eyatra-coa-categories-edit',
				'display_name' => 'Edit',
			],
			5343 => [
				'display_order' => 1,
				'parent_id' => 5340,
				'name' => 'eyatra-coa-categories-delete',
				'display_name' => 'Delete',
			],

			// //MASTERS > COA CATEGORIES > ACCOUNT TYPES
			// 5360 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5340,
			// 	'name' => 'eyatra-coa-account-types',
			// 	'display_name' => 'COA Account Types',
			// ],
			// 5361 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5360,
			// 	'name' => 'eyatra-coa-account-types-add',
			// 	'display_name' => 'Add',
			// ],
			// 5362 => [
			// 	'display_order' => 2,
			// 	'parent_id' => 5360,
			// 	'name' => 'eyatra-coa-account-types-edit',
			// 	'display_name' => 'Edit',
			// ],
			// 5363 => [
			// 	'display_order' => 3,
			// 	'parent_id' => 5360,
			// 	'name' => 'eyatra-coa-account-types-delete',
			// 	'display_name' => 'Delete',
			// ],

			// //MASTERS > COA CATEGORIES > BALANCE TYPES
			// 5380 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5340,
			// 	'name' => 'eyatra-coa-balance-types',
			// 	'display_name' => 'COA Balance Types',
			// ],
			// 5381 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5380,
			// 	'name' => 'eyatra-coa-balance-types-add',
			// 	'display_name' => 'Add',
			// ],
			// 5382 => [
			// 	'display_order' => 2,
			// 	'parent_id' => 5380,
			// 	'name' => 'eyatra-coa-balance-types-edit',
			// 	'display_name' => 'Edit',
			// ],
			// 5383 => [
			// 	'display_order' => 3,
			// 	'parent_id' => 5380,
			// 	'name' => 'eyatra-coa-balance-types-delete',
			// 	'display_name' => 'Delete',
			// ],

			// //MASTERS > COA CATEGORIES > FINAL STATEMENT
			// 5400 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5340,
			// 	'name' => 'eyatra-coa-final-statement',
			// 	'display_name' => 'COA Final Statement',
			// ],
			// 5401 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5400,
			// 	'name' => 'eyatra-coa-final-statement-add',
			// 	'display_name' => 'Add',
			// ],
			// 5402 => [
			// 	'display_order' => 2,
			// 	'parent_id' => 5400,
			// 	'name' => 'eyatra-coa-final-statement-edit',
			// 	'display_name' => 'Edit',
			// ],
			// 5403 => [
			// 	'display_order' => 3,
			// 	'parent_id' => 5400,
			// 	'name' => 'eyatra-coa-final-statement-delete',
			// 	'display_name' => 'Delete',
			// ],

			// //MASTERS > COA CATEGORIES > GROUPS
			// 5420 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5340,
			// 	'name' => 'eyatra-coa-groups',
			// 	'display_name' => 'COA Groups',
			// ],
			// 5421 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5420,
			// 	'name' => 'eyatra-coa-groups-add',
			// 	'display_name' => 'Add',
			// ],
			// 5422 => [
			// 	'display_order' => 2,
			// 	'parent_id' => 5420,
			// 	'name' => 'eyatra-coa-groups-edit',
			// 	'display_name' => 'Edit',
			// ],
			// 5423 => [
			// 	'display_order' => 3,
			// 	'parent_id' => 5420,
			// 	'name' => 'eyatra-coa-groups-delete',
			// 	'display_name' => 'Delete',
			// ],

			// //MASTERS > COA CATEGORIES > SUB GROUPS
			// 5440 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5340,
			// 	'name' => 'eyatra-coa-sub-groups',
			// 	'display_name' => 'COA Groups',
			// ],
			// 5441 => [
			// 	'display_order' => 1,
			// 	'parent_id' => 5440,
			// 	'name' => 'eyatra-coa-sub-groups-add',
			// 	'display_name' => 'Add',
			// ],
			// 5442 => [
			// 	'display_order' => 2,
			// 	'parent_id' => 5440,
			// 	'name' => 'eyatra-coa-sub-groups-edit',
			// 	'display_name' => 'Edit',
			// ],
			// 5443 => [
			// 	'display_order' => 3,
			// 	'parent_id' => 5440,
			// 	'name' => 'eyatra-coa-sub-groups-delete',
			// 	'display_name' => 'Delete',
			// ],

			//MASTERS > COA CODES
			5460 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-coa-codes',
				'display_name' => 'COA Codes',
			],
			5461 => [
				'display_order' => 1,
				'parent_id' => 5460,
				'name' => 'eyatra-coa-codes-add',
				'display_name' => 'Add',
			],
			5462 => [
				'display_order' => 2,
				'parent_id' => 5460,
				'name' => 'eyatra-coa-codes-edit',
				'display_name' => 'Edit',
			],
			5463 => [
				'display_order' => 3,
				'parent_id' => 5460,
				'name' => 'eyatra-coa-codes-delete',
				'display_name' => 'Delete',
			],

			//MASTERS > IMPORT JOBS
			5464 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-import-jobs',
				'display_name' => 'Import Jobs',
			],

			//MASTERS > OUTLET REIMBURSEMENT
			5465 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-outlet-reimbursement',
				'display_name' => 'Outlet Reimbursement',
			],

			//MASTERS > OUTLETS
			5480 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-outlets',
				'display_name' => 'Outlets',
			],
			5481 => [
				'display_order' => 1,
				'parent_id' => 5480,
				'name' => 'eyatra-outlet-add',
				'display_name' => 'Add',
			],
			5482 => [
				'display_order' => 1,
				'parent_id' => 5480,
				'name' => 'eyatra-outlet-edit',
				'display_name' => 'Edit',
			],
			5483 => [
				'display_order' => 1,
				'parent_id' => 5480,
				'name' => 'eyatra-outlet-delete',
				'display_name' => 'Delete',
			],
			5484 => [
				'display_order' => 1,
				'parent_id' => 5480,
				'name' => 'eyatra-import-outlet',
				'display_name' => 'Import Outlet',
			],

			//MASTERS > ALTERNATE APPROVAL
			5490 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-alternate-approval',
				'display_name' => 'Alternate Approve',
			],
			5491 => [
				'display_order' => 1,
				'parent_id' => 5490,
				'name' => 'eyatra-alternate-approval-edit',
				'display_name' => 'Edit',
			],

			//TRIPS
			5500 => [
				'display_order' => 1,
				'parent_id' => 5000,
				'name' => 'trips',
				'display_name' => 'Trips',
			],
			5501 => [
				'display_order' => 1,
				'parent_id' => 5500,
				'name' => 'trip-add',
				'display_name' => 'Add',
			],
			5502 => [
				'display_order' => 2,
				'parent_id' => 5500,
				'name' => 'trip-edit',
				'display_name' => 'Edit',
			],
			5503 => [
				'display_order' => 3,
				'parent_id' => 5500,
				'name' => 'trip-delete',
				'display_name' => 'Delete',
			],
			5504 => [
				'display_order' => 4,
				'parent_id' => 5500,
				'name' => 'view-all-trips',
				'display_name' => 'View All',
			],
			5505 => [
				'display_order' => 4,
				'parent_id' => 5500,
				'name' => 'eyatra-indv-trips',
				'display_name' => 'My Trips',
			],
			5506 => [
				'display_order' => 4,
				'parent_id' => 5500,
				'name' => 'eyatra-indv-claimed-trips',
				'display_name' => 'My Claimed Trips',
			],

			//MASTERS > EMPLOYEES
			5520 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-employees',
				'display_name' => 'Employees',
			],
			5521 => [
				'display_order' => 1,
				'parent_id' => 5520,
				'name' => 'eyatra-employee-add',
				'display_name' => 'Add',
			],
			5522 => [
				'display_order' => 1,
				'parent_id' => 5520,
				'name' => 'eyatra-employee-edit',
				'display_name' => 'Edit',
			],
			5523 => [
				'display_order' => 1,
				'parent_id' => 5520,
				'name' => 'eyatra-employee-delete',
				'display_name' => 'Delete',
			],
			5524 => [
				'display_order' => 1,
				'parent_id' => 5520,
				'name' => 'eyatra-import-employee',
				'display_name' => 'Import Employee',
			],

			//AGENTS
			5540 => [
				'display_order' => 1,
				'parent_id' => 5000,
				'name' => 'eyatra-agents',
				'display_name' => 'Agent',
			],

			//AGENT > BOOKING
			5541 => [
				'display_order' => 4,
				'parent_id' => 5540,
				'name' => 'eyatra-indv-trip-booking-requests',
				'display_name' => 'Booking Requests',
			],

			//AGENT > CLAIM
			5542 => [
				'display_order' => 5,
				'parent_id' => 5540,
				'name' => 'eyatra-indv-agent-claims',
				'display_name' => 'Agent Claims',
			],

			//PETTY CASH / EXPENSE VOUCHER
			5560 => [
				'display_order' => 7,
				'parent_id' => 5000,
				'name' => 'eyatra-indv-expense-vouchers',
				'display_name' => 'Expense Vouchers',
			],

			//CASHIER > OUTLET REIMBURSEMENT
			5580 => [
				'display_order' => 1,
				'parent_id' => 5000,
				'name' => 'eyatra-cashier-outlet-reimbursement',
				'display_name' => 'Cashier Outlet Reimbursement',
			],

		    5589 => [
				'display_order' => 1,
				'parent_id' => 5000,
				'name' => 'eyatra-indv-trip-approval',
				'display_name' => 'Trip Approval',
			],

			//APPROVALS / VERIFICATION
			5600 => [
				'display_order' => 1,
				'parent_id' => 5000,
				'name' => 'eyatra-verification',
				'display_name' => 'Verification / Approvals',
			],
			5601 => [
				'display_order' => 1,
				'parent_id' => 5600,
				'name' => 'eyatra-indv-trip-verifications',
				'display_name' => 'Trips Verification',
			],
			5602 => [
				'display_order' => 7,
				'parent_id' => 5600,
				'name' => 'eyatra-indv-trip-advance-requests',
				'display_name' => 'Trip Advance Requests',
			],
			5603 => [
				'display_order' => 6,
				'parent_id' => 5600,
				'name' => 'eyatra-indv-employee-claims-verification1',
				'display_name' => 'Employee Claims Verification 1',
			],

			5604 => [
				'display_order' => 6,
				'parent_id' => 5600,
				'name' => 'eyatra-indv-employee-claims-verification2',
				'display_name' => 'Employee Claim Verification 2',
			],

			5605 => [
				'display_order' => 6,
				'parent_id' => 5600,
				'name' => 'eyatra-indv-financier-claims-verification3',
				'display_name' => 'Financier Trip Claim Verification',
			],

			5606 => [
				'display_order' => 6,
				'parent_id' => 5600,
				'name' => 'eyatra-indv-agent-claims-verfication1',
				'display_name' => 'Agent Claims Verification 1',
			],
			5607 => [
				'display_order' => 8,
				'parent_id' => 5600,
				'name' => 'eyatra-indv-expense-vouchers-verification1',
				'display_name' => 'Expense Vouchers Manager Verfication',
			],
			5608 => [
				'display_order' => 8,
				'parent_id' => 5600,
				'name' => 'eyatra-indv-expense-vouchers-verification2',
				'display_name' => 'Expense Vouchers Cashier Verfication',
			],
			5609 => [
				'display_order' => 8,
				'parent_id' => 5600,
				'name' => 'eyatra-indv-expense-vouchers-verification3',
				'display_name' => 'Expense Vouchers Financier Verfication',
			],
			5610 => [
				'display_order' => 1,
				'parent_id' => 5600,
				'name' => 'eyatra-indv-local-trip-verifications',
				'display_name' => 'Local Trips Verification',
			],
			5611 => [
				'display_order' => 8,
				'parent_id' => 5600,
				'name' => 'eyatra-indv-financier-local-trip-claims-verification',
				'display_name' => 'Financier Local Trip Claim Verification',
			],
			5612 => [
				'display_order' => 8,
				'parent_id' => 5600,
				'name' => 'eYatra-claim-verifier',
				'display_name' => 'Claim Verifier',
			],

			//ROLES
			5620 => [
				'display_order' => 101,
				'parent_id' => 5020,
				'name' => 'eyatra-roles',
				'display_name' => 'Roles',
			],
			5621 => [
				'display_order' => 8,
				'parent_id' => 5620,
				'name' => 'eyatra-role-add',
				'display_name' => 'Add',
			],
			5622 => [
				'display_order' => 8,
				'parent_id' => 5620,
				'name' => 'eyatra-role-edit',
				'display_name' => 'Edit',
			],
			5623 => [
				'display_order' => 8,
				'parent_id' => 5620,
				'name' => 'eyatra-role-delete',
				'display_name' => 'Delete',
			],

			//MASTERS > PETTY CASH EXPENSE TYPES
			5640 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-pettycash-expense-types',
				'display_name' => 'Petty Cash Expense Types',
			],
			5641 => [
				'display_order' => 1,
				'parent_id' => 5640,
				'name' => 'eyatra-pettycash-expense-types-add',
				'display_name' => 'Add',
			],
			5642 => [
				'display_order' => 2,
				'parent_id' => 5640,
				'name' => 'eyatra-pettycash-expense-types-edit',
				'display_name' => 'Edit',
			],
			5643 => [
				'display_order' => 3,
				'parent_id' => 5640,
				'name' => 'eyatra-pettycash-expense-types-delete',
				'display_name' => 'Delete',
			],

			//LOCAL TRIPS
			5660 => [
				'display_order' => 1,
				'parent_id' => 5000,
				'name' => 'local-trips',
				'display_name' => 'Local Trips',
			],
			5661 => [
				'display_order' => 1,
				'parent_id' => 5660,
				'name' => 'local-trip-add',
				'display_name' => 'Add',
			],
			5662 => [
				'display_order' => 2,
				'parent_id' => 5660,
				'name' => 'local-trip-edit',
				'display_name' => 'Edit',
			],
			5663 => [
				'display_order' => 3,
				'parent_id' => 5660,
				'name' => 'local-trip-delete',
				'display_name' => 'Delete',
			],
			5664 => [
				'display_order' => 4,
				'parent_id' => 5660,
				'name' => 'local-view-all-trips',
				'display_name' => 'View All',
			],
			5665 => [
				'display_order' => 4,
				'parent_id' => 5660,
				'name' => 'eyatra-indv-local-trips',
				'display_name' => 'My Local Trips',
			],
			5666 => [
				'display_order' => 4,
				'parent_id' => 5660,
				'name' => 'eyatra-indv-claimed-local-trips',
				'display_name' => 'My Claimed Local Trips',
			],

			//REPORTS
			5680 => [
				'display_order' => 1,
				'parent_id' => 5000,
				'name' => 'eyatra-reports',
				'display_name' => 'Reports',
			],

			//REPORTS > FINANCIER > TRIP ADVANCE REQUEST
			5681 => [
				'display_order' => 1,
				'parent_id' => 5680,
				'name' => 'outstation-trip-advance-report',
				'display_name' => 'Trip Advance Request Report',
			],
			//REPORTS > ADMIN > OUTSTATION TRIP
			5682 => [
				'display_order' => 1,
				'parent_id' => 5680,
				'name' => 'eyatra-outstation-trip-report',
				'display_name' => 'Outstation Trip Report',
			],
			//REPORTS > ADMIN > LOCAL TRIP
			5683 => [
				'display_order' => 1,
				'parent_id' => 5680,
				'name' => 'eyatra-local-trip-report',
				'display_name' => 'Local Trip Report',
			],
			//REPORTS > MANAGER > OUTSTATION TRIP
			5684 => [
				'display_order' => 1,
				'parent_id' => 5680,
				'name' => 'eyatra-outstation-trip-manager-report',
				'display_name' => 'Outstation Trip - Manager Report',
			],
			//REPORTS > MANAGER > LOCAL TRIP
			5685 => [
				'display_order' => 1,
				'parent_id' => 5680,
				'name' => 'eyatra-local-trip-manager-report',
				'display_name' => 'Local Trip - Manager Report',
			],
			//REPORTS > MANAGER > PETTY CASH
			5686 => [
				'display_order' => 1,
				'parent_id' => 5680,
				'name' => 'eyatra-petty-cash-manager-report',
				'display_name' => 'Petty Cash- Manager Report',
			],
			//REPORTS > CASHIER > PETTY CASH
			5687 => [
				'display_order' => 1,
				'parent_id' => 5680,
				'name' => 'eyatra-petty-cash-cashier-report',
				'display_name' => 'Petty Cash - Cashier Report',
			],
			//REPORTS > FINANCIER > PETTY CASH
			5688 => [
				'display_order' => 1,
				'parent_id' => 5680,
				'name' => 'eyatra-petty-cash-financier-report',
				'display_name' => 'Petty Cash - Financier Report',
			],
			//REPORTS > ADMIN > PETTY CASH
			5689 => [
				'display_order' => 1,
				'parent_id' => 5680,
				'name' => 'eyatra-petty-cash-admin-report',
				'display_name' => 'Petty Cash Report',
			],
			//REPORTS > FINANCIER > OUTSTATION TRIP
			5690 => [
				'display_order' => 1,
				'parent_id' => 5680,
				'name' => 'eyatra-outstation-trip-financier-report',
				'display_name' => 'Outstation Trip - Financier Report',
			],
			//REPORTS > FINANCIER > LOCAL TRIP
			5691 => [
				'display_order' => 1,
				'parent_id' => 5680,
				'name' => 'eyatra-local-trip-financier-report',
				'display_name' => 'Local Trip - Financier Report',
			],

			//MASTERS > COMPANY
			5700 => [
				'display_order' => 1,
				'parent_id' => 5020,
				'name' => 'eyatra-company',
				'display_name' => 'Company',
			],
			5701 => [
				'display_order' => 1,
				'parent_id' => 5700,
				'name' => 'eyatra-company-add',
				'display_name' => 'Add',
			],
			5702 => [
				'display_order' => 2,
				'parent_id' => 5700,
				'name' => 'eyatra-company-edit',
				'display_name' => 'Edit',
			],
			5703 => [
				'display_order' => 3,
				'parent_id' => 5700,
				'name' => 'eyatra-company-delete',
				'display_name' => 'Delete',
			],
			5704 => [
				'display_order' => 3,
				'parent_id' => 5700,
				'name' => 'eyatra-all-company-view',
				'display_name' => 'View All Companies',
			],
			// MASTERS -> GST Report
			5705 => [
				'display_order' => 1,
				'parent_id' => 5680,
				'name' => 'eyatra-gst-report',
				'display_name' => 'GST Report',
			],

			5707 => [
				'display_order' => 1,
				'parent_id' => 5680,
				'name' => 'eyatra-employee-gstr-report',
				'display_name' => 'GSTR2 Report',

			// MASTERS -> AGENT Report
			5706 => [
				'display_order' => 1,
				'parent_id' => 5680,
				'name' => 'eyatra-agent-report',
				'display_name' => 'Agent Report',

			],
			5708 => [
				'display_order' => 1,
				'parent_id' => 5680,
				'name' => 'eyatra-report-list',
				'display_name' => 'Report List',
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
