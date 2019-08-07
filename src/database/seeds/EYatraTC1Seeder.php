<?php

namespace Uitoux\EYatra\Database\Seeds;

use App\Country;
use App\User;
use Carbon\Carbon;
use Faker\Factory as Faker;
use Illuminate\Database\Seeder;
use Uitoux\EYatra\Address;
use Uitoux\EYatra\Agent;
use Uitoux\EYatra\Company;
use Uitoux\EYatra\Config;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\Entity;
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

		$company_id = $this->command->ask("Enter company id", '7');
		$delete_company = $this->command->ask("Do you want to delete company", 'n');
		if ($delete_company == 'y') {
			$company = Company::find($company_id);
			$company->forceDelete();
		}

		$base_telephone_number = '1234567' . $company_id;
		$company = Company::firstOrNew([
			'id' => $company_id,
		]);
		$com_data['data']['code'] = 'com' . $company_id;
		$com_data['data']['name'] = 'Company ' . $company_id;
		$com_data['data']['address'] = $faker->streetAddress;
		$com_data['data']['cin_number'] = 'CIN' . $company_id;
		$com_data['data']['gst_number'] = 'GST' . $company_id;
		$com_data['data']['customer_care_email'] = 'customercare@com' . $company_id . '.in';
		$com_data['data']['customer_care_phone'] = $base_telephone_number . '00';
		$com_data['data']['reference_code'] = 'com' . $company_id;
		$company->fill($com_data['data']);
		$company->save();

		$company->users()->forceDelete();
		//ADMIN USERS
		$admin = User::firstOrNew([
			'company_id' => $company->id,
			'username' => 'c' . $company->id . '/a1',
		]);
		$admin->user_type_id = 3120;
		$admin->mobile_number = $base_telephone_number . '01';
		$admin->password = '$2y$10$N9pYzAbL2spl7vX3ZE1aBeekppaosAdixk04PTkK5obng7.KsLAQ2';
		$admin->save();
		$admin->roles()->sync(500);

		//COUNTRIES
		for ($i = 5; $i <= 6; $i++) {
			$country = Country::find($i);
			if ($country) {
				$country->delete();
			}
			$country = Country::firstOrNew([
				'id' => $i,
			]);
			$country->code = 'C' . $i;
			$country->name = 'Country ' . $i;
			$country->save();

			//STATES
			for ($j = 1; $j <= 9; $j++) {
				$state = NState::firstOrNew([
					'country_id' => $country->id,
					'code' => 'S' . $j,
				]);
				$state->name = 'Country ' . $i . ' / State ' . $j;
				$state->created_by = $admin->id;
				$state->save();

				//CITIES
				for ($k = 1; $k <= 15; $k++) {
					$city = NCity::firstOrNew([
						'state_id' => $state->id,
						'name' => 'Country ' . $i . ' / State ' . $j . ' / City ' . $k,
					]);
					$city->created_by = $admin->id;
					$city->save();
				}
			}
		}

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
				'L3',
				'M1',
				'M2',
				'M3',
				'M4',
				'O1',
				'O2',
				'O3',
				'O4',
				'S1',
				'S2',
				'S3',
				'SC1',
				'SC2',
				'SC3',
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
			],
			503 => [
				'A/C Taxi',
				'Non A/A Taxi',
				'Auto Ricksaw',
				'Bus',
			],
		];
		foreach ($sample_entities as $entity_type_id => $entities) {
			foreach ($entities as $entity_name) {
				$record = Entity::firstOrCreate([
					'entity_type_id' => $entity_type_id,
					'company_id' => $company->id,
					'name' => $entity_name,
					'created_by' => $admin->id,
				]);
			}
		}

		//OUTLETS
		for ($i = 1; $i <= 15; $i++) {
			$outlet = Outlet::firstOrNew([
				'company_id' => $company_id,
				'code' => 'c' . $company_id . '/o' . $i,
			]);
			$outlet->name = 'Company ' . $company->id . ' / Outlet ' . $i;
			$outlet->created_by = $admin->id;
			$outlet->save();

			//OUTLET ADDRESS
			$address = Address::firstOrNew([
				'address_of_id' => 3160,
				'entity_id' => $outlet->id,
			]);
			$address->name = 'Primary';
			$address->line_1 = $faker->streetAddress;
			$country = NCountry::find(5);
			$state = $country->states()->inRandomOrder()->first();
			$city = $state->cities()->inRandomOrder()->first();
			$address->country_id = $country->id;
			$address->state_id = $state->id;
			$address->city_id = $city->id;
			$address->save();

			//EMPLOYEES - MANAGERS
			for ($j = 1; $j <= 5; $j++) {
				$manager = Employee::firstOrNew([
					'company_id' => $company_id,
					'code' => $outlet->code . '/mngr' . $j,
				]);
				$manager->outlet_id = $outlet->id;
				$manager->grade_id = $company->employeeGrades()->inRandomOrder()->first()->id;
				$manager->created_by = $admin->id;
				$manager->save();

				//USER ACCOUNT
				$user = new User();
				$user->company_id = $company->id;
				$user->user_type_id = 3121;
				$user->entity_id = $manager->id;
				$user->username = $manager->code;
				$user->mobile_number = $faker->unique()->numberBetween(9842000000, 9842099999);
				$user->password = '$2y$10$N9pYzAbL2spl7vX3ZE1aBeekppaosAdixk04PTkK5obng7.KsLAQ2';
				$user->save();
				$user->roles()->sync(502);

				//EMPLOYEES - REGULAR
				for ($k = 1; $k <= 5; $k++) {
					$employee = Employee::firstOrNew([
						'company_id' => $company_id,
						'code' => $manager->code . '/e' . $k,
					]);
					$employee->outlet_id = $outlet->id;
					$employee->reporting_to_id = $manager->id;
					$employee->grade_id = $company->employeeGrades()->inRandomOrder()->first()->id;
					$employee->created_by = $admin->id;
					$employee->save();

					//USER ACCOUNT
					$user = new User();
					$user->company_id = $company->id;
					$user->user_type_id = 3121;
					$user->entity_id = $employee->id;
					$user->username = $employee->code;
					$user->mobile_number = $faker->unique()->numberBetween(9842000000, 9842099999);
					$user->password = '$2y$10$N9pYzAbL2spl7vX3ZE1aBeekppaosAdixk04PTkK5obng7.KsLAQ2';
					$user->save();
					$user->roles()->sync(501);
				}
			}
		}

		//AGENTS
		for ($i = 1; $i <= 15; $i++) {
			$agent = Agent::firstOrNew([
				'company_id' => $company_id,
				'code' => 'c' . $company_id . '/agt' . $i,
			]);
			$agent->name = 'Company ' . $company->id . ' / Agent ' . $i;
			$agent->created_by = $admin->id;
			$agent->save();

			//USER ACCOUNT
			$user = new User();
			$user->company_id = $company->id;
			$user->user_type_id = 3122;
			$user->entity_id = $agent->id;
			$user->username = $agent->code;
			$user->mobile_number = $faker->unique()->numberBetween(9842000000, 9842099999);
			$user->password = '$2y$10$N9pYzAbL2spl7vX3ZE1aBeekppaosAdixk04PTkK5obng7.KsLAQ2';
			$user->save();
			$user->roles()->sync(503);

			//AGENT ADDRESS
			$address = Address::firstOrNew([
				'address_of_id' => 3161,
				'entity_id' => $agent->id,
			]);
			$address->name = 'Primary';
			$address->line_1 = $faker->streetAddress;
			$country = NCountry::find(5);
			$state = $country->states()->inRandomOrder()->first();
			$city = $state->cities()->inRandomOrder()->first();
			$address->country_id = $country->id;
			$address->state_id = $state->id;
			$address->city_id = $city->id;
			$address->save();

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
		foreach (NState::get() as $state) {
			$state->travelModes()->sync([]);
			foreach (Entity::travelModeList() as $travel_mode) {
				$agent = Agent::whereHas('travelModes', function ($query) use ($travel_mode) {
					$query->where('id', $travel_mode->id);
				})->inRandomOrder()->first();

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
			foreach ($expense_type_ids as $expense_type_id) {
				$expense_types[$expense_type_id] = [
					'eligible_amount' => $faker->randomElement([1000, 1500, 2000, 2500]),
				];
			}
			$grade->expenseTypes()->sync($expense_types);

			//GRADE TRAVEL PURPOSE MAPPING
			$trip_purpose_ids = $company->tripPurposes()->inRandomOrder()->limit($faker->numberBetween(1, 4))->pluck('id');
			$trip_purposes = [];
			foreach ($trip_purpose_ids as $trip_purpose_id) {
				$trip_purposes[] = $trip_purpose_id;
			}
			$grade->tripPurposes()->sync($trip_purposes);
		}

		$trip_number = 1;
		Trip::join('employees as e', 'e.id', 'trips.employee_id')->where('e.company_id', $company->id)->forceDelete();
		foreach ($company->employees()->whereNotNull('reporting_to_id')->limit(5)->get() as $employee) {

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
					$manager_verification_status_id = 3084; //NEW
				} elseif ($i > 40 && $i <= 45) {
					$trip_status_id = 3021; //MANAGER VERIFICATION PENDING
					$booking_method_id = 3040; //SELF
					$booking_status_id = 3062; //CANCELLED
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
					$manager_verification_status_id = 3084; //NEW
				} elseif ($i > 85 && $i <= 90) {
					$trip_status_id = 3021; //MANAGER VERIFICATION PENDING
					$booking_method_id = 3042; //AGENT
					$booking_status_id = 3062; //CANCELLED
					$manager_verification_status_id = 3080; //MANAGER VERIFICATION PENDING
				} else {
					$trip_status_id = 3020; //NEW
					$booking_method_id = 3040; //SELF
					$booking_status_id = 3060; //PENDING
					$manager_verification_status_id = 3084; //NEW
				}
				$trip = new Trip();
				$trip->employee_id = $employee->id;
				$trip->number = 'TRP' . $trip_number++;
				$trip->purpose_id = $employee->grade->tripPurposes()->inRandomOrder()->first()->id;
				$trip->description = $faker->sentence;
				$trip->manager_id = $employee->reporting_to_id;
				$trip->status_id = $trip_status_id; //NEW
				$trip->advance_received = $faker->randomElement([0, 500, 100, 1500, 2000]);
				$trip->created_by = $admin->id;
				$trip->save();

				//SINGLE CITY
				$src_city = $employee->outlet->address->city;
				//dd($src_city);
				$dest_city = NCity::where('id', '!=', $src_city->id)->inRandomOrder()->first();
				$visit1_date = Carbon::today();
				$visit2_date = Carbon::tomorrow();

				$visit = new Visit();
				$visit->trip_id = $trip->id;
				$visit->from_city_id = $src_city->id;
				$visit->to_city_id = $dest_city->id;
				$visit->date = $visit1_date;
				$visit->travel_mode_id = $company->travelModes()->inRandomOrder()->first()->id;
				$visit->booking_method_id = $booking_method_id;
				$visit->booking_status_id = $booking_status_id;
				$visit->status_id = $trip_status_id;
				$visit->manager_verification_status_id = $manager_verification_status_id;
				if ($visit->booking_method_id == 3042) {
					//AGENT
					$state = $employee->outlet->address->city->state;
					$agent = $state->agents()->withPivot('travel_mode_id')->where('travel_mode_id', $visit->travel_mode_id)->first();
					$visit->agent_id = $agent->id;
					$visit->notes_to_agent = $faker->sentence;
				}
				$visit->save();

				//ADDING BBOKING DETAILS
				if ($visit->booking_status_id == 3061 || $visit->booking_status_id == 3062) {
					//BOOKED OR CANCELLED
					$booking = new VisitBooking;
					$booking->visit_id = $visit->id;
					$booking->type_id = 3100; // FRESH BOOKING
					$booking->travel_mode_id = $visit->travel_mode_id;
					$booking->reference_number = $faker->swiftBicNumber;
					$booking->amount = $faker->numberBetween(500, 2000);
					$booking->tax = $booking->amount * 10 / 100;
					$booking->total = $booking->amount + $booking->tax;
					$booking->status_id = $booking_detail_status_id;
					$booking->created_by = $employee->user->id;
					if ($visit->booking_method_id == 3042) {
						//AGENT
						// $agent = Tra::whereHas('travelModes', function ($query) use ($travel_mode) {
						// 	$query->where('id', $travel_mode->id);
						// })->inRandomOrder()->first();

						$booking->service_charge = $faker->randomElement([100, 200, 300, 400, 500]);
					}

					$booking->save();

				}

				//RETURN TRAVEL
				$visit = new Visit();
				$visit->trip_id = $trip->id;
				$visit->from_city_id = $dest_city->id;
				$visit->to_city_id = $src_city->id;
				$visit->date = $visit2_date;
				$visit->travel_mode_id = $company->travelModes()->inRandomOrder()->first()->id;
				$visit->booking_method_id = $booking_method_id;
				$visit->booking_status_id = $booking_status_id;
				$visit->status_id = $trip_status_id;
				$visit->manager_verification_status_id = $manager_verification_status_id;
				if ($visit->booking_method_id == 3042) {
					//AGENT
					$state = $employee->outlet->address->city->state;
					$agent = $state->agents()->withPivot('travel_mode_id')->where('travel_mode_id', $visit->travel_mode_id)->first();
					$visit->agent_id = $agent->id;
					$visit->notes_to_agent = $faker->sentence;
				}
				$visit->save();

				//ADDING BBOKING DETAILS
				if ($visit->booking_status_id == 3061 || $visit->booking_status_id == 3062) {
					//BOOKED OR CANCELLED
					$booking = new VisitBooking;
					$booking->visit_id = $visit->id;
					$booking->type_id = 3100; // FRESH BOOKING
					$booking->travel_mode_id = $visit->travel_mode_id;
					$booking->reference_number = $faker->swiftBicNumber;
					$booking->amount = $faker->numberBetween(500, 2000);
					$booking->tax = $booking->amount * 10 / 100;
					$booking->total = $booking->amount + $booking->tax;
					$booking->status_id = $booking_detail_status_id;
					$booking->created_by = $employee->user->id;
					if ($visit->booking_method_id == 3042) {
						//AGENT
						// $agent = Tra::whereHas('travelModes', function ($query) use ($travel_mode) {
						// 	$query->where('id', $travel_mode->id);
						// })->inRandomOrder()->first();

						$booking->service_charge = $faker->randomElement([100, 200, 300, 400, 500]);
					}

					$booking->save();

					if ($booking->status_id == 3241) {
						//CLAIMED
					}

				}

			}

		}
	}
}
