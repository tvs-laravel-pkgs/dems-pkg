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
			530 => 'Agent Claim Status',
			531 => 'Local Trip Statuses',
			532 => 'Local Trave Mode Category Types - EYatra',
			533 => 'Trip / Expense Types',
			534 => 'Trip / Expense Approval Types',
			535 => 'Claim Statuses',
			536 => 'Financial Years',
			537 => 'Serial Number Segment Types',
			538 => 'Report Types',
			539 => 'Mail Config Types',
			540 => 'Boarding Types',
			541 => 'Outstation Trip Attachments',

			545 => 'Booking Categories',
			546 => 'Lodging Tax Invoice Types ',

			547 => 'Axapta Export Types',
			548 => 'Trip Mode',
			549 => 'Lodge Sharing Types',
			550 => 'Oracle Transaction Type',
			551 => 'Company Business Unit',
			552 => 'Trip Advance - Pre Payment Invoice Natural Account',
			553 => 'Claim - Invoice Expenses Natural Account',
			554 => 'Oracle Other Transaction Invoice Types',
			555 => 'Oracle Other Transaction Types',
			556 => 'Lodging HSN Code',
			557 => 'Advance Balance - Employee To Company Natural Account',
			558 => 'HRMS To Travelex Employee Sync Types',
			559 => 'Enable Agent Booking Preference In Trip',
			560 => 'Trip Self Booking Approval Must',
			561 => 'HRMS To Travelex New Employee Default Role',
			562 => 'Enable Ticket Booking Request Email To Agent',
			563 => 'Is Fare Doc Required In Claims For Visit',
			564 => 'On Claim Visit Proof Upload Value',
			565 => 'On Trip Cancel Agent Notification Is Required',
			566 => 'On Visit Cancel Agent Notification Is Required',
			567 => 'Enable Ticket Cancell SMS To Agent',
			568 => 'HRMS To Travelex Employee Sync Categories',
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

			//Don't Enable - Parthi (If enable this config, it will be displayed in grade form. so i need to hide this config.Client dont want in grade form )
			// 3003 => [
			// 	'name' => 'Local Travel Expenses',
			// 	'config_type_id' => 500,
			// ],

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
			3027 => [
				'name' => 'Resolved',
				'config_type_id' => 501,
			],
			3028 => [
				'name' => 'Manager Approved',
				'config_type_id' => 501,
			],
			3032 => [
				'name' => 'Cancelled',
				'config_type_id' => 501,
			],
			3038 => [
				'name' => 'Trip Auto Cancel',
				'config_type_id' => 501,
			],

			//CLAIM STATUS
			3023 => [
				'name' => 'Claim Pending',
				'config_type_id' => 535,
			],
			3024 => [
				'name' => 'Claim Rejected',
				'config_type_id' => 535,
			],
			3025 => [
				'name' => 'Payment Pending from Financier',
				'config_type_id' => 535,
			],
			3026 => [
				'name' => 'Completed',
				'config_type_id' => 535,
			],
			3027 => [
				'name' => 'Resolved',
				'config_type_id' => 535,
			],
			3029 => [
				'name' => 'Deviation Approval Pending',
				'config_type_id' => 535,
			],
			3030 => [
				'name' => 'Financier Payment Hold',
				'config_type_id' => 535,
			],
			3031 => [
				'name' => 'Payment Pending from Employee',
				'config_type_id' => 535,
			],
			3033 => [
				'name' => 'Claim Inprogress',
				'config_type_id' => 535,
			],
			3034 => [
				'name' => 'Payment Pending',
				'config_type_id' => 535,
			],
			3035 => [
				'name' => 'Claim Approved',
				'config_type_id' => 535,
			],
			3036 => [
				/*'name' => 'Claim Verification Pending',*/
				'name' => 'Nodel Approval Pending',
				'config_type_id' => 535,
			],
			3037 => [
				'name' => 'Financier Approved',
				'config_type_id' => 535,
			],
			3039 => [
				'name' => 'Claim Auto Cancel',
				'config_type_id' => 535,
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
			3063 => [
				'name' => 'Tatkal',
				'config_type_id' => 503,
			],
			3064 => [
				'name' => 'Visit Rescheduled',
				'config_type_id' => 503,
			],
			3065 => [
				'name' => 'Ticket Uploaded',
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
				'name' => 'Booking',
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
			3185 => [
				'name' => 'Google Attachment',
				'config_type_id' => 510,
			],
			3186 => [
				'name' => 'Local Trip Travel Expense',
				'config_type_id' => 510,
			],
			3187 => [
				'name' => 'Local Trip Google Attachment',
				'config_type_id' => 510,
			],
			3188 => [
				'name' => 'Local Trip Other Expense',
				'config_type_id' => 510,
			],
			3189 => [
				'name' => 'Transport Expense',
				'config_type_id' => 510,
			],

			//ATTACHMENT TYPE - EYATRA
			3200 => [
				'name' => 'Multi Attachments',
				'config_type_id' => 511,
			],

			//VISIT STATUSES
			3220 => [
				'name' => 'New Request',
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
			3227 => [
				'name' => 'Financier Payment Hold',
				'config_type_id' => 512,
			],
			3228 => [
				'name' => 'Claim Inprogress',
				'config_type_id' => 512,
			],
			3229 => [
				'name' => 'Visit Rescheduled',
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
			3255 => [
				'name' => 'Local Trip Expense Claim',
				'config_type_id' => 515,
			],
			3256 => [
				'name' => 'Employee Petty Cash Advance Expense Request',
				'config_type_id' => 515,
			],
			3257 => [
				'name' => 'Employee Petty Cash Advance Expense Claim',
				'config_type_id' => 515,
			],
			3258 => [
				'name' => 'Employee Petty Cash Employee Advance Re-paid',
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
			3263 => [
				'name' => 'Export',
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
			3273 => [
				'name' => 'Employee Advance Expense Request',
				'config_type_id' => 517,
			],
			3274 => [
				'name' => 'Employee Advance Expense Claim',
				'config_type_id' => 517,
			],
			3275 => [
				'name' => 'Employee Return balance Expense Amount',
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

			//ACTIVITY LOG ENTITY TYPES - I
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
				'name' => 'Other Expense',
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
			3233 => [
				'name' => 'DD',
				'config_type_id' => 522,
			],

			//LODGING STAY
			3340 => [
				'name' => 'Lodge Stay',
				'config_type_id' => 521,
			],
			3341 => [
				'name' => 'Flat Claim',
				'config_type_id' => 521,
			],
			3342 => [
				'name' => 'Office Guest House',
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
			3382 => [
				'name' => 'City',
				'config_type_id' => 523,
			],
			3383 => [
				'name' => 'Grade',
				'config_type_id' => 523,
			],
			3384 => [
				'name' => 'Emp Delete',
				'config_type_id' => 523,
			],

			//TRAVEL MODE CATEGORY TYPE
			3400 => [
				'name' => 'Own Vehicle',
				'config_type_id' => 525,
			],
			// 3401 => [
			// 	'name' => 'Own Vehicle',
			// 	'config_type_id' => 525,
			// ],
			3402 => [
				'name' => 'Not Claimable',
				'config_type_id' => 525,
			],
			3403 => [
				'name' => 'Claimable',
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
			//CHANEGED AS LOCAL CONVEYANCE AS VEHICLE CONVEYANCE AND OTHER EXPENSES AS PCV EXPENSE ON 28-02-2022
			3440 => [
				'name' => 'Vehicle Conveyance',
				'config_type_id' => 527,
			],
			3441 => [
				'name' => 'PCV Expense',
				'config_type_id' => 527,
			],
			3442 => [
				'name' => 'Advance Expense',
				'config_type_id' => 527,
			],

			//ADVANCE EXPENSE VOUCHER
			//ADVANCE REQUEST
			3460 => [
				'name' => 'Waiting for Manager Approval',
				'config_type_id' => 528,
			],
			3461 => [
				'name' => 'Advance Payment pending from Cashier', //ADVANCE APPROVE ID GOES TO CASHIER
				'config_type_id' => 528,
			],
			3462 => [
				'name' => 'Advance Payment pending from Financier', //ADVANCE APPROVE ID GOES TO FINACIER
				'config_type_id' => 528,
			],
			3463 => [
				'name' => 'Manager Rejected',
				'config_type_id' => 528,
			],
			3464 => [
				'name' => 'Advance Amount Approved',
				'config_type_id' => 528,
			],
			3465 => [
				'name' => 'Advance Amount Rejected',
				'config_type_id' => 528,
			],
			//EXPENSE
			3466 => [
				'name' => 'Manager Approval Pending',
				'config_type_id' => 528,
			],
			3467 => [
				'name' => 'Waiting for Cashier Approval', //EXPENSE APPROVE ID GOES TO CASHIER
				'config_type_id' => 528,
			],
			3468 => [
				'name' => 'Waiting for Financier Approval', //EXPENSE APPROVE ID GOES TO FINANCIER
				'config_type_id' => 528,
			],
			3469 => [
				'name' => 'Expense Manager Rejected',
				'config_type_id' => 528,
			],
			3470 => [
				'name' => 'Paid',
				'config_type_id' => 528,
			],
			3471 => [
				'name' => 'Expense Claim Rejected',
				'config_type_id' => 528,
			],
			3472 => [
				'name' => 'Payment Pending from Employee',
				'config_type_id' => 528,
			],
			3473 => [
				'name' => 'Employee Return balance Expense Amount',
				'config_type_id' => 528,
			],

			//ALTERNATE APPROVER TYPE
			3480 => [
				'name' => 'Temporary',
				'config_type_id' => 529,
			],
			3481 => [
				'name' => 'Permanent',
				'config_type_id' => 529,
			],

			//ACTIVITY LOG ENTITY TYPES - II
			3500 => [
				'name' => 'Expense types',
				'config_type_id' => 519,
			],
			3500 => [
				'name' => 'Expense types',
				'config_type_id' => 519,
			],
			3501 => [
				'name' => 'Advance Expense',
				'config_type_id' => 519,
			],

			//AGENT CLAIM STATUS
			3520 => [
				'name' => 'Claim Requested',
				'config_type_id' => 530,
			],
			3521 => [
				'name' => 'Paid',
				'config_type_id' => 530,
			],
			3522 => [
				'name' => 'Claim Rejected',
				'config_type_id' => 530,
			],

			// //LOCAL TRIP STATUS
			// 3540 => [
			// 	'name' => 'Approval Pending',
			// 	'config_type_id' => 531,
			// ],
			// 3541 => [
			// 	'name' => 'Manager Rejected',
			// 	'config_type_id' => 531,
			// ],
			// 3542 => [
			// 	'name' => 'Manager Approved',
			// 	'config_type_id' => 531,
			// ],
			// 3543 => [
			// 	'name' => 'Trip Claim Requested',
			// 	'config_type_id' => 531,
			// ],
			// 3544 => [
			// 	'name' => 'Claim Approved by Manager',
			// 	'config_type_id' => 531,
			// ],
			// 3545 => [
			// 	'name' => 'Claim Rejected by Manager',
			// 	'config_type_id' => 531,
			// ],
			// 3546 => [
			// 	'name' => 'Financier Hold',
			// 	'config_type_id' => 531,
			// ],
			// 3547 => [
			// 	'name' => 'Financier Rejected',
			// 	'config_type_id' => 531,
			// ],
			// 3548 => [
			// 	'name' => 'Paid',
			// 	'config_type_id' => 531,
			// ],

			//TRAVEL MODE CATEGORY TYPE
			3560 => [
				'name' => 'Other Amount Not Eligible',
				'config_type_id' => 532,
			],
			3561 => [
				'name' => 'Other Amount Eligible',
				'config_type_id' => 532,
			],

			//TRIP / EXPENSE TYPES
			3581 => [
				'name' => 'Outstation Trip',
				'config_type_id' => 533,
			],
			3582 => [
				'name' => 'Local Trip',
				'config_type_id' => 533,
			],
			3583 => [
				'name' => 'Local Conveyance',
				'config_type_id' => 533,
			],
			3584 => [
				'name' => 'Other Expenses',
				'config_type_id' => 533,
			],
			3585 => [
				'name' => 'Advance Expenses',
				'config_type_id' => 533,
			],

			//TRIP / EXPENSE APPROVAL TYPES
			3600 => [
				'name' => 'Outstation Trip - Manager Approved',
				'config_type_id' => 534,
			],
			3601 => [
				'name' => 'Outstation Trip Claim - Manager Approved',
				'config_type_id' => 534,
			],
			3602 => [
				'name' => 'Outstation Trip Claim - Sr Manager Approved',
				'config_type_id' => 534,
			],
			3603 => [
				'name' => 'Outstation Trip Claim - Financier Approved',
				'config_type_id' => 534,
			],
			3604 => [
				'name' => 'Outstation Trip Claim - Financier Paid',
				'config_type_id' => 534,
			],
			3605 => [
				'name' => 'Outstation Trip Claim - Employee Paid',
				'config_type_id' => 534,
			],
			3606 => [
				'name' => 'Local Trip - Manager Approved',
				'config_type_id' => 534,
			],
			3607 => [
				'name' => 'Local Trip Claim - Manager Approved',
				'config_type_id' => 534,
			],
			3608 => [
				'name' => 'Local Trip Claim - Financier Approved',
				'config_type_id' => 534,
			],
			3609 => [
				'name' => 'Local Conveyance - Manager Approved',
				'config_type_id' => 534,
			],
			3610 => [
				'name' => 'Local Conveyance - Cashier Approved',
				'config_type_id' => 534,
			],
			3611 => [
				'name' => 'Local Conveyance - Financier Approved',
				'config_type_id' => 534,
			],
			3612 => [
				'name' => 'Other Expenses - Manager Approved',
				'config_type_id' => 534,
			],
			3613 => [
				'name' => 'Other Expenses - Financier Approved',
				'config_type_id' => 534,
			],
			3614 => [
				'name' => 'Advance Expenses Request - Manager Approved',
				'config_type_id' => 534,
			],
			3615 => [
				'name' => 'Advance Expenses Request - Cashier Approved',
				'config_type_id' => 534,
			],
			3616 => [
				'name' => 'Advance Expenses Request - Financier Approved',
				'config_type_id' => 534,
			],
			3617 => [
				'name' => 'Advance Expenses Claim - Manager Approved',
				'config_type_id' => 534,
			],
			3618 => [
				'name' => 'Advance Expenses Claim - Cashier Approved',
				'config_type_id' => 534,
			],
			3619 => [
				'name' => 'Advance Expenses Claim - Financier Approved',
				'config_type_id' => 534,
			],
			3620 => [
				'name' => 'Trip Advance Request - Financier Approved',
				'config_type_id' => 534,
			],
			3621 => [
				'name' => 'Other Expenses - Cashier Approved',
				'config_type_id' => 534,
			],
			3622 => [
				'name' => 'Outstation Trip Claim - Verifier Approved',
				'config_type_id' => 534,
			],
			3623 => [
				'name' => 'Local Trip Claim - Verifier Approved',
				'config_type_id' => 534,
			],

			//Financial Years
			3700 => [
				'name' => 'FY-19',
				'config_type_id' => 536,
			],
			3701 => [
				'name' => 'FY-20',
				'config_type_id' => 536,
			],
			3702 => [
				'name' => 'FY-21',
				'config_type_id' => 536,
			],
			3703 => [
				'name' => 'FY-22',
				'config_type_id' => 536,
			],
			3704 => [
				'name' => 'FY-23',
				'config_type_id' => 536,
			],
			// Serial Number Segment Types
			3711 => [
				'name' => 'Branch Code',
				'config_type_id' => 537,
			],
			3712 => [
				'name' => 'FY Code',
				'config_type_id' => 537,
			],
			3713 => [
				'name' => 'Static Text',
				'config_type_id' => 537,
			],
			// Report Types
			3721 => [
				'name' => 'Bank Statement',
				'config_type_id' => 538,
			],
			3722 => [
				'name' => 'Travel X To Ax',
				'config_type_id' => 538,
			],
			// Mail types
			3731 => [
				'name' => 'Bank and Traven x report mail',
				'config_type_id' => 539,
			],
			//Boarding Type
			3741 => [
				'name' => 'Normal',
				'config_type_id' => 540,
			],
			3742 => [
				'name' => 'Sponsor',
				'config_type_id' => 540,
			],
			3743 => [
				'name' => 'Leave',
				'config_type_id' => 540,
			],
			3750 => [
				'name' => 'All',
				'config_type_id' => 541,
			],
			3751 => [
				'name' => 'Fare Detail(Agent Ticket)',
				'config_type_id' => 541,
			],
			3752 => [
				'name' => 'Lodging',
				'config_type_id' => 541,
			],
			3753 => [
				'name' => 'Boarding',
				'config_type_id' => 541,
			],
			3754 => [
				'name' => 'Others',
				'config_type_id' => 541,
			],
			3755 => [
				'name' => 'Self Booking Attachments',
				'config_type_id' => 541,
			],
			3756 => [
				'name' => 'Guest House Approval',
				'config_type_id' => 541,
			],

			// Booking Categories
			3760 => [
				'name' => 'Agent',
				'config_type_id' => 545,
			],
			3761 => [
				'name' => 'User',
				'config_type_id' => 545,
			],

			// Lodging Tax Invoice Types
			3771 => [
				'name' => 'Lodging',
				'hsn_code' => 996311,
				'config_type_id' => 546,
			],
			3772 => [
				'name' => 'Drywash',
				'hsn_code' => 999719,
				'config_type_id' => 546,
			],
			3773 => [
				'name' => 'Boarding',
				'hsn_code' => 996332,
				'config_type_id' => 546,
			],
			3774 => [
				'name' => 'Others',
				'hsn_code' => 999719,
				'config_type_id' => 546,
			],
			3775 => [
				'name' => 'Roundoff',
				'config_type_id' => 546,
			],
			3776 => [
				'name' => 'Discount',
				'config_type_id' => 546,
			],

			// Axapta Export Types
			3790 => [
				'name' => 'Agent',
				'config_type_id' => 547,
			],
			3791 => [
				'name' => 'Self',
				'config_type_id' => 547,
			],

			// trip mode
			3792 => [
				'name' => 'Short Distance',
				'config_type_id' => 548,
			],
			3793 => [
				'name' => 'Overnight',
				'config_type_id' => 548,
			],

			//LODGE SHARE TYPE
			3810 => [
				'name' => 'Single',
				'config_type_id' => 549,
			],
			3811 => [
				'name' => 'Yes',
				'config_type_id' => 549,
			],
			3812 => [
				'name' => 'No',
				'config_type_id' => 549,
			],

			3831 => [
				'name' => 'Travelx Pre Payment Invoice',
				'config_type_id' => 550,
			],
			3832 => [
				'name' => 'Travelx Invoice',
				'config_type_id' => 550,
			],

			//COMPANY BUSINESS UNITS
			3841 => [
				'name' => 'OEM',
				'config_type_id' => 551,
			],
			3842 => [
				'name' => 'OES',
				'config_type_id' => 551,
			],
			3843 => [
				'name' => 'BPCL',
				'config_type_id' => 551,
			],

			//ORACLE NATURAL ACCOUNT
			3860 => [
				'name' => '152668',
				'config_type_id' => 552,
			],
			3861 => [
				'name' => '570222',
				'config_type_id' => 553,
			],

			3881 => [
				'name' => 'AR Invoice',
				'config_type_id' => 554,
			],
			3882 => [
				'name' => 'AP Invoice',
				'config_type_id' => 554,
			],

			3891 => [
				'name' => 'Frieght Charges',
				'config_type_id' => 555,
			],
			3892 => [
				'name' => 'Roundoff',
				'config_type_id' => 555,
			],

			3901 => [
				'name' => '996311',
				'config_type_id' => 556,
			],

			3921 => [
				'name' => '152674',
				'config_type_id' => 557,
			],

			3941 => [
				'name' => 'HRMS To Travelex Employee Addition Mail',
				'config_type_id' => 539,
			],
			3942 => [
				'name' => 'HRMS To Travelex Employee Detail Change Mail',
				'config_type_id' => 539,
			],
			3943 => [
				'name' => 'HRMS To Travelex Employee Deletion Mail',
				'config_type_id' => 539,
			],
			3944 => [
				'name' => 'HRMS To Travelex Employee Reporting To Change Mail',
				'config_type_id' => 539,
			],
			3945 => [
				'name' => 'HRMS To Travelex Employee Manual Addition Mail',
				'config_type_id' => 539,
			],

			3961 => [
				'name' => 'Employee Addition',
				'config_type_id' => 558,
			],
			3962 => [
				'name' => 'Employee Updation',
				'config_type_id' => 558,
			],
			3963 => [
				'name' => 'Employee Deletion',
				'config_type_id' => 558,
			],
			3964 => [
				'name' => 'Employee Reporting To Updation',
				'config_type_id' => 558,
			],
			3965 => [
				'name' => 'Employee Manual Addition',
				'config_type_id' => 558,
			],

			3971 => [
				'name' => 'No',
				'config_type_id' => 559,
			],
			3972 => [
				'name' => 'No',
				'config_type_id' => 560,
			],

			3975 => [
				'name' => 'Employee',
				'config_type_id' => 561,
			],

			3981 => [
				'name' => 'No',
				'config_type_id' => 562,
			],
			3982 => [
				'name' => 'Yes',
				'config_type_id' => 563,
			],
			3983 => [
				'name' => 'Yes',
				'config_type_id' => 564,
			],
			3984 => [
				'name' => 'No',
				'config_type_id' => 565,
			],
			3985 => [
				'name' => 'No',
				'config_type_id' => 566,
			],
			3986 => [
				'name' => 'No',
				'config_type_id' => 567,
			],

			3990 => [
				'name' => 'New Addition',
				'config_type_id' => 568,
			],
			3991 => [
				'name' => 'Deletion',
				'config_type_id' => 568,
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
