<?php

namespace Uitoux\EYatra\Database\Seeds;

use App\Lob;
use App\User;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Uitoux\EYatra\Address;
use Uitoux\EYatra\Agent;
use Uitoux\EYatra\AgentClaim;
use Uitoux\EYatra\Company;
use Uitoux\EYatra\Config;
use Uitoux\EYatra\Designation;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\Entity;
use Uitoux\EYatra\GradeAdvancedEligiblity;
use Uitoux\EYatra\NCity;
use Uitoux\EYatra\NCountry;
use Uitoux\EYatra\NState;
use Uitoux\EYatra\Outlet;
use Uitoux\EYatra\Trip;
use Uitoux\EYatra\Visit;
use Uitoux\EYatra\VisitBooking;

class EYatraTC1Seeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$faker = Faker::create();

		$company_id = $this->command->ask("Enter company id", '4');
		$delete_company = $this->command->ask("Do you want to delete company", 'n');
		if ($delete_company == 'y') {
			$company = Company::find($company_id);
			$company->forceDelete();
		}

		$number_of_items = $this->command->ask("How many records do you want to create?", '1');
		$create_dummy_records = $this->command->ask("Do you want to create dummy records", 'n');

		$base_telephone_number = $company_id;
		$company = Company::firstOrNew([
			'id' => $company_id,
		]);
		$com_data['data']['code'] = 'com' . $company_id;
		$com_data['data']['name'] = 'Company ' . $company_id;
		$com_data['data']['address'] = $faker->streetAddress;
		$com_data['data']['cin_number'] = 'CIN' . $company_id;
		$com_data['data']['gst_number'] = 'GST' . $company_id;
		$com_data['data']['customer_care_email'] = 'customercare@com' . $company_id . '.in';
		$com_data['data']['customer_care_phone'] = $base_telephone_number . '000000000';
		$com_data['data']['reference_code'] = 'com' . $company_id;
		$company->fill($com_data['data']);
		$company->save();

		// $company->users()->forceDelete();
		//ADMIN USERS
		$admin = User::firstOrNew([
			'company_id' => $company->id,
			'username' => 'c' . $company->id . '/a1',
		]);
		$admin->user_type_id = 3120;
		$admin->mobile_number = $base_telephone_number . '100000000';
		$admin->name = $company->name . ' / Admin 1';
		$admin->password = 'Test@123';
		$admin->save();

		$this->call(EYatraSeeder::class);

		$admin->roles()->sync(500);
		$country = NCountry::find(5);
		if ($country) {
			// $country->delete();
		}

		// $los = [
		// 	'lob1' => [
		// 		'sbus' => [
		// 			'sbu1'
		// 		],
		// 	],
		// 	'lob2',
		// 	'lob1',
		// 	'lob1',
		// ]

		Lob::create($company, $admin);

		$countries = [
			5 => [
				'data' => [
					'name' => 'India',
					'code' => 'IN',
				],
				'states' => [
					'TN' => [
						'data' => [
							'name' => 'Tamilnadu',
						],
						'regions' => [
							'TN1' => 'Tamilnadu 1',
							'TN2' => 'Tamilnadu 2',
						],
						'cities' => [
							'Coimbatore',
							'Madurai',
							'Chennai',
							'Salem',
						],
					],
					'KL' => [
						'data' => [
							'name' => 'Kerala',
						],
						'regions' => [
							'KL1' => 'Kerala 1',
							'KL2' => 'Kerala 2',
						],
						'cities' => [
							'Palakkad',
							'Kollam',
							'Ernakulam',
						],
					],
					'KA' => [
						'data' => [
							'name' => 'Karnataka',
						],
						'regions' => [
							'KA1' => 'Karnataka 1',
							'KA2' => 'Karnataka 2',
						],
						'cities' => [
							'Bangalore',
							'Mysore',
						],
					],

				],
			],
		];

		NCountry::create($countries, $admin, $company);
		//NCountry::createDummies($admin);
		//DUMMY ENTITY CREATION
		$dummy_entities = [
		];
		foreach ($dummy_entities as $entity_type_id => $name) {
			for ($i = 1; $i <= 15; $i++) {
				$record = Entity::firstOrCreate([
					'entity_type_id' => $entity_type_id,
					'company_id' => $company->id,
					'name' => $company->code . '/' . $name . $i,
					'created_by' => $admin->id,
				]);
			}
		}

		$sample_entities = [
			500 => [
				'L1',
				'L2',
				'M1',
				'M2',
				'O1',
				'O2',
			],
			501 => [
				'Client Meeting',
				'Project Delivery',
				'Vehicle Service',
				'Requirement Discussion',
				'Project Demo',
			],
			502 => [
				'Bus',
				'Train',
				'Air',
				'Two Wheeler',
				'Four Wheeler',
				'Office Vehicle',
			],
			503 => [
				'A/C Taxi',
				'Non A/C Taxi',
				'Auto Ricksaw',
				'Bus',
				'Toll Fee',
			],
			504 => [
				'Normal',
				'Home',
			],
			505 => [
				'Paytm',
				'Phone Pay',
				'Google Pay',
				'BHIM UPI',
			],
			506 => [
				'A',
				'B',
				'C',
				'D',
			],
			507 => [
				'Trip Postponed',
				'Date Irrelevant',
				'Trip Details Mismatch',
				'Not Eligible',
			],
			508 => [
				'Amount Limit Exceed',
				'Not Eligible',
				'Budget not allocated',
			],
			509 => [
				'Amount Limit Exceed',
			],
			510 => [
				'Amount Limit Exceed',
				'Budget not allocated',
			],
			511 => [
				'Amount Limit Exceed',
				'Budget not allocated',
			],
			512 => [
				'Pooja',
				'Entertainment',
				'Local Conveyance',
			],
			513 => [
				'Assets',
				'Liabilites',
				'Equities',
			],
			514 => [
				'Debit',
				'Credit',
			],
			515 => [
				'Balance Sheet',
			],
			516 => [
				'Test Group',
				'Current Assets',
			],
			517 => [
				'Inventory',
				'Test Sub Group',
			],
			518 => [
				'General Ticket',
				'Tatkal',
				'Premimum Tatkal',
			],

		];

		Entity::create($sample_entities, $admin, $company);
		Designation::create($company, $admin);
		foreach ($company->designations as $designation) {
			$grade = $company->employeeGrades()->inRandomOrder()->first();
			$designation->grade_id = $grade->id;
			$designation->save();
		}

		foreach (NCity::get() as $city) {
			$city->category_id = $company->cityCategories()->inRandomOrder()->first()->id;
			$city->save();
		}

		$this->command->info('------------------');
		$this->command->info('Creating Outlets > Managers > Employees');

		//OUTLETS
		for ($i = 1; $i <= $number_of_items; $i++) {
			$outlet = Outlet::firstOrNew([
				'company_id' => $company_id,
				'code' => 'c' . $company_id . '/o' . $i,
			]);
			$outlet->name = 'Company ' . $company->id . ' / Outlet ' . $i;

			$outlet->amount_eligible = $faker->randomElement([0, 1]);
			$outlet->reimbursement_amount = $outlet->amount_eligible == 1 ? $faker->randomElement([10000, 20000, 30000]) : 0;
			$outlet->amount_limit = $outlet->reimbursement_amount / 5;
			$outlet->created_by = $admin->id;
			$outlet->save();

			//OUTLET ADDRESS
			$address_of_id = 3160;
			$address = Address::create($address_of_id, $outlet, $faker, $company->id);
			dump($outlet, $address);

			$this->command->info('------------------');
			$this->command->info('Outlet Created : ' . $outlet->name);
			// dd($address);

			//OUTLET CASHIER CREATION
			$code = $outlet->code . '/cash' . $i;
			$cashier = Employee::create($company, $code, $outlet, $admin, $faker);
			$this->command->info('------------------');
			$this->command->info('Cashier Created : ' . $cashier->code);

			$user_type_id = 3121;
			$cashier_user = Employee::createUser($company, $user_type_id, $cashier, $faker, $base_telephone_number . '5' . $i . '10000000', $roles = 504);
			$outlet->cashier_id = $cashier->id;
			$outlet->save();

			//Sr.MANAGER
			$code = $outlet->code . '/sm' . $i;
			$sr_manager = Employee::create($company, $code, $outlet, $admin, $faker);
			$this->command->info('------------------');
			$this->command->info('Sr.Manager Created : ' . $sr_manager->code);

			//USER ACCOUNT
			$user_type_id = 3121;
			$user = Employee::createUser($company, $user_type_id, $sr_manager, $faker, $base_telephone_number . $i . '20000000', $roles = 502);

			//MANAGERS
			for ($j = 1; $j <= $number_of_items; $j++) {
				dump($j, $number_of_items);

				//MANAGERS
				$code = $sr_manager->code . '/mngr' . $j;
				$manager = Employee::create($company, $code, $outlet, $admin, $faker, $sr_manager->id);
				$this->command->info('------------------');
				$this->command->info('Manager Created : ' . $manager->code);

				//USER ACCOUNT
				$user_type_id = 3121;
				$user = Employee::createUser($company, $user_type_id, $manager, $faker, $base_telephone_number . $i . $j . '0000000', $roles = 502);

				//EMPLOYEES - REGULAR
				for ($k = 1; $k <= $number_of_items; $k++) {
					$code = $manager->code . '/e' . $k;
					$employee = Employee::create($company, $code, $outlet, $admin, $faker, $manager->id);
					$this->command->info('------------------');
					$this->command->info('Employee Created : ' . $employee->code);

					$user_type_id = 3121;
					$user = Employee::createUser($company, $user_type_id, $employee, $faker, $base_telephone_number . $i . $j . $k . '000000', $roles = 501);
				}
			}
		}

		// $this->command->info('');
		// $this->command->info('Outlet Cashier Mapping');
		// foreach ($company->outlets as $outlet) {
		// 	$cashier = $company->employees()->inRandomOrder()->first();
		// 	$user = $cashier->user;
		// 	$user->roles()->attach(504);
		// 	$outlet->cashier_id = $cashier->id;
		// 	$outlet->save();
		// }

		$this->command->info('');
		$this->command->info('Creating Agents');

		//AGENTS
		for ($i = 1; $i <= $number_of_items; $i++) {
			$agent = Agent::firstOrNew([
				'company_id' => $company_id,
				'code' => 'c' . $company_id . '/agt' . $i,
			]);
			$agent->created_by = $admin->id;
			$agent->save();

			$user_type_id = 3122;
			$user = Employee::createUser($company, $user_type_id, $agent, $faker, $base_telephone_number . '300' . $i, $roles = 503);

			//AGENT ADDRESS
			$address_of_id = 3161;
			$address = Address::create($address_of_id, $agent, $faker, $company->id);

			$travel_modes = [];
			if ($i <= 10) {
				$travel_mode_ids = $company->travelModes()->pluck('id');
			} else {
				$travel_mode_ids = $company->travelModes()->inRandomOrder()->limit($faker->numberBetween(1, 5))->pluck('id');
			}
			foreach ($travel_mode_ids as $travel_mode_id) {
				$travel_modes[] = $travel_mode_id;
			}
			$agent->travelModes()->sync($travel_modes);
		}

		//STATE <> TRAVEL MODE <> AGENT <> SERVICE CHARGE MAPPING
		$this->command->info('');
		$this->command->info('MAPPING STATE <> TRAVEL MODE <> AGENT <> SERVICE CHARGE');
		foreach (NState::get() as $state) {
			$state->travelModes()->sync([]);
			foreach ($company->travelModes as $travel_mode) {
				$agent = Agent::whereHas('travelModes', function ($query) use ($travel_mode) {
					$query->where('id', $travel_mode->id);
				})->inRandomOrder()->first();

				$agent = $company->agents()->inRandomOrder()->first();
				// dd($agent, $travel_mode, $agent->travelModes);

				$travel_modes[$travel_mode->id] = [
					'agent_id' => $agent->id,
					'service_charge' => $faker->numberBetween(10, 100),
				];
			}
			$state->travelModes()->sync($travel_modes);
		}

		foreach ($company->employeeGrades as $grade) {
			//GRADE EXPENSE TYPE MAPPING
			$expense_type_ids = Config::where('config_type_id', 500)->inRandomOrder()->limit($faker->numberBetween(1, 4))->pluck('id');
			$expense_types = [];
			$grade->expenseTypes()->sync([]);
			foreach ($expense_type_ids as $expense_type_id) {
				// dd($company->cityCategories);
				foreach ($company->cityCategories as $city_category) {
					$expense_types[$expense_type_id] = [
						'eligible_amount' => $faker->randomElement([1000, 1500, 2000, 2500]),
						'city_category_id' => $city_category->id,
					];
					$grade->expenseTypes()->attach($expense_type_id, $expense_types[$expense_type_id]);
				}
			}

			//GRADE TRAVEL PURPOSE MAPPING
			$trip_purpose_ids = $company->tripPurposes()->inRandomOrder()->limit($faker->numberBetween(1, 4))->pluck('id');
			$trip_purposes = [];
			foreach ($trip_purpose_ids as $trip_purpose_id) {
				$trip_purposes[] = $trip_purpose_id;
			}
			$grade->tripPurposes()->sync($trip_purposes);

			//GRADE TRAVEL MODE MAPPING
			$travel_mode_ids = $company->travelModes()->inRandomOrder()->limit($faker->numberBetween(1, 4))->pluck('id');
			$travel_modes = [];
			foreach ($travel_mode_ids as $travel_mode_id) {
				$travel_modes[] = $travel_mode_id;
			}
			$grade->travelModes()->sync($travel_modes);

			//GRADE LOCAL TRAVEL MODE MAPPING
			$local_travel_mode_ids = $company->localTravelModes()->inRandomOrder()->limit($faker->numberBetween(1, 4))->pluck('id');
			$local_travel_modes = [];
			foreach ($local_travel_mode_ids as $local_travel_mode_id) {
				$local_travel_modes[] = $local_travel_mode_id;
			}
			$grade->localTravelModes()->sync($local_travel_modes);

			//GRADE ADVANCE ELIGIBILITY
			$advance_eligibility = $faker->randomElement([0, 1]);
			$deviation_eligiblity = $faker->randomElement([1, 2]);
			$grade_details = GradeAdvancedEligiblity::firstOrNew(['grade_id' => $grade->id]);
			$grade_details->advanced_eligibility = $advance_eligibility;
			$grade_details->deviation_eligiblity = $deviation_eligiblity;
			$grade_details->save();
			// $grade->gradeEligibility()->sync($advance_eligibility);
		}

		if ($create_dummy_records == 'y') {
			$this->command->info('-----------------');
			$this->command->info('CREATING TRIPS');
			Trip::join('employees as e', 'e.id', 'trips.employee_id')->where('e.company_id', $company->id)->forceDelete();
			$trip_number = 1;
			foreach ($company->employees()->whereNotNull('reporting_to_id')->limit(5)->orderBy('id')->get() as $employee) {
				for ($i = 1; $i <= 100; $i++) {

					if ($i > 0 && $i <= 5) {
						$trip_status_id = 3020; //NEW
						$booking_method_id = 3040; //SELF
						$booking_status_id = 3060; //PENDING
						$manager_verification_status_id = 3084; //NEW
					} elseif ($i > 5 && $i <= 10) {
						$trip_status_id = 3021; //MANAGER VERIFICATION PENDING
						$booking_method_id = 3040; //SELF
						$booking_status_id = 3060; //PENDING
						$manager_verification_status_id = 3080; //MANAGER VERIFICATION PENDING
					} elseif ($i > 10 && $i <= 15) {
						$trip_status_id = 3028; //MANAGER APPROVED
						$booking_method_id = 3040; //SELF
						$booking_status_id = 3060; //PENDING
						$manager_verification_status_id = 3081; //MANAGER APPROVED
					} elseif ($i > 15 && $i <= 20) {
						$trip_status_id = 3022; //MANAGER REJECTED
						$booking_method_id = 3040; //SELF
						$booking_status_id = 3060; //PENDING
						$manager_verification_status_id = 3082; //MANAGER REJECTED
					} elseif ($i > 20 && $i <= 25) {
						$trip_status_id = 3027; //RESOLVED
						$booking_method_id = 3040; //SELF
						$booking_status_id = 3060; //PENDING
						$manager_verification_status_id = 3083; //RESOLVED
					} elseif ($i > 25 && $i <= 30) {
						$trip_status_id = 3020; //NEW
						$booking_method_id = 3040; //SELF
						$booking_status_id = 3061; //BOOKED
						$booking_detail_status_id = 3240; // CLAIM PENDING
						$manager_verification_status_id = 3084; //NEW
					} elseif ($i > 30 && $i <= 35) {
						$trip_status_id = 3021; //MANAGER VERIFICATION PENDING
						$booking_method_id = 3040; //SELF
						$booking_status_id = 3061; //BOOKED
						$booking_detail_status_id = 3240; // CLAIM PENDING
						$manager_verification_status_id = 3080; //MANAGER VERIFICATION PENDING
					} elseif ($i > 35 && $i <= 40) {
						$trip_status_id = 3020; //NEW
						$booking_method_id = 3040; //SELF
						$booking_status_id = 3062; //CANCELLED
						$booking_detail_status_id = 3240; // CLAIM PENDING
						$manager_verification_status_id = 3084; //NEW
					} elseif ($i > 40 && $i <= 45) {
						$trip_status_id = 3021; //MANAGER VERIFICATION PENDING
						$booking_method_id = 3040; //SELF
						$booking_status_id = 3062; //CANCELLED
						$booking_detail_status_id = 3240; // CLAIM PENDING
						$manager_verification_status_id = 3080; //MANAGER VERIFICATION PENDING
					} elseif ($i > 45 && $i <= 50) {
						$trip_status_id = 3020; //NEW
						$booking_method_id = 3042; //AGENT
						$booking_status_id = 3060; //PENDING
						$manager_verification_status_id = 3084; //NEW
					} elseif ($i > 50 && $i <= 55) {
						$trip_status_id = 3021; //MANAGER VERIFICATION PENDING
						$booking_method_id = 3042; //AGENT
						$booking_status_id = 3060; //PENDING
						$manager_verification_status_id = 3080; //MANAGER VERIFICATION PENDING
					} elseif ($i > 55 && $i <= 60) {
						$trip_status_id = 3028; //MANAGER APPROVED
						$booking_method_id = 3042; //AGENT
						$booking_status_id = 3060; //PENDING
						$manager_verification_status_id = 3081; //MANAGER APPROVED
					} elseif ($i > 60 && $i <= 65) {
						$trip_status_id = 3022; //MANAGER REJECTED
						$booking_method_id = 3042; //AGENT
						$booking_status_id = 3060; //PENDING
						$manager_verification_status_id = 3082; //MANAGER REJECTED
					} elseif ($i > 65 && $i <= 70) {
						$trip_status_id = 3027; //RESOLVED
						$booking_method_id = 3042; //AGENT
						$booking_status_id = 3060; //PENDING
						$manager_verification_status_id = 3083; //RESOLVED
					} elseif ($i > 70 && $i <= 75) {
						$trip_status_id = 3020; //NEW
						$booking_method_id = 3042; //AGENT
						$booking_status_id = 3061; //BOOKED
						$booking_detail_status_id = 3240; // CLAIM PENDING
						$manager_verification_status_id = 3084; //NEW
					} elseif ($i > 75 && $i <= 80) {
						$trip_status_id = 3021; //MANAGER VERIFICATION PENDING
						$booking_method_id = 3042; //AGENT
						$booking_status_id = 3061; //BOOKED
						$booking_detail_status_id = 3241; // CLAIMED
						$manager_verification_status_id = 3080; //MANAGER VERIFICATION PENDING
					} elseif ($i > 80 && $i <= 85) {
						$trip_status_id = 3020; //NEW
						$booking_method_id = 3042; //AGENT
						$booking_status_id = 3062; //CANCELLED
						$booking_detail_status_id = 3241; // CLAIMED
						$manager_verification_status_id = 3084; //NEW
					} elseif ($i > 85 && $i <= 90) {
						$trip_status_id = 3021; //MANAGER VERIFICATION PENDING
						$booking_method_id = 3042; //AGENT
						$booking_detail_status_id = 3240; // CLAIM PENDING
						$booking_status_id = 3062; //CANCELLED
						$manager_verification_status_id = 3080; //MANAGER VERIFICATION PENDING
					} else {
						$trip_status_id = 3020; //NEW
						$booking_method_id = 3040; //SELF
						$booking_status_id = 3060; //PENDING
						$manager_verification_status_id = 3084; //NEW
					}
					$trip_number++;
					$trip = Trip::create($employee, $trip_number, $faker, $trip_status_id, $admin);
					//SINGLE CITY
					$src_city = $employee->outlet->address->city;
					$dest_city = NCity::where('id', '!=', $src_city->id)->inRandomOrder()->first();
					$visit1_date = Carbon::today();
					$visit2_date = Carbon::tomorrow();

					$visit = Visit::create($trip, $src_city, $dest_city, $visit1_date, $company, $booking_method_id, $booking_status_id, $trip_status_id, $manager_verification_status_id, $employee, $faker);

					//ADDING BBOKING DETAILS
					if ($visit->booking_status_id == 3061 || $visit->booking_status_id == 3062) {
						//BOOKED OR CANCELLED
						$booking = VisitBooking::create($visit, $faker, $booking_detail_status_id, $employee);
					}

					//RETURN TRAVEL
					$visit = Visit::create($trip, $dest_city, $src_city, $visit2_date, $company, $booking_method_id, $booking_status_id, $trip_status_id, $manager_verification_status_id, $employee, $faker);

					//ADDING BBOKING DETAILS
					if ($visit->booking_status_id == 3061 || $visit->booking_status_id == 3062) {
						//BOOKED OR CANCELLED
						$booking = VisitBooking::create($visit, $faker, $booking_detail_status_id, $employee);

						if ($booking->status_id == 3241) {
							//CLAIMED
							$agent_claim = new AgentClaim;
						}
					}
				}
			}

			$this->command->info('-----------------');
			$this->command->info('CREATING EXPENSE VOUCHERS');
			Trip::join('employees as e', 'e.id', 'trips.employee_id')->where('e.company_id', $company->id)->forceDelete();
			$trip_number = 1;
			foreach ($company->employees()->whereNotNull('reporting_to_id')->limit(5)->orderBy('id')->get() as $employee) {
				for ($i = 1; $i <= 100; $i++) {
				}
			}
		}

		$this->command->info('');
		$this->command->info('');

		$this->command->info('Seeder Completed');
		$this->command->info('Seeder Completed');
		$this->command->info('Seeder Completed');
	}
}
