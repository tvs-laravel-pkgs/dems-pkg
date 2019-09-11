<?php

namespace Uitoux\EYatra\Database\Seeds;

use App\Config;
use App\ConfigType;
use Illuminate\Database\Seeder;

class EYatraConfigSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$config_types = [
			500 => 'Expense Types',
			501 => 'Trip Statuses',
			502 => 'Trip Booking Methods',
			503 => 'Visit Booking Statuses',
			504 => 'Manager Verification Statuses',
			505 => 'Visit Booking Type',
			506 => 'User Type - EYatra',
			507 => 'Visit Payment Statuses',
			508 => 'Address of - EYatra',
			509 => 'Address Type - EYatra',
			510 => 'Attachment of - EYatra',
			511 => 'Attachment Type - EYatra',
			512 => 'Visit Statuses - EYatra',
			513 => 'Visit Booking Statuses - EYatra',
			514 => 'Employee Payment Mode - EYatra',
			515 => 'Payment of - EYatra',
			516 => 'Advance Request Approval Statuses - EYatra',
			517 => 'Reimbursement status - EYatra',
			518 => 'Petty Cash Status - EYatra',
			519 => 'Activity Log Entity Types - EYatra',
			520 => 'Activity Log Activities - EYatra',
			521 => 'Lodging Stay',
			522 => 'Agent Payment Mode - EYatra',
			523 => 'Import Type - EYatra',
			524 => 'Import Status - EYatra',
			525 => 'Trave Mode Category Types - EYatra',
			526 => 'Account Type',
			527 => 'Petty Cash Type',
			528 => 'Advance Expense Voucher',
			529 => 'Alternate Approver Type',
		];

		$configs = [
			//EXPENSE TYPES
			3000 => [
				'name' => 'Travel Expenses',
				'config_type_id' => 500,
			],
			3001 => [
				'name' => 'Lodging Expenses',
				'config_type_id' => 500,
			],
			3002 => [
				'name' => 'Boarding Expenses',
				'config_type_id' => 500,
			],
			3003 => [
				'name' => 'Local Travel Expenses',
				'config_type_id' => 500,
			],

			//TRIP STATUSES
			3020 => [
				'name' => 'New',
				'config_type_id' => 501,
			],
			3021 => [
				'name' => 'Manager Approval Pending',
				'config_type_id' => 501,
			],
			3022 => [
				'name' => 'Manager Rejected',
				'config_type_id' => 501,
			],
			3023 => [
				'name' => 'Claimed',
				'config_type_id' => 501,
			],
			3024 => [
				'name' => 'Claim Rejected',
				'config_type_id' => 501,
			],
			3025 => [
				'name' => 'Payment Pending',
				'config_type_id' => 501,
			],
			3026 => [
				'name' => 'Paid',
				'config_type_id' => 501,
			],
			3027 => [
				'name' => 'Resolved',
				'config_type_id' => 501,
			],
			3028 => [
				'name' => 'Manager Approved',
				'config_type_id' => 501,
			],
			3029 => [
				'name' => 'Senior Manager Claim Approval Pending',
				'config_type_id' => 501,
			],

			//TRIP BOOKING METHOD
			3040 => [
				'name' => 'Self',
				'config_type_id' => 502,
			],
			3042 => [
				'name' => 'Agent',
				'config_type_id' => 502,
			],

			//BOOKING STATUSES
			3060 => [
				'name' => 'Pending',
				'config_type_id' => 503,
			],
			3061 => [
				'name' => 'Booked',
				'config_type_id' => 503,
			],
			3062 => [
				'name' => 'Cancelled',
				'config_type_id' => 503,
			],

			//MANAGER VERIFICATION STATUSES
			3080 => [
				'name' => 'Pending',
				'config_type_id' => 504,
			],
			3081 => [
				'name' => 'Approved',
				'config_type_id' => 504,
			],
			3082 => [
				'name' => 'Rejected',
				'config_type_id' => 504,
			],
			3083 => [
				'name' => 'Resolved',
				'config_type_id' => 504,
			],
			3084 => [
				'name' => 'New',
				'config_type_id' => 504,
			],

			//VISIT BOOKING TYPE
			3100 => [
				'name' => 'Fresh Booking',
				'config_type_id' => 505,
			],
			3101 => [
				'name' => 'Booking Cancellation',
				'config_type_id' => 505,
			],

			//USER TYPE
			3120 => [
				'name' => 'Admin',
				'config_type_id' => 506,
			],
			3121 => [
				'name' => 'Employee',
				'config_type_id' => 506,
			],
			3122 => [
				'name' => 'Agent',
				'config_type_id' => 506,
			],

			//VISIT BOOKING PAYMENT STATUSES
			3140 => [
				'name' => 'Not Claimed',
				'config_type_id' => 507,
			],
			3141 => [
				'name' => 'Approved',
				'config_type_id' => 507,
			],
			3142 => [
				'name' => 'Rejected',
				'config_type_id' => 507,
			],
			3143 => [
				'name' => 'Resolved',
				'config_type_id' => 507,
			],
			3144 => [
				'name' => 'Payment Pending',
				'config_type_id' => 507,
			],
			3145 => [
				'name' => 'Paid',
				'config_type_id' => 507,
			],

			//ADDRESS OF - EYATRA
			3160 => [
				'name' => 'Outlet',
				'config_type_id' => 508,
			],
			3161 => [
				'name' => 'Agent',
				'config_type_id' => 508,
			],

			//ATTACHMENT OF - EYATRA
			3180 => [
				'name' => 'Visit Booking',
				'config_type_id' => 510,
			],
			3181 => [
				'name' => 'Lodging Expense',
				'config_type_id' => 510,
			],
			3182 => [
				'name' => 'Boarding Expense',
				'config_type_id' => 510,
			],
			3183 => [
				'name' => 'Local Travel Expense',
				'config_type_id' => 510,
			],
			3184 => [
				'name' => 'Agent Invoice Attachment',
				'config_type_id' => 510,
			],

			//ATTACHMENT TYPE - EYATRA
			3200 => [
				'name' => 'Multi Attachments',
				'config_type_id' => 511,
			],

			//VISIT STATUSES
			3220 => [
				'name' => 'New',
				'config_type_id' => 512,
			],
			3221 => [
				'name' => 'Cancellation Requested',
				'config_type_id' => 512,
			],
			3222 => [
				'name' => 'Claim Requested',
				'config_type_id' => 512,
			],
			3223 => [
				'name' => 'Payment Pending',
				'config_type_id' => 512,
			],
			3224 => [
				'name' => 'Senior Manager Claim Approval Pending',
				'config_type_id' => 512,
			],
			3225 => [
				'name' => 'Paid',
				'config_type_id' => 512,
			],
			3226 => [
				'name' => 'Claim Rejected',
				'config_type_id' => 512,
			],

			//VISIT BOOKING STATUSES
			3240 => [
				'name' => 'Claim Pending',
				'config_type_id' => 513,
			],
			3241 => [
				'name' => 'Claimed',
				'config_type_id' => 513,
			],
			3242 => [
				'name' => 'Payment Pending',
				'config_type_id' => 513,
			],
			3243 => [
				'name' => 'Paid',
				'config_type_id' => 513,
			],

			//EMPLOYEE PAYMENT MODES
			3244 => [
				'name' => 'Bank',
				'config_type_id' => 514,
			],
			3245 => [
				'name' => 'Cheque',
				'config_type_id' => 514,
			],
			3246 => [
				'name' => 'Wallet',
				'config_type_id' => 514,
			],

			//PAYMENT OF
			3250 => [
				'name' => 'Employee Advance Claim',
				'config_type_id' => 515,
			],
			3251 => [
				'name' => 'Employee Expense Claim',
				'config_type_id' => 515,
			],
			3252 => [
				'name' => 'Agent Booking Claim',
				'config_type_id' => 515,
			],
			3253 => [
				'name' => 'Employee Petty Cash Local Conveyance Claim',
				'config_type_id' => 515,
			],
			3254 => [
				'name' => 'Employee Petty Cash Other Expense Claim',
				'config_type_id' => 515,
			],

			//ADVANCE REQUEST APPROVAL STATUSES
			3260 => [
				'name' => 'Requested',
				'config_type_id' => 516,
			],
			3261 => [
				'name' => 'Approved',
				'config_type_id' => 516,
			],
			3262 => [
				'name' => 'Rejected',
				'config_type_id' => 516,
			],

			//REIMBURSEMENT HISTORY STATUS
			3270 => [
				'name' => 'Local Conveyance Claim',
				'config_type_id' => 517,
			],
			3271 => [
				'name' => 'Cash Topup',
				'config_type_id' => 517,
			],
			3272 => [
				'name' => 'Other Expense Claim',
				'config_type_id' => 517,
			],

			//PETTY CASH
			3280 => [
				'name' => 'Manager Approval Pending',
				'config_type_id' => 518,
			],
			3281 => [
				'name' => 'Manager Approved', // PETTY CASH CLAIM GOES TO CASHIER
				'config_type_id' => 518,
			],
			3282 => [
				'name' => 'Manager Rejected',
				'config_type_id' => 518,
			],
			3283 => [
				'name' => 'Paid',
				'config_type_id' => 518,
			],
			3284 => [
				'name' => 'Claim Rejected',
				'config_type_id' => 518,
			],
			3285 => [
				'name' => 'Manager  Approved', // PETTY CASH CLAIM GOES TO FINANCIER
				'config_type_id' => 518,
			],

			//ACTIVITY LOG ENTITY TYPES
			3300 => [
				'name' => 'Trip',
				'config_type_id' => 519,
			],
			3301 => [
				'name' => 'Employee',
				'config_type_id' => 519,
			],
			3302 => [
				'name' => 'Outlet',
				'config_type_id' => 519,
			],
			3303 => [
				'name' => 'Employee Grade',
				'config_type_id' => 519,
			],
			3304 => [
				'name' => 'State',
				'config_type_id' => 519,
			],
			3305 => [
				'name' => 'City',
				'config_type_id' => 519,
			],
			3306 => [
				'name' => 'COA Codes',
				'config_type_id' => 519,
			],
			3307 => [
				'name' => 'Agent',
				'config_type_id' => 519,
			],
			3308 => [
				'name' => 'Travel Modes',
				'config_type_id' => 519,
			],
			3309 => [
				'name' => 'Local Travel Modes',
				'config_type_id' => 519,
			],
			3310 => [
				'name' => 'City Category',
				'config_type_id' => 519,
			],
			3311 => [
				'name' => 'Employee Designations',
				'config_type_id' => 519,
			],
			3312 => [
				'name' => 'Regions',
				'config_type_id' => 519,
			],
			3313 => [
				'name' => 'Agent Claims',
				'config_type_id' => 519,
			],
			3314 => [
				'name' => 'Rejection Reasons',
				'config_type_id' => 519,
			],

			3315 => [
				'name' => 'Trip Purposes',
				'config_type_id' => 519,
			],
			3316 => [
				'name' => 'COA Sub Groups',
				'config_type_id' => 519,
			],

			3317 => [
				'name' => 'Local Conveyance',
				'config_type_id' => 519,
			],

			3318 => [
				'name' => 'Other Expenses',
				'config_type_id' => 519,
			],
			3319 => [
				'name' => 'Visit',
				'config_type_id' => 519,
			],

			//ACTIVITIES
			3320 => [
				'name' => 'Add',
				'config_type_id' => 520,
			],
			3321 => [
				'name' => 'Edit',
				'config_type_id' => 520,
			],
			3322 => [
				'name' => 'Delete',
				'config_type_id' => 520,
			],
			3323 => [
				'name' => 'Approve',
				'config_type_id' => 520,
			],
			3324 => [
				'name' => 'Reject',
				'config_type_id' => 520,
			],
			3325 => [
				'name' => 'Book',
				'config_type_id' => 520,
			],
			3326 => [
				'name' => 'Cancel',
				'config_type_id' => 520,
			],
			3327 => [
				'name' => 'Claim',
				'config_type_id' => 520,
			],
			3328 => [
				'name' => 'Paid',
				'config_type_id' => 520,
			],

			//AGENT PAYMENT MODES
			3229 => [
				'name' => 'DD',
				'config_type_id' => 522,
			],
			3230 => [
				'name' => 'NEFT',
				'config_type_id' => 522,
			],
			3231 => [
				'name' => 'RTGS',
				'config_type_id' => 522,
			],
			3232 => [
				'name' => 'IMPS',
				'config_type_id' => 522,
			],

			//LODGING STAY
			3340 => [
				'name' => 'Normal',
				'config_type_id' => 521,
			],
			3341 => [
				'name' => 'Home',
				'config_type_id' => 521,
			],

			//IMPORT STATUS
			3361 => [
				'name' => 'Pending',
				'config_type_id' => 524,
			],
			3362 => [
				'name' => 'Calculating Total Records',
				'config_type_id' => 524,
			],
			3363 => [
				'name' => 'Inprogress',
				'config_type_id' => 524,
			],
			3364 => [
				'name' => 'Completed',
				'config_type_id' => 524,
			],
			3365 => [
				'name' => 'Cancelled',
				'config_type_id' => 524,
			],
			3366 => [
				'name' => 'Server Error',
				'config_type_id' => 524,
			],
			3367 => [
				'name' => 'Error',
				'config_type_id' => 524,
			],

			//IMPORT TYPE
			3380 => [
				'name' => 'Employee',
				'config_type_id' => 523,
			],
			3381 => [
				'name' => 'Outlet',
				'config_type_id' => 523,
			],

			//TRAVEL MODE CATEGORY TYPE
			3400 => [
				'name' => 'Own Two Wheeler',
				'config_type_id' => 525,
			],
			3401 => [
				'name' => 'Own Four Wheeler',
				'config_type_id' => 525,
			],
			3402 => [
				'name' => 'Vehicle No Claim',
				'config_type_id' => 525,
			],
			3403 => [
				'name' => 'Claim',
				'config_type_id' => 525,
			],

			//ACCOUNT TYPE
			3420 => [
				'name' => 'Savings',
				'config_type_id' => 526,
			],
			3421 => [
				'name' => 'Current',
				'config_type_id' => 526,
			],

			//PETTY CASH TYPE
			3440 => [
				'name' => 'Local Conveyance',
				'config_type_id' => 527,
			],
			3441 => [
				'name' => 'Other Expense',
				'config_type_id' => 527,
			],
			3442 => [
				'name' => 'Advance Expense',
				'config_type_id' => 527,
			],

			//ADVANCE EXPENSE VOUCHER
			3460 => [
				'name' => 'New',
				'config_type_id' => 528,
			],
			3461 => [
				'name' => 'Claim Approved',
				'config_type_id' => 528,
			],
			3462 => [
				'name' => 'Claim Rejected',
				'config_type_id' => 528,
			],

			//ALTERNATE APPROVER TYPE
			3460 => [
				'name' => 'Temporary',
				'config_type_id' => 529,
			],
			3461 => [
				'name' => 'Permanent',
				'config_type_id' => 529,
			],
		];

		//SAVING CONFIG TYPES
		foreach ($config_types as $config_type_id => $config_type_name) {
			$config_type = ConfigType::firstOrNew([
				'id' => $config_type_id,
			]);
			$config_type->name = $config_type_name;
			$config_type->save();
		}

		//SAVING CONFIGS
		foreach ($configs as $id => $config_data) {
			$config = Config::firstOrNew([
				'id' => $id,
			]);
			$config->fill($config_data);
			$config->save();
		}
	}
}
