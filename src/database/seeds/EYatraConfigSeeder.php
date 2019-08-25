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
			3264 => [
				'name' => 'Claim',
				'config_type_id' => 517,
			],
			3265 => [
				'name' => 'Cash Topup',
				'config_type_id' => 517,
			],

		];
		foreach ($config_types as $config_type_id => $config_type_name) {
			$config_type = ConfigType::firstOrNew([
				'id' => $config_type_id,
			]);
			$config_type->name = $config_type_name;
			$config_type->save();
		}

		foreach ($configs as $id => $config_data) {
			$config = Config::firstOrNew([
				'id' => $id,
			]);
			$config->fill($config_data);
			$config->save();
		}
	}
}
