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
				'name' => 'Self First Booking',
				'config_type_id' => 505,
			],
			3101 => [
				'name' => 'Self Booking Cancel',
				'config_type_id' => 505,
			],
			3102 => [
				'name' => 'Agent First Booking',
				'config_type_id' => 505,
			],
			3103 => [
				'name' => 'Agent Booking Cancel',
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
				'name' => 'Visit',
				'config_type_id' => 509,
			],

			//ATTACHMENT TYPE - EYATRA
			3200 => [
				'name' => 'Booking Attachments',
				'config_type_id' => 510,
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
