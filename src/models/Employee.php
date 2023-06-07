<?php

namespace Uitoux\EYatra;
use App\Entity;
use App\HrmsToTravelxEmployeeSyncLog;
use App\MailConfiguration;
use App\Mail\TravelexConfigMail;
use App\User;
use Auth;
use Carbon\Carbon;
use Config as databaseConfig;
use DB;
use Excel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Mail;
use Uitoux\EYatra\Company;
use Uitoux\EYatra\Department;
use Uitoux\EYatra\Designation;
use Uitoux\EYatra\Lob;
use Uitoux\EYatra\Outlet;
use Uitoux\EYatra\Sbu;

class Employee extends Model {
	use SoftDeletes;

	protected $fillable = [
		// 'id',
		'company_id',
		'code',
		'outlet_id',
		'reporting_to_id',
		'grade_id',
		'payment_mode_id',
		'department_id',
		'data_source',
		'sbu_id',
		'designation_id',
		'date_of_joining',
		'aadhar_no',
		'pan_no',
		'self_approve',
		'gender',
		'date_of_birth',
		'created_by',
		'updated_by',
		'deleted_by',
	];

	public function company() {
		return $this->belongsTo('App\Company');
	}

	public function tripEmployeeClaim() {
		return $this->hasOne('Uitoux\EYatra\EmployeeClaim');
	}

	public function Sbu() {
		return $this->belongsTo('Uitoux\EYatra\Sbu');
	}
	public function Department() {
		return $this->belongsTo('Uitoux\EYatra\Department')->withTrashed();
	}

	public function trips() {
		return $this->hasMany('Uitoux\EYatra\Trip');
	}

	public function grade() {
		return $this->belongsTo('Uitoux\EYatra\Entity', 'grade_id')->withTrashed();
	}

	public function grade_details() {
		return $this->belongsTo('Uitoux\EYatra\GradeAdvancedEligiblity', 'grade_id');
	}

	public function designation() {
		return $this->belongsTo('Uitoux\EYatra\Designation')->withTrashed();
	}

	public function outlet() {
		return $this->belongsTo('Uitoux\EYatra\Outlet')->withTrashed();
	}

	public function bankDetail() {
		return $this->hasOne('Uitoux\EYatra\BankDetail', 'entity_id')->where('detail_of_id', 3121);
	}
	public function chequeDetail() {
		return $this->hasOne('Uitoux\EYatra\ChequeDetail', 'entity_id')->where('detail_of_id', 3121);
	}
	public function walletDetail() {
		return $this->hasOne('Uitoux\EYatra\WalletDetail', 'entity_id')->where('wallet_of_id', 3121);
	}

	public function reportingTo() {
		return $this->belongsTo('Uitoux\EYatra\Employee', 'reporting_to_id')->withTrashed();
	}
	public function deviationApprover() {
		return $this->belongsTo('Uitoux\EYatra\Employee')->withTrashed();
	}

	public function manager() {
		return $this->belongsTo('Uitoux\EYatra\Employee', 'reporting_to_id')->withTrashed();
	}

	public function paymentMode() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'payment_mode_id');
	}
	public function vehicleDetails() {
		return $this->hasMany('Uitoux\EYatra\VehicleDetails');
	}

	public function user() {
		return $this->hasOne('App\User', 'entity_id')->where('user_type_id', 3121)->withTrashed();
	}

	public static function getList() {
		return Employee::select('users.name', 'employees.id')
			->leftJoin('users', 'users.entity_id', 'employees.id')
			->where('users.user_type_id', 3121)
			->get();
	}
	public static function getEmployeeListBasedCompany() {
		return Employee::select(
			DB::raw('CONCAT(users.name," / ",employees.code) as name')
			, 'employees.id')
			->leftJoin('users', 'users.entity_id', 'employees.id')
			->where('users.user_type_id', 3121)
			->where('users.company_id', Auth::user()->company_id)
			->get();
	}

	public static function create2($company, $outlet, $admin, $faker, $manager_id = null) {
		$emp_code = Employee::withTrashed()->select('id')->orderBy('id', 'desc')->limit(1)->first();
		if ($emp_code) {
			$employee_code = $emp_code->id + 1;
			$code = "EMP" . $employee_code;
		} else {
			$code = 'EMP1';
		}
		$employee = Employee::firstOrNew([
			'company_id' => $company->id,
			'code' => $code,
		]);
		$employee->outlet_id = $outlet->id;
		$employee->grade_id = $company->employeeGrades()->inRandomOrder()->first()->id;
		$lob = $company->lobs()->inRandomOrder()->first();
		$employee->sbu_id = $lob->sbus()->inRandomOrder()->first()->id;
		$employee->designation_id = $company->designations()->inRandomOrder()->first()->id;
		$employee->aadhar_no = $faker->creditCardNumber;
		$employee->pan_no = $faker->swiftBicNumber;
		$employee->gender = $faker->randomElement(['Male', 'Female']);
		$employee->reporting_to_id = $manager_id;
		$employee->payment_mode_id = Config::where('config_type_id', 514)->inRandomOrder()->first()->id;
		$employee->created_by = $admin->id;
		$employee->save();

		return $employee;
	}

	public static function generate($company, $code, $outlet, $admin, $faker, $manager_id = null) {
		$employee = Employee::firstOrNew([
			'company_id' => $company->id,
			'code' => $code,
		]);
		$employee->outlet_id = $outlet->id;
		$employee->grade_id = $company->employeeGrades()->inRandomOrder()->first()->id;
		$lob = $company->lobs()->inRandomOrder()->first();
		$employee->sbu_id = $lob->sbus()->inRandomOrder()->first()->id;
		$employee->designation_id = $company->designations()->inRandomOrder()->first()->id;
		$employee->aadhar_no = $faker->creditCardNumber;
		$employee->pan_no = $faker->swiftBicNumber;
		$employee->gender = $faker->randomElement(['Male', 'Female']);
		$employee->reporting_to_id = $manager_id;
		$employee->payment_mode_id = Config::where('config_type_id', 514)->inRandomOrder()->first()->id;
		$employee->created_by = $admin->id;
		$employee->save();

		return $employee;
	}
	public static function createUser($company, $user_type_id, $entity, $faker, $mobile_number, $roles) {
		$user = User::firstOrNew([
			'company_id' => $company->id,
			'user_type_id' => $user_type_id,
			'username' => $entity->code,
		]);
		$user->name = $faker->name;
		$user->entity_id = $entity->id;
		$user->email = $faker->safeEmail;
		$user->mobile_number = $mobile_number;

		$user->password = 'Test@123';
		$user->save();
		$user->roles()->sync($roles);
		return $user;

	}
	public function setDateOfJoiningAttribute($value) {
		return $this->attributes['date_of_joining'] = $value ? date('Y-m-d', strtotime($value)) : date('Y-m-d');
	}

	public function getDateOfJoiningAttribute($value) {
		return date('d-m-Y', strtotime($value));
	}

	public function setDateOfBirthAttribute($value) {
		return $this->attributes['date_of_birth'] = $value ? date('Y-m-d', strtotime($value)) : date('Y-m-d');
	}

	public function getDateOfBirthAttribute($value) {
		return date('d-m-Y', strtotime($value));
	}

	public static function hrmsEmployeeAdditionSync($request) {
		// dd($request->all());
		$hrmsPortalConfig = config('custom.HRMS_PORTAL_CONFIG');
		if ($hrmsPortalConfig == true) {
			$hrmsPortal = DB::table('portals')->select([
				'db_host_name',
				'db_port_number',
				'db_name',
				'db_user_name',
				'db_password',
			])
				->where('id', 2) //HRMS
				->first();

			$dbHostName = $hrmsPortal->db_host_name;
			$dbPortNumber = $hrmsPortal->db_port_number;
			$dbPortDriver = 'mysql';
			$dbName = $hrmsPortal->db_name;
			$dbUserName = $hrmsPortal->db_user_name;
			$dbPassword = $hrmsPortal->db_password;
		} else {
			$dbHostName = config('custom.HRMS_DB_HOST');
			$dbPortNumber = config('custom.HRMS_DB_PORT_NUMBER');
			$dbPortDriver = 'mysql';
			$dbName = config('custom.HRMS_DB_NAME');
			$dbUserName = config('custom.HRMS_DB_USER_NAME');
			$dbPassword = config('custom.HRMS_DB_PASSWORD');
		}

		$employeeAdditionExistLog = HrmsToTravelxEmployeeSyncLog::select([
			'id',
			'from_date_time',
			'to_date_time',
		])
			->where('company_id', $request->company_id)
			->where('type_id', 3961) //EMPLOYEE ADDITION
			->orderBy('id', 'DESC')
			->first();
		if ($employeeAdditionExistLog) {
			$fromDateTime = $employeeAdditionExistLog->to_date_time;
			$toDateTime = date("Y-m-d H:i:s");
		} else {
			$fromDateTime = date('Y-m-d') . ' 00:00:00';
			$toDateTime = date("Y-m-d H:i:s");
		}

		DB::setDefaultConnection('dynamic');
		dataBaseConfig::set('database.connections.dynamic.host', $dbHostName);
		dataBaseConfig::set('database.connections.dynamic.port', $dbPortNumber);
		dataBaseConfig::set('database.connections.dynamic.driver', $dbPortDriver);
		dataBaseConfig::set('database.connections.dynamic.database', $dbName);
		dataBaseConfig::set('database.connections.dynamic.username', $dbUserName);
		dataBaseConfig::set('database.connections.dynamic.password', $dbPassword);
		DB::purge('dynamic');
		DB::reconnect('dynamic');
		$hrmsCompanyId = DB::table('companies')
			->where('adre_code', $request->company_code)
			->pluck('id')
			->first();
		if (!$hrmsCompanyId) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['The logined user company not found in HRMS'],
			]);
		}

		$hrmsEmployees = DB::table('employees')->select([
			'employees.id',
			'employees.company_id',
			'employees.code as employee_code',
			'employees.name as employee_name',
			'employees.doj',
			'employees.dob',
			'employees.mobile_number',
			'employees.email',
			'companies.adre_code',
			'outlet_companies.adre_code as outlet_company_adre_code',
			'outlets.id as outlet_id',
			'outlets.code as outlet_code',
			'outlets.name as outlet_name',
			'grade_companies.adre_code as grade_company_adre_code',
			'grades.code as grade_code',
			'designation_companies.adre_code as designation_company_adre_code',
			'designations.code as designation_code',
			'lob_companies.adre_code as lob_company_adre_code',
			'lobs.id as lob_id',
			'lobs.code as lob_code',
			'sbus.code as sbu_code',
			'employee_details.pan',
			'employee_details.aadhar',
			'genders.label as gender_name',
			'reporting_to_companies.adre_code as reporting_to_company_adre_code',
			'reporting_to_employees.code as reporting_to_employee_code',
			'reporting_to_employees.name as reporting_to_employee_name',
			'reporting_to_outlets.code as reporting_to_outlet_code',
			'reporting_to_outlets.name as reporting_to_outlet_name',
			'reporting_to_outlet_companies.adre_code as reporting_to_outlet_company_adre_code',
			'reporting_to_grades.code as reporting_to_grade_code',
			'reporting_to_grade_companies.adre_code as reporting_to_grade_company_adre_code',
			'reporting_to_employees.mobile_number as reporting_to_mobile_number',
			'reporting_to_employees.email as reporting_to_email',
			'reporting_to_employees.dob as reporting_to_dob',
			'funcs.name as function_name',
			'funcs_companies.adre_code as func_company_adre_code',
		])
			->join('companies', 'companies.id', 'employees.company_id')
			->leftjoin('outlets', 'outlets.id', 'employees.outlet_id')
			->leftjoin('companies as outlet_companies', 'outlet_companies.id', 'outlets.company_id')
			->join('grades', 'grades.id', 'employees.grade_id')
			->leftjoin('companies as grade_companies', 'grade_companies.id', 'grades.company_id')
			->leftjoin('designations', 'designations.id', 'employees.designation_id')
			->leftjoin('companies as designation_companies', 'designation_companies.id', 'designations.company_id')
			->leftjoin('lobs', 'lobs.id', 'employees.lob_id')
			->leftjoin('companies as lob_companies', 'lob_companies.id', 'lobs.company_id')
			->leftjoin('sbus', 'sbus.id', 'employees.sbu_id')
			->leftjoin('employee_details', 'employee_details.employee_id', 'employees.id')
			->leftjoin('genders', 'genders.id', 'employees.gender_id')
			->leftjoin('funcs', 'funcs.id', 'employees.func_id')
			->leftjoin('companies as funcs_companies', 'funcs_companies.id', 'funcs.company_id')
			->leftjoin('employees as reporting_to_employees', 'reporting_to_employees.id', 'employees.reporting_to_id')
			->leftjoin('companies as reporting_to_companies', 'reporting_to_companies.id', 'reporting_to_employees.company_id')
			->leftjoin('outlets as reporting_to_outlets', 'reporting_to_outlets.id', 'reporting_to_employees.outlet_id')
			->leftjoin('companies as reporting_to_outlet_companies', 'reporting_to_outlet_companies.id', 'reporting_to_outlets.company_id')
			->leftjoin('grades as reporting_to_grades', 'reporting_to_grades.id', 'reporting_to_employees.grade_id')
			->leftjoin('companies as reporting_to_grade_companies', 'reporting_to_grade_companies.id', 'reporting_to_grades.company_id')
			->where('employees.company_id', $hrmsCompanyId)
			->whereBetween('employees.created_at', [$fromDateTime, $toDateTime])
			->whereIn('employees.lob_id', [4, 15]) //DLOB, OESL
			->get();

		if (count($hrmsEmployees) == 0) {
			$formattedFromDateTime = date('d/m/Y h:i A', strtotime($fromDateTime));
			$formattedToDateTime = date('d/m/Y h:i A', strtotime($toDateTime));

			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['New employee details not found for this period : ' . $formattedFromDateTime . ' to ' . $formattedToDateTime],
			]);
		}

		if (count($hrmsEmployees) > 0) {
			foreach ($hrmsEmployees as $hrmsEmployeeData) {
				$outletHrEmail = null;
				$outletHrRoleId = DB::table('roles')
					->where('company_id', $hrmsEmployeeData->company_id)
					->where('name', 'Outlet HR')
					->pluck('id')
					->first();
				if ($outletHrRoleId) {
					$outletHrEmployeeIds = DB::table('user_has_roles')
						->join('employees', 'employees.user_id', 'user_has_roles.user_id')
						->where('user_has_roles.role_id', $outletHrRoleId)
						->pluck('employees.id');
					if ($outletHrEmployeeIds) {
						$outletHrEmail = DB::table('employee_outlets')->select([
							'employee_outlets.id',
							'employees.code',
							'employees.email',
						])
							->join('employees', 'employees.id', 'employee_outlets.employee_id')
							->whereIn('employee_outlets.employee_id', $outletHrEmployeeIds)
							->where('employee_outlets.outlet_id', $hrmsEmployeeData->outlet_id)
							->pluck('email')
							->first();
					}
				}
				$hrmsEmployeeData->outlet_hr_email = $outletHrEmail;
			}
		}

		DB::setDefaultConnection('mysql');
		if (count($hrmsEmployees) > 0) {
			$employeeSyncedData = [];
			$employeeErrorReport = [];
			foreach ($hrmsEmployees as $hrmsEmployee) {
				DB::beginTransaction();
				try {
					$skip = false;
					$recordErrors = [];

					//CHECK EMPLOYEE LOB IS NOT DLOB, OESL
					// if (!in_array($hrmsEmployee->lob_id, [4, 15])) {
					// 	continue;
					// }

					//EMPLOYEE COMPANY
					if (!$hrmsEmployee->adre_code) {
						$skip = true;
						$recordErrors[] = 'The employee company is required';
					} else {
						$companyId = Company::where('code', $hrmsEmployee->adre_code)->pluck('id')->first();
						if (!$companyId) {
							$skip = true;
							$recordErrors[] = 'The employee company not found in travelex';
						} else {
							$employeeExistId = Employee::withTrashed()
								->where('company_id', $companyId)
								->where('code', $hrmsEmployee->employee_code)
								->pluck('id')
								->first();
							if ($employeeExistId) {
								$skip = true;
								$recordErrors[] = 'The employee detail already available in travelex';
							}
						}
					}

					//EMPLOYEE MOBILE NUMBER
					if (!$hrmsEmployee->mobile_number) {
						$skip = true;
						$recordErrors[] = 'The employee mobile number is requried';
					} else {
						if (!preg_match('/^[0-9]{10}+$/', $hrmsEmployee->mobile_number)) {
							$skip = true;
							$recordErrors[] = 'The employee mobile number is invalid';
						}
					}

					//EMPLOYEE OUTLET
					if ($hrmsEmployee->outlet_code) {
						$outletCompanyId = Company::where('code', $hrmsEmployee->outlet_company_adre_code)->pluck('id')->first();
						if (!$outletCompanyId) {
							$skip = true;
							$recordErrors[] = 'The employee outlet company not found in travelex';
						} else {
							$outlet = Outlet::withTrashed()->firstOrNew([
								'company_id' => $outletCompanyId,
								'code' => $hrmsEmployee->outlet_code,
							]);
							if ($outlet->exists) {
								$outlet->updated_by = Auth::id();
								$outlet->updated_at = Carbon::now();
							} else {
								$outlet->created_by = Auth::id();
								$outlet->created_at = Carbon::now();
							}
							$outlet->name = $hrmsEmployee->outlet_name;
							$outlet->save();
						}
					} else {
						$skip = true;
						$recordErrors[] = 'The employee outlet code is required';
					}

					//EMPLOYEE GRADE
					if ($hrmsEmployee->grade_code) {
						$gradeCompanyId = Company::where('code', $hrmsEmployee->grade_company_adre_code)->pluck('id')->first();
						if (!$gradeCompanyId) {
							$skip = true;
							$recordErrors[] = 'The employee grade company not found in travelex';
						} else {
							$grade = Entity::withTrashed()->firstOrNew([
								'company_id' => $gradeCompanyId,
								'entity_type_id' => 500, //GRADE
								'name' => $hrmsEmployee->grade_code,
							]);
							if ($grade->exists) {
								$grade->updated_by = Auth::id();
								$grade->updated_at = Carbon::now();
							} else {
								$grade->created_by = Auth::id();
								$grade->created_at = Carbon::now();
							}
							$grade->save();

							//EMPLOYEE DESIGNATION
							if ($hrmsEmployee->designation_code) {
								$designationCompanyId = Company::where('code', $hrmsEmployee->designation_company_adre_code)->pluck('id')->first();
								if (!$designationCompanyId) {
									$skip = true;
									$recordErrors[] = 'The employee designation company not found in travelex';
								} else {
									$designation = Designation::withTrashed()->firstOrNew([
										'company_id' => $designationCompanyId,
										'name' => $hrmsEmployee->designation_code,
										'grade_id' => $grade->id,
									]);
									if ($designation->exists) {
										$designation->updated_by = Auth::id();
										$designation->updated_at = Carbon::now();
									} else {
										$designation->created_by = Auth::id();
										$designation->created_at = Carbon::now();
									}
									$designation->save();
								}
							}
						}
					} else {
						$skip = true;
						$recordErrors[] = 'The employee grade is required';
					}

					//EMLOYEE LOB & SBU
					if ($hrmsEmployee->lob_code) {
						$lobCompanyId = Company::where('code', $hrmsEmployee->lob_company_adre_code)->pluck('id')->first();
						if (!$lobCompanyId) {
							$skip = true;
							$recordErrors[] = 'The employee lob company not found in travelex';
						} else {
							$lob = Lob::firstOrNew([
								'company_id' => $lobCompanyId,
								'name' => $hrmsEmployee->lob_code,
							]);
							if ($lob->exists) {
								$lob->updated_by = Auth::id();
								$lob->updated_at = Carbon::now();
							} else {
								$lob->created_by = Auth::id();
								$lob->created_at = Carbon::now();
							}
							$lob->save();

							if ($hrmsEmployee->sbu_code) {
								$sbu = Sbu::firstOrNew([
									'lob_id' => $lob->id,
									'name' => $hrmsEmployee->sbu_code,
								]);
								if ($sbu->exists) {
									$sbu->updated_by = Auth::id();
									$sbu->updated_at = Carbon::now();
								} else {
									$sbu->created_by = Auth::id();
									$sbu->created_at = Carbon::now();
								}
								$sbu->save();

								//EMPLOYEE DEPARTMENT
								$businessId = null;
								if ($hrmsEmployee->lob_code == 'DLOB') {
									if (strpos($hrmsEmployee->sbu_code, 'honda') !== false) {
										$businessId = 3; //HONDA
									} else {
										$businessId = 1; //DLOB
									}
								} else if ($hrmsEmployee->lob_code == 'OESL') {
									$businessId = 2; //OESL
								}

								if ($businessId && $hrmsEmployee->function_name) {
									$funcCompanyId = Company::where('code', $hrmsEmployee->func_company_adre_code)->pluck('id')->first();
									if (!$funcCompanyId) {
										$skip = true;
										$recordErrors[] = 'The employee function company not found in travelex';
									} else {
										$department = Department::withTrashed()->firstOrNew([
											'company_id' => $funcCompanyId,
											'business_id' => $businessId,
											'name' => $hrmsEmployee->function_name,
										]);
										if ($department->exists) {
											$department->updated_by = Auth::id();
											$department->updated_at = Carbon::now();
										} else {
											$department->created_by = Auth::id();
											$department->created_at = Carbon::now();
											$department->short_name = $hrmsEmployee->function_name;
										}
										$department->save();
									}
								}
							} else {
								$skip = true;
								$recordErrors[] = 'The employee sbu is required';
							}
						}
					}

					//EMPLOYEE REPORTING TO DETAILS
					if ($hrmsEmployee->reporting_to_employee_code) {
						//EMPLOYEE REPORTING TO MOBILE NUMBER
						// if (!$hrmsEmployee->reporting_to_mobile_number) {
						// 	$skip = true;
						// 	$recordErrors[] = 'The reporting to employee mobile number is requried';
						// } else {
						// 	if (!preg_match('/^[0-9]{10}+$/', $hrmsEmployee->reporting_to_mobile_number)) {
						// 		$skip = true;
						// 		$recordErrors[] = 'The reporting to employee mobile number is invalid';
						// 	}
						// }

						$reportingToCompanyId = Company::where('code', $hrmsEmployee->reporting_to_company_adre_code)->pluck('id')->first();
						if (!$reportingToCompanyId) {
							$skip = true;
							$recordErrors[] = 'The reporting to employee company not found in travelex';
						} else {
							$reportingToEmployeeExistId = Employee::withTrashed()
								->where('company_id', $reportingToCompanyId)
								->where('code', $hrmsEmployee->reporting_to_employee_code)
								->pluck('id')
								->first();
							if (!$reportingToEmployeeExistId) {
								$skip = true;
								$recordErrors[] = 'The reporting to employee detail not available in travelex';
							}
						}

						//EMPLOYEE REPORTING TO OUTLET
						// if ($hrmsEmployee->reporting_to_outlet_code) {
						// 	$reportingToOutletCompanyId = Company::where('code', $hrmsEmployee->reporting_to_outlet_company_adre_code)->first()->id;
						// 	if (!$reportingToOutletCompanyId) {
						// 		$skip = true;
						// 		$recordErrors[] = 'The reporting to outlet company not found in travelex';
						// 	} else {
						// 		$reportingToOutlet = Outlet::withTrashed()->firstOrNew([
						// 			'company_id' => $reportingToOutletCompanyId,
						// 			'code' => $hrmsEmployee->reporting_to_outlet_code,
						// 		]);
						// 		if ($reportingToOutlet->exists) {
						// 			$reportingToOutlet->updated_by = Auth::id();
						// 			$reportingToOutlet->updated_at = Carbon::now();
						// 		} else {
						// 			$reportingToOutlet->created_by = Auth::id();
						// 			$reportingToOutlet->created_at = Carbon::now();
						// 		}
						// 		$reportingToOutlet->name = $hrmsEmployee->reporting_to_outlet_name;
						// 		$reportingToOutlet->save();
						// 	}
						// } else {
						// 	$skip = true;
						// 	$recordErrors[] = 'The reporting to outlet code is required';
						// }

						//EMPLOYEE REPORTING TO GRADE
						// if ($hrmsEmployee->reporting_to_grade_code) {
						// 	$reportingToGradeCompanyId = Company::where('code', $hrmsEmployee->reporting_to_grade_company_adre_code)->first()->id;
						// 	if (!$reportingToGradeCompanyId) {
						// 		$skip = true;
						// 		$recordErrors[] = 'The reporting to grade company not found in travelex';
						// 	} else {
						// 		$reportingToGrade = Entity::withTrashed()->firstOrNew([
						// 			'company_id' => $reportingToGradeCompanyId,
						// 			'entity_type_id' => 500, //GRADE
						// 			'name' => $hrmsEmployee->reporting_to_grade_code,
						// 		]);
						// 		if ($reportingToGrade->exists) {
						// 			$reportingToGrade->updated_by = Auth::id();
						// 			$reportingToGrade->updated_at = Carbon::now();
						// 		} else {
						// 			$reportingToGrade->created_by_id = Auth::id();
						// 			$reportingToGrade->created_at = Carbon::now();
						// 		}
						// 		$reportingToGrade->save();
						// 	}
						// } else {
						// 	$skip = true;
						// 	$recordErrors[] = 'The reporting to grade is required';
						// }
					} else {
						$skip = true;
						$recordErrors[] = 'The reporting to employee detail is required';
					}

					if (!$skip) {
						//REPORTING TO SAVE
						if ($hrmsEmployee->reporting_to_employee_code) {
							// $reportingToEmployee = Employee::withTrashed()->firstOrNew([
							// 	'company_id' => $reportingToCompanyId,
							// 	'code' => $hrmsEmployee->reporting_to_employee_code,
							// ]);
							// $reportingToEmployee->outlet_id = $reportingToOutlet->id;
							// $reportingToEmployee->grade_id = $reportingToGrade->id;
							// if ($reportingToEmployee->exists) {
							// 	$reportingToEmployee->updated_by = Auth::id();
							// 	$reportingToEmployee->updated_at = Carbon::now();
							// } else {
							// 	$reportingToEmployee->created_by = Auth::id();
							// 	$reportingToEmployee->created_at = Carbon::now();
							// }
							// $reportingToEmployee->save();

							//REPORTING TO USER SAVE
							// $reportingToUser = User::withTrashed()->firstOrNew([
							// 	'entity_id' => $reportingToEmployee->id,
							// 	'user_type_id' => 3121, //EMPLOYEE
							// ]);
							// if ($reportingToUser->exists) {
							// 	$reportingToUser->updated_by = Auth::id();
							// 	$reportingToUser->updated_at = Carbon::now();
							// 	if ($hrmsEmployee->reporting_to_dob) {
							// 		$reportingToUser->password = $hrmsEmployee->reporting_to_dob;
							// 	}
							// } else {
							// 	$reportingToUser->created_by = Auth::id();
							// 	$reportingToUser->created_at = Carbon::now();
							// 	if ($hrmsEmployee->reporting_to_dob) {
							// 		$reportingToUser->password = $hrmsEmployee->reporting_to_dob;
							// 	} else {
							// 		$reportingToUser->password = $reportingToEmployee->code;
							// 	}
							// }
							// $reportingToUser->company_id = $reportingToEmployee->company_id;
							// $reportingToUser->entity_type = 0;
							// $reportingToUser->username = $reportingToEmployee->code;
							// $reportingToUser->name = $hrmsEmployee->reporting_to_employee_name;
							// $reportingToUser->mobile_number = $hrmsEmployee->reporting_to_mobile_number;
							// if ($hrmsEmployee->reporting_to_email) {
							// 	$reportingToUser->email = $hrmsEmployee->reporting_to_email;
							// }
							// $reportingToUser->save();

							$reportingToEmployee = Employee::withTrashed()
								->where('id', $reportingToEmployeeExistId)
								->first();
							if ($reportingToEmployee) {
								$reportingToUser = User::withTrashed()
									->where('entity_id', $reportingToEmployee->id)
									->where('user_type_id', 3121) //EMPLOYEE
									->first();
								if ($reportingToUser) {
									if ($hrmsEmployee->reporting_to_employee_name) {
										$reportingToUser->name = $hrmsEmployee->reporting_to_employee_name;
									}
									if ($hrmsEmployee->reporting_to_email) {
										$reportingToUser->email = $hrmsEmployee->reporting_to_email;
									}
									$reportingToUser->save();
								}
							}
						}

						//EMPLOYEE SAVE
						$employee = Employee::withTrashed()->firstOrNew([
							'company_id' => $companyId,
							'code' => $hrmsEmployee->employee_code,
						]);
						if ($employee->exists) {
							$employee->updated_by = Auth::id();
							$employee->updated_at = Carbon::now();
						} else {
							$employee->created_by = Auth::id();
							$employee->created_at = Carbon::now();
						}
						$employee->outlet_id = $outlet->id;
						if (isset($reportingToEmployee)) {
							$employee->reporting_to_id = $reportingToEmployee->id;
						}
						$employee->grade_id = $grade->id;
						if (isset($designation)) {
							$employee->designation_id = $designation->id;
						}
						if (isset($department)) {
							$employee->department_id = $department->id;
						}
						if ($hrmsEmployee->doj) {
							$employee->date_of_joining = $hrmsEmployee->doj;
						}
						if ($hrmsEmployee->aadhar) {
							$employee->aadhar_no = $hrmsEmployee->aadhar;
						}
						if ($hrmsEmployee->pan) {
							$employee->pan_no = $hrmsEmployee->pan;
						}
						if ($hrmsEmployee->gender_name) {
							$employee->gender = $hrmsEmployee->gender_name;
						}
						if ($hrmsEmployee->dob) {
							$employee->date_of_birth = $hrmsEmployee->dob;
						}
						if (isset($sbu)) {
							$employee->sbu_id = $sbu->id;
						}
						$employee->save();

						//USER SAVE
						$user = User::withTrashed()->firstOrNew([
							'entity_id' => $employee->id,
							'user_type_id' => 3121, //EMPLOYEE
						]);
						if ($user->exists) {
							$user->updated_by = Auth::id();
							$user->updated_at = Carbon::now();
							if ($hrmsEmployee->dob) {
								$user->password = $hrmsEmployee->dob;
							}
						} else {
							$user->created_by = Auth::id();
							$user->created_at = Carbon::now();
							if ($hrmsEmployee->dob) {
								$user->password = $hrmsEmployee->dob;
							} else {
								$user->password = $employee->code;
							}
						}
						$user->company_id = $employee->company_id;
						$user->entity_type = 0;
						$user->username = $employee->code;
						$user->name = $hrmsEmployee->employee_name;
						$user->mobile_number = $hrmsEmployee->mobile_number;
						if ($hrmsEmployee->email) {
							$user->email = $hrmsEmployee->email;
						}
						$user->save();

						$employeeAdditionData = self::hrmsToDemsEmployeeData($employee, 'New Addition');
						$employeeSyncedData[] = self::hrmsToDemsEmployeeData($employee, 'New Addition');

						//EMPLOYEE ADDITION MAIL TO EMPLOYEE, CC REPORTING MANAGE AND OUTLET HR
						if ($employeeAdditionData && $user->email) {
							$toEmail = [$user->email];
							$ccEmail = [];

							if (isset($reportingToUser->email)) {
								$ccEmail[] = $reportingToUser->email;
							}
							if (isset($hrmsEmployee->outlet_hr_email)) {
								$ccEmail[] = $hrmsEmployee->outlet_hr_email;
							}

							$arr = [];
							$arr['from_mail'] = env('MAIL_FROM_ADDRESS', 'travelex@tvs.in');
							$arr['from_name'] = 'DEMS-Admin';
							$arr['to_email'] = $toEmail;
							$arr['cc_email'] = $ccEmail;
							$arr['subject'] = 'Travelex login access enabled';
							$arr['content'] = 'Hi ' . $user->name . ', we have enabled travelex access for your login, please give your ecode with  AD password  to login travelex application.';
							$arr['blade_file'] = 'mail.hrms_to_travelex_employee_report';
							$arr['type'] = 1;
							$arr['employee'] = $employeeAdditionData;
							$mail_instance = new TravelexConfigMail($arr);
							$mail = Mail::send($mail_instance);
						}

					}

					// EMPLOYEE ERROR DETAILS
					if (count($recordErrors) > 0) {
						$employeeErrorReport[] = [
							$hrmsEmployee->employee_code,
							$hrmsEmployee->employee_name,
							implode(',', $recordErrors),
						];
					}
					DB::commit();
				} catch (\Exception $e) {
					DB::rollBack();
					$employeeErrorReport[] = [
						$hrmsEmployee->employee_code,
						$hrmsEmployee->employee_name,
						$e->getMessage() . ' Line: ' . $e->getLine() . ' File: ' . $e->getFile(),
					];
					continue;
				}
			}

			$errorFileName = null;
			if (count($employeeErrorReport) > 0) {
				$timeStamp = date('Y_m_d_h_i_s');
				$excelHeader = [
					'Employee Code',
					'Employee Name',
					'Error',
				];
				$errorFileName = 'emp_addition_error_' . $timeStamp;
				//NEED TO ENABLE
				$file = Excel::create($errorFileName, function ($excel) use ($excelHeader, $employeeErrorReport) {
					$excel->sheet('Errors', function ($sheet) use ($excelHeader, $employeeErrorReport) {
						$sheet->fromArray($employeeErrorReport, NULL, 'A1');
						$sheet->row(1, $excelHeader);
						$sheet->row(1, function ($row) {
							$row->setBackground('#07c63a');
						});
					});
				})->store('xlsx', storage_path('app/public/hrms_to_dems/'));
			}

			//EMPLOYEE ADDITION MAIL
			if (count($employeeSyncedData) > 0) {
				$mailConfig = MailConfiguration::select(
					'to_email',
					'cc_email'
				)
					->where('company_id', $request->company_id)
					->where('config_id', 3941) //HRMS To Travelex Employee Addition Mail
					->first();

				$to = explode(',', $mailConfig->to_email);
				$cc = explode(',', $mailConfig->cc_email);

				$arr = [];
				$arr['from_mail'] = env('MAIL_FROM_ADDRESS', 'travelex@tvs.in');
				$arr['from_name'] = 'DEMS-Admin';
				$arr['to_email'] = $to;
				$arr['cc_email'] = $cc;
				$arr['subject'] = 'Employee Addition';
				$arr['content'] = 'The below employee please create in Axapta vendor master immediately';
				$arr['blade_file'] = 'mail.hrms_to_travelex_employee_report';
				$arr['type'] = 2;
				$arr['employee_details'] = $employeeSyncedData;
				$mail_instance = new TravelexConfigMail($arr);
				$mail = Mail::send($mail_instance);
			}

			$employeeSyncLog = new HrmsToTravelxEmployeeSyncLog();
			$employeeSyncLog->company_id = $request->company_id;
			$employeeSyncLog->type_id = 3961; //EMPLOYEE ADDITION
			$employeeSyncLog->from_date_time = $fromDateTime;
			$employeeSyncLog->to_date_time = $toDateTime;
			$employeeSyncLog->new_count = count($employeeSyncedData);
			$employeeSyncLog->created_by_id = Auth::id();
			if ($errorFileName) {
				$employeeSyncLog->error_file = 'storage/app/public/hrms_to_dems/' . $errorFileName . '.xlsx';
			}
			$employeeSyncLog->save();

			return response()->json([
				'success' => true,
				'message' => ['New employees synced successfully'],
			]);
		}
	}

	public static function hrmsEmployeeUpdationSync($request) {
		$hrmsPortalConfig = config('custom.HRMS_PORTAL_CONFIG');
		if ($hrmsPortalConfig == true) {
			$hrmsPortal = DB::table('portals')->select([
				'db_host_name',
				'db_port_number',
				'db_name',
				'db_user_name',
				'db_password',
			])
				->where('id', 2) //HRMS
				->first();

			$dbHostName = $hrmsPortal->db_host_name;
			$dbPortNumber = $hrmsPortal->db_port_number;
			$dbPortDriver = 'mysql';
			$dbName = $hrmsPortal->db_name;
			$dbUserName = $hrmsPortal->db_user_name;
			$dbPassword = $hrmsPortal->db_password;
		} else {
			$dbHostName = config('custom.HRMS_DB_HOST');
			$dbPortNumber = config('custom.HRMS_DB_PORT_NUMBER');
			$dbPortDriver = 'mysql';
			$dbName = config('custom.HRMS_DB_NAME');
			$dbUserName = config('custom.HRMS_DB_USER_NAME');
			$dbPassword = config('custom.HRMS_DB_PASSWORD');
		}

		$employeeUpdationExistLog = HrmsToTravelxEmployeeSyncLog::select([
			'id',
			'from_date_time',
			'to_date_time',
		])
			->where('company_id', $request->company_id)
			->where('type_id', 3962) //EMPLOYEE UPDATION
			->orderBy('id', 'DESC')
			->first();
		if ($employeeUpdationExistLog) {
			$fromDateTime = $employeeUpdationExistLog->to_date_time;
			$toDateTime = date("Y-m-d H:i:s");
		} else {
			$fromDateTime = date('Y-m-d') . ' 00:00:00';
			$toDateTime = date("Y-m-d H:i:s");
		}

		DB::setDefaultConnection('dynamic');
		dataBaseConfig::set('database.connections.dynamic.host', $dbHostName);
		dataBaseConfig::set('database.connections.dynamic.port', $dbPortNumber);
		dataBaseConfig::set('database.connections.dynamic.driver', $dbPortDriver);
		dataBaseConfig::set('database.connections.dynamic.database', $dbName);
		dataBaseConfig::set('database.connections.dynamic.username', $dbUserName);
		dataBaseConfig::set('database.connections.dynamic.password', $dbPassword);
		DB::purge('dynamic');
		DB::reconnect('dynamic');

		$hrmsCompanyId = DB::table('companies')
			->where('adre_code', $request->company_code)
			->pluck('id')
			->first();
		if (!$hrmsCompanyId) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['The logined user company not found in HRMS'],
			]);
		}

		$transferRequests = DB::table('transfer_requests')->select([
			'transfer_requests.id',
			'employees.id as employee_id',
			'transfer_requests.outlet_id',
			'transfer_requests.grade_id',
			'transfer_requests.designation_id',
			'transfer_requests.function_id',
			'transfer_requests.updated_at',
		])
			->join('employees', 'employees.id', 'transfer_requests.employee_id')
			->where('employees.company_id', $hrmsCompanyId)
			->where('transfer_requests.status_id', 4) //APPROVED
			->whereBetween('transfer_requests.updated_at', [$fromDateTime, $toDateTime])
			->get();

		if (count($transferRequests) == 0) {
			$formattedFromDateTime = date('d/m/Y h:i A', strtotime($fromDateTime));
			$formattedToDateTime = date('d/m/Y h:i A', strtotime($toDateTime));

			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Employee transfer requests not found for this period : ' . $formattedFromDateTime . ' to ' . $formattedToDateTime],
			]);
		}
		$employeeTransferRequests = collect($transferRequests)->groupBy('employee_id');

		//GETTING EMPLOYEE OUTLET,GRADE,DESIGNATION,FUNCTION CHANGE DETAILS
		$employeeUpdateDetails = [];
		foreach ($employeeTransferRequests as $employeeId => $employeeTransferRequest) {
			$employeeTransferRequest = collect($employeeTransferRequest)->sortByDesc('updated_at');
			$outletIds = array_filter($employeeTransferRequest->pluck('outlet_id')->toArray());
			$outletId = null;
			if (count($outletIds) > 0) {
				$outletId = array_shift($outletIds);
			}

			$gradeIds = array_filter($employeeTransferRequest->pluck('grade_id')->toArray());
			$gradeId = null;
			if (count($gradeIds) > 0) {
				$gradeId = array_shift($gradeIds);
			}

			$designationIds = array_filter($employeeTransferRequest->pluck('designation_id')->toArray());
			$designationId = null;
			if (count($designationIds) > 0) {
				$designationId = array_shift($designationIds);
			}

			$functionIds = array_filter($employeeTransferRequest->pluck('function_id')->toArray());
			$functionId = null;
			if (count($functionIds) > 0) {
				$functionId = array_shift($functionIds);
			}

			//IF NO TRANSFER WAS FOUND
			if (!$outletId && !$gradeId && !$designationId && !$functionId) {
				continue;
			}

			$hrmsEmployee = DB::table('employees')->select([
				'employees.id',
				'employees.code as employee_code',
				'employees.name as employee_name',
				'employees.lob_id',
				'employee_companies.adre_code as employee_company_adre_code',
				'lob_companies.adre_code as lob_company_adre_code',
				'lobs.code as lob_code',
				'sbus.code as sbu_code',
			])
				->join('companies as employee_companies', 'employee_companies.id', 'employees.company_id')
				->leftjoin('lobs', 'lobs.id', 'employees.lob_id')
				->leftjoin('companies as lob_companies', 'lob_companies.id', 'lobs.company_id')
				->leftjoin('sbus', 'sbus.id', 'employees.sbu_id')
				->where('employees.id', $employeeId)
				->first();

			//CHECK EMPLOYEE LOB IS NOT DLOB, OESL
			if (!in_array($hrmsEmployee->lob_id, [4, 15])) {
				continue;
			}

			$hrmsOutlet = DB::table('outlets')->select([
				'outlets.id',
				'outlets.code as outlet_code',
				'outlets.name as outlet_name',
				'outlet_companies.adre_code as outlet_company_adre_code',
			])
				->join('companies as outlet_companies', 'outlet_companies.id', 'outlets.company_id')
				->where('outlets.id', $outletId)
				->first();

			$hrmsGrade = DB::table('grades')->select([
				'grades.id',
				'grades.code as grade_code',
				'grade_companies.adre_code as grade_company_adre_code',
			])
				->join('companies as grade_companies', 'grade_companies.id', 'grades.company_id')
				->where('grades.id', $gradeId)
				->first();

			$hrmsDesignation = DB::table('designations')->select([
				'designations.id',
				'designations.code as designation_code',
				'designation_companies.adre_code as designation_company_adre_code',
			])
				->join('companies as designation_companies', 'designation_companies.id', 'designations.company_id')
				->where('designations.id', $designationId)
				->first();

			$hrmsFunction = DB::table('funcs')->select([
				'funcs.id',
				'funcs.name as function_name',
				'function_companies.adre_code as function_company_adre_code',
			])
				->join('companies as function_companies', 'function_companies.id', 'funcs.company_id')
				->where('funcs.id', $functionId)
				->first();

			$employeeUpdateDetails[] = [
				'employee' => $hrmsEmployee,
				'outlet' => $hrmsOutlet,
				'grade' => $hrmsGrade,
				'designation' => $hrmsDesignation,
				'function' => $hrmsFunction,
			];
		}

		if (count($employeeUpdateDetails) == 0) {
			$formattedFromDateTime = date('d/m/Y h:i A', strtotime($fromDateTime));
			$formattedToDateTime = date('d/m/Y h:i A', strtotime($toDateTime));

			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Employee transfer requests not found for this period : ' . $formattedFromDateTime . ' to ' . $formattedToDateTime],
			]);
		}

		DB::setDefaultConnection('mysql');
		if (count($employeeUpdateDetails) > 0) {
			$employeeSyncedData = [];
			$syncErrors = [];
			$employeeUpdateCount = 0;
			foreach ($employeeUpdateDetails as $employeeUpdateDetail) {
				DB::beginTransaction();
				try {
					$skip = false;
					$recordErrors = [];

					//EMPLOYEE COMPANY
					if (!$employeeUpdateDetail['employee']->employee_company_adre_code) {
						$skip = true;
						$recordErrors[] = 'The employee company is required';
					} else {
						$companyId = Company::where('code', $employeeUpdateDetail['employee']->employee_company_adre_code)->pluck('id')->first();
						if (!$companyId) {
							$skip = true;
							$recordErrors[] = 'The employee company not found in travelex';
						} else {
							$employeeExistId = Employee::withTrashed()
								->where('company_id', $companyId)
								->where('code', $employeeUpdateDetail['employee']->employee_code)
								->pluck('id')
								->first();
							if (!$employeeExistId) {
								$skip = true;
								$recordErrors[] = 'The employee detail not found in travelex';
							}
						}
					}

					//EMPLOYEE OUTLET
					if (!empty($employeeUpdateDetail['outlet'])) {
						$outletCompanyId = Company::where('code', $employeeUpdateDetail['outlet']->outlet_company_adre_code)->pluck('id')->first();
						if (!$outletCompanyId) {
							$skip = true;
							$recordErrors[] = 'The employee outlet company not found in travelex';
						} else {
							$outlet = Outlet::withTrashed()->firstOrNew([
								'company_id' => $outletCompanyId,
								'code' => $employeeUpdateDetail['outlet']->outlet_code,
							]);
							if ($outlet->exists) {
								$outlet->updated_by = Auth::id();
								$outlet->updated_at = Carbon::now();
							} else {
								$outlet->created_by = Auth::id();
								$outlet->created_at = Carbon::now();
							}
							$outlet->name = $employeeUpdateDetail['outlet']->outlet_name;
							$outlet->save();
						}
					}

					//EMPLOYEE GRADE
					if (!empty($employeeUpdateDetail['grade'])) {
						$gradeCompanyId = Company::where('code', $employeeUpdateDetail['grade']->grade_company_adre_code)->pluck('id')->first();
						if (!$gradeCompanyId) {
							$skip = true;
							$recordErrors[] = 'The employee grade company not found in travelex';
						} else {
							$grade = Entity::withTrashed()->firstOrNew([
								'company_id' => $gradeCompanyId,
								'entity_type_id' => 500, //GRADE
								'name' => $employeeUpdateDetail['grade']->grade_code,
							]);
							if ($grade->exists) {
								$grade->updated_by = Auth::id();
								$grade->updated_at = Carbon::now();
							} else {
								$grade->created_by = Auth::id();
								$grade->created_at = Carbon::now();
							}
							$grade->save();

							//EMPLOYEE DESIGNATION
							if (!empty($employeeUpdateDetail['designation'])) {
								$designationCompanyId = Company::where('code', $employeeUpdateDetail['designation']->designation_company_adre_code)->pluck('id')->first();
								if (!$designationCompanyId) {
									$skip = true;
									$recordErrors[] = 'The employee designation company not found in travelex';
								} else {
									$designation = Designation::withTrashed()->firstOrNew([
										'company_id' => $designationCompanyId,
										'name' => $employeeUpdateDetail['designation']->designation_code,
										'grade_id' => $grade->id,
									]);
									if ($designation->exists) {
										$designation->updated_by = Auth::id();
										$designation->updated_at = Carbon::now();
									} else {
										$designation->created_by = Auth::id();
										$designation->created_at = Carbon::now();
									}
									$designation->save();
								}
							}
						}
					}

					//EMLOYEE LOB & SBU
					if (!empty($employeeUpdateDetail['employee']->lob_code)) {
						$lobCompanyId = Company::where('code', $employeeUpdateDetail['employee']->lob_company_adre_code)->pluck('id')->first();
						if (!$lobCompanyId) {
							$skip = true;
							$recordErrors[] = 'The employee lob company not found in travelex';
						} else {
							$lob = Lob::firstOrNew([
								'company_id' => $lobCompanyId,
								'name' => $employeeUpdateDetail['employee']->lob_code,
							]);
							if ($lob->exists) {
								$lob->updated_by = Auth::id();
								$lob->updated_at = Carbon::now();
							} else {
								$lob->created_by = Auth::id();
								$lob->created_at = Carbon::now();
							}
							$lob->save();

							if (!empty($employeeUpdateDetail['employee']->sbu_code)) {
								$sbu = Sbu::firstOrNew([
									'lob_id' => $lob->id,
									'name' => $employeeUpdateDetail['employee']->sbu_code,
								]);
								if ($sbu->exists) {
									$sbu->updated_by = Auth::id();
									$sbu->updated_at = Carbon::now();
								} else {
									$sbu->created_by = Auth::id();
									$sbu->created_at = Carbon::now();
								}
								$sbu->save();

								//EMPLOYEE DEPARTMENT
								$businessId = null;
								if ($employeeUpdateDetail['employee']->lob_code == 'DLOB') {
									if (strpos($employeeUpdateDetail['employee']->sbu_code, 'honda') !== false) {
										$businessId = 3; //HONDA
									} else {
										$businessId = 1; //DLOB
									}
								} else if ($employeeUpdateDetail['employee']->lob_code == 'OESL') {
									$businessId = 2; //OESL
								}

								if ($businessId && !empty($employeeUpdateDetail['function']->function_name)) {
									$funcCompanyId = Company::where('code', $employeeUpdateDetail['function']->function_company_adre_code)->pluck('id')->first();
									if (!$funcCompanyId) {
										$skip = true;
										$recordErrors[] = 'The employee function company not found in travelex';
									} else {
										$department = Department::withTrashed()->firstOrNew([
											'company_id' => $funcCompanyId,
											'business_id' => $businessId,
											'name' => $employeeUpdateDetail['function']->function_name,
										]);
										if ($department->exists) {
											$department->updated_by = Auth::id();
											$department->updated_at = Carbon::now();
										} else {
											$department->created_by = Auth::id();
											$department->created_at = Carbon::now();
											$department->short_name = $employeeUpdateDetail['function']->function_name;
										}
										$department->save();
									}
								}
							} else {
								$skip = true;
								$recordErrors[] = 'The employee sbu is required';
							}
						}
					}

					if (!$skip) {
						//EMPLOYEE UPDATE
						$employee = Employee::withTrashed()
							->where('company_id', $companyId)
							->where('code', $employeeUpdateDetail['employee']->employee_code)
							->first();
						if ($employee) {
							if (isset($outlet)) {
								$employee->outlet_id = $outlet->id;
							}
							if (isset($grade)) {
								$employee->grade_id = $grade->id;
							}
							if (isset($designation)) {
								$employee->designation_id = $designation->id;
							}
							if (isset($department)) {
								$employee->department_id = $department->id;
							}
							$employee->save();

							if (isset($outlet)) {
								$employeeSyncedData[] = self::hrmsToDemsEmployeeData($employee, 'Outlet Change');
							}
							if (isset($grade)) {
								$employeeSyncedData[] = self::hrmsToDemsEmployeeData($employee, 'Grade Change');
							}
							if (isset($designation)) {
								$employeeSyncedData[] = self::hrmsToDemsEmployeeData($employee, 'Designation Change');
							}
							if (isset($department)) {
								$employeeSyncedData[] = self::hrmsToDemsEmployeeData($employee, 'Function Change');
							}

							//UPDATE COUNT
							if (isset($outlet) || isset($grade) || isset($designation) || isset($department)) {
								$employeeUpdateCount++;
							}
						}
					}

					//SYNC ERROR DETAILS
					if (count($recordErrors) > 0) {
						$syncErrors[] = [
							$employeeUpdateDetail['employee']->employee_code,
							$employeeUpdateDetail['employee']->employee_name,
							implode(',', $recordErrors),
						];
					}
					DB::commit();
				} catch (\Exception $e) {
					DB::rollBack();
					$syncErrors[] = [
						$employeeUpdateDetail['employee']->employee_code,
						$employeeUpdateDetail['employee']->employee_name,
						$e->getMessage() . ' Line: ' . $e->getLine() . ' File: ' . $e->getFile(),
					];
					continue;
				}
			}

			$errorFileName = null;
			if (count($syncErrors) > 0) {
				$timeStamp = date('Y_m_d_h_i_s');
				$excelHeader = [
					'Employee Code',
					'Employee Name',
					'Error',
				];
				$errorFileName = 'emp_updation_error_' . $timeStamp;
				//NEED TO ENABLE
				$file = Excel::create($errorFileName, function ($excel) use ($excelHeader, $syncErrors) {
					$excel->sheet('Errors', function ($sheet) use ($excelHeader, $syncErrors) {
						$sheet->fromArray($syncErrors, NULL, 'A1');
						$sheet->row(1, $excelHeader);
						$sheet->row(1, function ($row) {
							$row->setBackground('#07c63a');
						});
					});
				})->store('xlsx', storage_path('app/public/hrms_to_dems/'));
			}

			//EMPLOYEE UPDATION MAIL
			if (count($employeeSyncedData) > 0) {
				$mailConfig = MailConfiguration::select(
					'to_email',
					'cc_email'
				)
					->where('company_id', $request->company_id)
					->where('config_id', 3942) //HRMS To Travelex Employee Change Mail
					->first();

				$to = explode(',', $mailConfig->to_email);
				$cc = explode(',', $mailConfig->cc_email);

				$arr['from_mail'] = env('MAIL_FROM_ADDRESS', 'travelex@tvs.in');
				$arr['from_name'] = 'DEMS-Admin';
				$arr['to_email'] = $to;
				$arr['cc_email'] = $cc;
				$arr['subject'] = 'Employee Update';
				$arr['content'] = 'The below employee changes has been made in travelex system FYI.';
				$arr['blade_file'] = 'mail.hrms_to_travelex_employee_report';
				$arr['type'] = 2;
				$arr['employee_details'] = $employeeSyncedData;
				$mail_instance = new TravelexConfigMail($arr);
				$mail = Mail::send($mail_instance);
			}

			$employeeSyncLog = new HrmsToTravelxEmployeeSyncLog();
			$employeeSyncLog->company_id = $request->company_id;
			$employeeSyncLog->type_id = 3962; //EMPLOYEE UPDATION
			$employeeSyncLog->from_date_time = $fromDateTime;
			$employeeSyncLog->to_date_time = $toDateTime;
			$employeeSyncLog->update_count = $employeeUpdateCount;
			$employeeSyncLog->created_by_id = Auth::id();
			if ($errorFileName) {
				$employeeSyncLog->error_file = 'storage/app/public/hrms_to_dems/' . $errorFileName . '.xlsx';
			}
			$employeeSyncLog->save();

			return response()->json([
				'success' => true,
				'message' => ['Employees updation synced successfully'],
			]);
		}
	}

	public static function hrmsEmployeeDeletionSync($request) {
		// dd($request->all());
		$hrmsPortalConfig = config('custom.HRMS_PORTAL_CONFIG');
		if ($hrmsPortalConfig == true) {
			$hrmsPortal = DB::table('portals')->select([
				'db_host_name',
				'db_port_number',
				'db_name',
				'db_user_name',
				'db_password',
			])
				->where('id', 2) //HRMS
				->first();

			$dbHostName = $hrmsPortal->db_host_name;
			$dbPortNumber = $hrmsPortal->db_port_number;
			$dbPortDriver = 'mysql';
			$dbName = $hrmsPortal->db_name;
			$dbUserName = $hrmsPortal->db_user_name;
			$dbPassword = $hrmsPortal->db_password;
		} else {
			$dbHostName = config('custom.HRMS_DB_HOST');
			$dbPortNumber = config('custom.HRMS_DB_PORT_NUMBER');
			$dbPortDriver = 'mysql';
			$dbName = config('custom.HRMS_DB_NAME');
			$dbUserName = config('custom.HRMS_DB_USER_NAME');
			$dbPassword = config('custom.HRMS_DB_PASSWORD');
		}

		$employeeDeletionExistLog = HrmsToTravelxEmployeeSyncLog::select([
			'id',
			'from_date_time',
			'to_date_time',
		])
			->where('company_id', $request->company_id)
			->where('type_id', 3963) //EMPLOYEE DELETION
			->orderBy('id', 'DESC')
			->first();
		if ($employeeDeletionExistLog) {
			$fromDateTime = $employeeDeletionExistLog->to_date_time;
			$toDateTime = date("Y-m-d H:i:s");
		} else {
			$fromDateTime = date('Y-m-d') . ' 00:00:00';
			$toDateTime = date("Y-m-d H:i:s");
		}

		DB::setDefaultConnection('dynamic');
		dataBaseConfig::set('database.connections.dynamic.host', $dbHostName);
		dataBaseConfig::set('database.connections.dynamic.port', $dbPortNumber);
		dataBaseConfig::set('database.connections.dynamic.driver', $dbPortDriver);
		dataBaseConfig::set('database.connections.dynamic.database', $dbName);
		dataBaseConfig::set('database.connections.dynamic.username', $dbUserName);
		dataBaseConfig::set('database.connections.dynamic.password', $dbPassword);
		DB::purge('dynamic');
		DB::reconnect('dynamic');
		$hrmsCompanyId = DB::table('companies')
			->where('adre_code', $request->company_code)
			->pluck('id')
			->first();
		if (!$hrmsCompanyId) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['The logined user company not found in HRMS'],
			]);
		}

		$hrmsDeletionEmployees = DB::table('employees')->select([
			'employees.id',
			'employees.code as employee_code',
			'employees.name as employee_name',
			'employees.lob_id',
			'companies.adre_code as employee_company_adre_code',
			'employees.deleted_at',
		])
			->join('companies', 'companies.id', 'employees.company_id')
			->where('employees.company_id', $hrmsCompanyId)
			->whereNotNull('employees.deleted_at')
			->whereBetween('employees.deleted_at', [$fromDateTime, $toDateTime])
			->whereIn('employees.lob_id', [4, 15]) //DLOB, OESL
			->get();

		if (count($hrmsDeletionEmployees) == 0) {
			$formattedFromDateTime = date('d/m/Y h:i A', strtotime($fromDateTime));
			$formattedToDateTime = date('d/m/Y h:i A', strtotime($toDateTime));

			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Employee deletion details not found for this period : ' . $formattedFromDateTime . ' to ' . $formattedToDateTime],
			]);
		}

		DB::setDefaultConnection('mysql');
		if (count($hrmsDeletionEmployees) > 0) {
			$employeeSyncedData = [];
			$employeeErrorReport = [];
			foreach ($hrmsDeletionEmployees as $hrmsDeletionEmployee) {
				DB::beginTransaction();
				try {
					$skip = false;
					$recordErrors = [];

					//CHECK EMPLOYEE LOB IS NOT DLOB, OESL
					// if (!in_array($hrmsDeletionEmployee->lob_id, [4, 15])) {
					// 	continue;
					// }

					//EMPLOYEE COMPANY
					if (!$hrmsDeletionEmployee->employee_company_adre_code) {
						$skip = true;
						$recordErrors[] = 'The employee company is required';
					} else {
						$companyId = Company::where('code', $hrmsDeletionEmployee->employee_company_adre_code)->pluck('id')->first();
						if (!$companyId) {
							$skip = true;
							$recordErrors[] = 'The employee company not found in travelex';
						} else {
							$employeeExistId = Employee::withTrashed()
								->where('company_id', $companyId)
								->where('code', $hrmsDeletionEmployee->employee_code)
								->pluck('id')
								->first();
							if (!$employeeExistId) {
								$skip = true;
								$recordErrors[] = 'The employee detail not found in travelex';
							}
						}
					}

					if (!$skip) {
						$employee = Employee::withTrashed()
							->where('company_id', $companyId)
							->where('code', $hrmsDeletionEmployee->employee_code)
							->first();
						if ($employee) {
							$employee->deleted_at = Carbon::now();
							$employee->deleted_by = Auth::id();
							$employee->save();

							$user = User::withTrashed()
								->where('entity_id', $employee->id)
								->where('user_type_id', 3121) //EMPLOYEE
								->first();
							if ($user) {
								$user->deleted_at = Carbon::now();
								$user->deleted_by = Auth::id();
								$user->save();
							}
							$employeeSyncedData[] = self::hrmsToDemsEmployeeData($employee, 'Deletion');
						}
					}

					// EMPLOYEE ERROR DETAILS
					if (count($recordErrors) > 0) {
						$employeeErrorReport[] = [
							$hrmsDeletionEmployee->employee_code,
							$hrmsDeletionEmployee->employee_name,
							implode(',', $recordErrors),
						];
					}
					DB::commit();
				} catch (\Exception $e) {
					DB::rollBack();
					$employeeErrorReport[] = [
						$hrmsDeletionEmployee->employee_code,
						$hrmsDeletionEmployee->employee_name,
						$e->getMessage() . ' Line: ' . $e->getLine() . ' File: ' . $e->getFile(),
					];
					continue;
				}
			}

			$errorFileName = null;
			// dump($employeeErrorReport);
			if (count($employeeErrorReport) > 0) {
				$timeStamp = date('Y_m_d_h_i_s');
				$excelHeader = [
					'Employee Code',
					'Employee Name',
					'Error',
				];
				$errorFileName = 'emp_deletion_error_' . $timeStamp;
				//NEED TO ENABLE
				$file = Excel::create($errorFileName, function ($excel) use ($excelHeader, $employeeErrorReport) {
					$excel->sheet('Errors', function ($sheet) use ($excelHeader, $employeeErrorReport) {
						$sheet->fromArray($employeeErrorReport, NULL, 'A1');
						$sheet->row(1, $excelHeader);
						$sheet->row(1, function ($row) {
							$row->setBackground('#07c63a');
						});
					});
				})->store('xlsx', storage_path('app/public/hrms_to_dems/'));
			}

			//EMPLOYEE DELETION MAIL
			if (count($employeeSyncedData) > 0) {
				$mailConfig = MailConfiguration::select(
					'to_email',
					'cc_email'
				)
					->where('company_id', $request->company_id)
					->where('config_id', 3943) //HRMS To Travelex Employee Deletion Mail
					->first();

				$to = explode(',', $mailConfig->to_email);
				$cc = explode(',', $mailConfig->cc_email);

				$arr = [];
				$arr['from_mail'] = env('MAIL_FROM_ADDRESS', 'travelex@tvs.in');
				$arr['from_name'] = 'DEMS-Admin';
				$arr['to_email'] = $to;
				$arr['cc_email'] = $cc;
				$arr['subject'] = 'Employee Deletion';
				$arr['content'] = 'The below employees travelex login access has been disabled please deactivate the same set of employees in Axapta vendor master.';
				$arr['blade_file'] = 'mail.hrms_to_travelex_employee_report';
				$arr['type'] = 2;
				$arr['employee_details'] = $employeeSyncedData;
				$mail_instance = new TravelexConfigMail($arr);
				$mail = Mail::send($mail_instance);
			}

			$employeeSyncLog = new HrmsToTravelxEmployeeSyncLog();
			$employeeSyncLog->company_id = $request->company_id;
			$employeeSyncLog->type_id = 3963; //EMPLOYEE ADDITION
			$employeeSyncLog->from_date_time = $fromDateTime;
			$employeeSyncLog->to_date_time = $toDateTime;
			$employeeSyncLog->delete_count = count($employeeSyncedData);
			$employeeSyncLog->created_by_id = Auth::id();
			if ($errorFileName) {
				$employeeSyncLog->error_file = 'storage/app/public/hrms_to_dems/' . $errorFileName . '.xlsx';
			}
			$employeeSyncLog->save();

			return response()->json([
				'success' => true,
				'message' => ['Employee deletion synced successfully'],
			]);
		}
	}

	public static function hrmsEmployeeReportingToSync($request) {
		$hrmsPortalConfig = config('custom.HRMS_PORTAL_CONFIG');
		if ($hrmsPortalConfig == true) {
			$hrmsPortal = DB::table('portals')->select([
				'db_host_name',
				'db_port_number',
				'db_name',
				'db_user_name',
				'db_password',
			])
				->where('id', 2) //HRMS
				->first();

			$dbHostName = $hrmsPortal->db_host_name;
			$dbPortNumber = $hrmsPortal->db_port_number;
			$dbPortDriver = 'mysql';
			$dbName = $hrmsPortal->db_name;
			$dbUserName = $hrmsPortal->db_user_name;
			$dbPassword = $hrmsPortal->db_password;
		} else {
			$dbHostName = config('custom.HRMS_DB_HOST');
			$dbPortNumber = config('custom.HRMS_DB_PORT_NUMBER');
			$dbPortDriver = 'mysql';
			$dbName = config('custom.HRMS_DB_NAME');
			$dbUserName = config('custom.HRMS_DB_USER_NAME');
			$dbPassword = config('custom.HRMS_DB_PASSWORD');
		}

		$employeeUpdationExistLog = HrmsToTravelxEmployeeSyncLog::select([
			'id',
			'from_date_time',
			'to_date_time',
		])
			->where('company_id', $request->company_id)
			->where('type_id', 3964) //EMPLOYEE REPORTING UPDATION
			->orderBy('id', 'DESC')
			->first();
		if ($employeeUpdationExistLog) {
			$fromDateTime = $employeeUpdationExistLog->to_date_time;
			$toDateTime = date("Y-m-d H:i:s");
		} else {
			$fromDateTime = date('Y-m-d') . ' 00:00:00';
			$toDateTime = date("Y-m-d H:i:s");
		}

		DB::setDefaultConnection('dynamic');
		dataBaseConfig::set('database.connections.dynamic.host', $dbHostName);
		dataBaseConfig::set('database.connections.dynamic.port', $dbPortNumber);
		dataBaseConfig::set('database.connections.dynamic.driver', $dbPortDriver);
		dataBaseConfig::set('database.connections.dynamic.database', $dbName);
		dataBaseConfig::set('database.connections.dynamic.username', $dbUserName);
		dataBaseConfig::set('database.connections.dynamic.password', $dbPassword);
		DB::purge('dynamic');
		DB::reconnect('dynamic');

		$hrmsCompanyId = DB::table('companies')
			->where('adre_code', $request->company_code)
			->pluck('id')
			->first();
		if (!$hrmsCompanyId) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['The logined user company not found in HRMS'],
			]);
		}

		$employeeReportingDetails = DB::table('employees')->select([
			'employees.id',
			'employees.code as employee_code',
			'employees.name as employee_name',
			'employees.lob_id',
			'companies.adre_code',
			'reporting_to_companies.adre_code as reporting_to_company_adre_code',
			'reporting_to_employees.code as reporting_to_employee_code',
			'reporting_to_employees.name as reporting_to_employee_name',
		])
			->join('companies', 'companies.id', 'employees.company_id')
			->join('employees as reporting_to_employees', 'reporting_to_employees.id', 'employees.reporting_to_id')
			->leftjoin('companies as reporting_to_companies', 'reporting_to_companies.id', 'reporting_to_employees.company_id')
			->where('employees.company_id', $hrmsCompanyId)
			->whereBetween('employees.updated_at', [$fromDateTime, $toDateTime])
			->whereIn('employees.lob_id', [4, 15]) //DLOB, OESL
			->whereNotNull('employees.reporting_to_id')
			->get();

		if (count($employeeReportingDetails) == 0) {
			$formattedFromDateTime = date('d/m/Y h:i A', strtotime($fromDateTime));
			$formattedToDateTime = date('d/m/Y h:i A', strtotime($toDateTime));

			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Employee reporting to updates not found for this period : ' . $formattedFromDateTime . ' to ' . $formattedToDateTime],
			]);
		}

		DB::setDefaultConnection('mysql');
		if (count($employeeReportingDetails) > 0) {
			$employeeSyncedData = [];
			$employeeErrorReport = [];
			foreach (collect($employeeReportingDetails) as $employeeReportingDetail) {
				DB::beginTransaction();
				try {
					$skip = false;
					$recordErrors = [];

					//CHECK EMPLOYEE LOB IS NOT DLOB, OESL
					// if (!in_array($employeeReportingDetail->lob_id, [4, 15])) {
					// 	continue;
					// }

					//EMPLOYEE COMPANY
					if (!$employeeReportingDetail->adre_code) {
						$skip = true;
						$recordErrors[] = 'The employee company is required';
					} else {
						$companyId = Company::where('code', $employeeReportingDetail->adre_code)->pluck('id')->first();
						if (!$companyId) {
							$skip = true;
							$recordErrors[] = 'The employee company not found in travelex';
						} else {
							$employeeExistId = Employee::withTrashed()
								->where('company_id', $companyId)
								->where('code', $employeeReportingDetail->employee_code)
								->pluck('id')
								->first();
							if (!$employeeExistId) {
								$skip = true;
								$recordErrors[] = 'The employee detail not available in travelex';
							}
						}
					}

					//EMPLOYEE REPORTING TO DETAILS
					$reportingToCompanyId = Company::where('code', $employeeReportingDetail->reporting_to_company_adre_code)->pluck('id')->first();
					if (!$reportingToCompanyId) {
						$skip = true;
						$recordErrors[] = 'The reporting to employee company not found in travelex';
					} else {
						$reportingEmployeeExistId = Employee::withTrashed()
							->where('company_id', $reportingToCompanyId)
							->where('code', $employeeReportingDetail->reporting_to_employee_code)
							->pluck('id')
							->first();
						if (!$reportingEmployeeExistId) {
							$skip = true;
							$recordErrors[] = 'The reporting employee not available in travelex';
						}
					}
					if (!$skip) {
						//REPORTING TO NAME UPDATE
						$reportingToUser = User::withTrashed()
							->where('entity_id', $reportingEmployeeExistId)
							->where('user_type_id', 3121) //EMPLOYEE
							->first();
						if ($reportingToUser && $employeeReportingDetail->reporting_to_employee_name) {
							$reportingToUser->name = $employeeReportingDetail->reporting_to_employee_name;
							$reportingToUser->save();
						}

						//EMPLOYEE REPORTING TO UPDATE
						$employee = Employee::withTrashed()->where('id', $employeeExistId)->first();
						if ($employee) {
							$employeeExistReportingToId = $employee->reporting_to_id;
							$employee->reporting_to_id = $reportingEmployeeExistId;
							$employee->updated_by = Auth::id();
							$employee->updated_at = Carbon::now();
							$employee->save();
							//CHECK REPORTING MANAGER IS CHANGED
							if ($employeeExistReportingToId != $reportingEmployeeExistId) {
								$employeeSyncedData[] = self::hrmsToDemsEmployeeData($employee, 'Reporting To Change');
							}
						}
					}

					// EMPLOYEE ERROR DETAILS
					if (count($recordErrors) > 0) {
						$employeeErrorReport[] = [
							$employeeReportingDetail->employee_code,
							$employeeReportingDetail->employee_name,
							implode(',', $recordErrors),
						];
					}
					DB::commit();
				} catch (\Exception $e) {
					DB::rollBack();
					$employeeErrorReport[] = [
						$employeeReportingDetail->employee_code,
						$employeeReportingDetail->employee_name,
						$e->getMessage() . ' Line: ' . $e->getLine() . ' File: ' . $e->getFile(),
					];
					continue;
				}
			}

			$errorFileName = null;
			if (count($employeeErrorReport) > 0) {
				$timeStamp = date('Y_m_d_h_i_s');
				$excelHeader = [
					'Employee Code',
					'Employee Name',
					'Error',
				];
				$errorFileName = 'emp_reporting_to_error_' . $timeStamp;
				//NEED TO ENABLE
				$file = Excel::create($errorFileName, function ($excel) use ($excelHeader, $employeeErrorReport) {
					$excel->sheet('Errors', function ($sheet) use ($excelHeader, $employeeErrorReport) {
						$sheet->fromArray($employeeErrorReport, NULL, 'A1');
						$sheet->row(1, $excelHeader);
						$sheet->row(1, function ($row) {
							$row->setBackground('#07c63a');
						});
					});
				})->store('xlsx', storage_path('app/public/hrms_to_dems/'));
			}

			//EMPLOYEE UPDATION MAIL
			if (count($employeeSyncedData) > 0) {
				$mailConfig = MailConfiguration::select(
					'to_email',
					'cc_email'
				)
					->where('company_id', $request->company_id)
					->where('config_id', 3944) //HRMS To Travelex Employee Reporting To Change Mail
					->first();

				$to = explode(',', $mailConfig->to_email);
				$cc = explode(',', $mailConfig->cc_email);

				$arr['from_mail'] = env('MAIL_FROM_ADDRESS', 'travelex@tvs.in');
				$arr['from_name'] = 'DEMS-Admin';
				$arr['to_email'] = $to;
				$arr['cc_email'] = $cc;
				$arr['subject'] = 'Employee Update';
				$arr['content'] = 'The below employee changes has been made in travelex system FYI.';
				$arr['blade_file'] = 'mail.hrms_to_travelex_employee_report';
				$arr['type'] = 2;
				$arr['employee_details'] = $employeeSyncedData;
				$mail_instance = new TravelexConfigMail($arr);
				$mail = Mail::send($mail_instance);
			}

			$employeeSyncLog = new HrmsToTravelxEmployeeSyncLog();
			$employeeSyncLog->company_id = $request->company_id;
			$employeeSyncLog->type_id = 3964; //EMPLOYEE REPORTING TO CHANGE
			$employeeSyncLog->from_date_time = $fromDateTime;
			$employeeSyncLog->to_date_time = $toDateTime;
			$employeeSyncLog->update_count = count($employeeSyncedData);
			$employeeSyncLog->created_by_id = Auth::id();
			if ($errorFileName) {
				$employeeSyncLog->error_file = 'storage/app/public/hrms_to_dems/' . $errorFileName . '.xlsx';
			}
			$employeeSyncLog->save();

			return response()->json([
				'success' => true,
				'message' => ['Employee details synced successfully'],
			]);
		}
	}

	public static function hrmsToDemsEmployeeData($employee, $category) {
		$employeeData = [
			'code' => $employee->code,
			'name' => $employee->user ? $employee->user->name : '',
			'designation' => $employee->designation ? $employee->designation->name : '',
			'grade' => $employee->grade->name,
			'outlet' => $employee->outlet->code,
			'lob' => isset($employee->Sbu->lob) ? $employee->Sbu->lob->name : '',
			'sbu' => $employee->Sbu ? $employee->Sbu->name : '',
			'function' => $employee->Department ? $employee->Department->name : '',
			'repoting_to_code' => $employee->reportingTo ? $employee->reportingTo->code : '',
			'repoting_to_name' => isset($employee->reportingTo->user) ? $employee->reportingTo->user->name : '',
			'category' => $category,
		];
		return $employeeData;
	}

	public static function hrmsEmployeeManualAddition($request) {
		// dd($request->all());
		$hrmsPortalConfig = config('custom.HRMS_PORTAL_CONFIG');
		if ($hrmsPortalConfig == true) {
			$hrmsPortal = DB::table('portals')->select([
				'db_host_name',
				'db_port_number',
				'db_name',
				'db_user_name',
				'db_password',
			])
				->where('id', 2) //HRMS
				->first();

			$dbHostName = $hrmsPortal->db_host_name;
			$dbPortNumber = $hrmsPortal->db_port_number;
			$dbPortDriver = 'mysql';
			$dbName = $hrmsPortal->db_name;
			$dbUserName = $hrmsPortal->db_user_name;
			$dbPassword = $hrmsPortal->db_password;
		} else {
			$dbHostName = config('custom.HRMS_DB_HOST');
			$dbPortNumber = config('custom.HRMS_DB_PORT_NUMBER');
			$dbPortDriver = 'mysql';
			$dbName = config('custom.HRMS_DB_NAME');
			$dbUserName = config('custom.HRMS_DB_USER_NAME');
			$dbPassword = config('custom.HRMS_DB_PASSWORD');
		}

		DB::setDefaultConnection('dynamic');
		dataBaseConfig::set('database.connections.dynamic.host', $dbHostName);
		dataBaseConfig::set('database.connections.dynamic.port', $dbPortNumber);
		dataBaseConfig::set('database.connections.dynamic.driver', $dbPortDriver);
		dataBaseConfig::set('database.connections.dynamic.database', $dbName);
		dataBaseConfig::set('database.connections.dynamic.username', $dbUserName);
		dataBaseConfig::set('database.connections.dynamic.password', $dbPassword);
		DB::purge('dynamic');
		DB::reconnect('dynamic');
		$hrmsCompanyId = DB::table('companies')
			->where('adre_code', $request->company_code)
			->pluck('id')
			->first();
		// ->id;

		if (!$hrmsCompanyId) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['The logined user company not found in HRMS'],
			]);
		}

		$hrmsEmployee = DB::table('employees')->select([
			'employees.id',
			'employees.company_id',
			'employees.code as employee_code',
			'employees.name as employee_name',
			'employees.doj',
			'employees.dob',
			'employees.mobile_number',
			'employees.email',
			'companies.adre_code',
			'outlet_companies.adre_code as outlet_company_adre_code',
			'outlets.id as outlet_id',
			'outlets.code as outlet_code',
			'outlets.name as outlet_name',
			'grade_companies.adre_code as grade_company_adre_code',
			'grades.code as grade_code',
			'designation_companies.adre_code as designation_company_adre_code',
			'designations.code as designation_code',
			'lob_companies.adre_code as lob_company_adre_code',
			'lobs.id as lob_id',
			'lobs.code as lob_code',
			'sbus.code as sbu_code',
			'employee_details.pan',
			'employee_details.aadhar',
			'genders.label as gender_name',
			'reporting_to_companies.adre_code as reporting_to_company_adre_code',
			'reporting_to_employees.code as reporting_to_employee_code',
			'reporting_to_employees.name as reporting_to_employee_name',
			'reporting_to_outlets.code as reporting_to_outlet_code',
			'reporting_to_outlets.name as reporting_to_outlet_name',
			'reporting_to_outlet_companies.adre_code as reporting_to_outlet_company_adre_code',
			'reporting_to_grades.code as reporting_to_grade_code',
			'reporting_to_grade_companies.adre_code as reporting_to_grade_company_adre_code',
			'reporting_to_employees.mobile_number as reporting_to_mobile_number',
			'reporting_to_employees.email as reporting_to_email',
			'reporting_to_employees.dob as reporting_to_dob',
			'funcs.name as function_name',
			'funcs_companies.adre_code as func_company_adre_code',
		])
			->join('companies', 'companies.id', 'employees.company_id')
			->leftjoin('outlets', 'outlets.id', 'employees.outlet_id')
			->leftjoin('companies as outlet_companies', 'outlet_companies.id', 'outlets.company_id')
			->join('grades', 'grades.id', 'employees.grade_id')
			->leftjoin('companies as grade_companies', 'grade_companies.id', 'grades.company_id')
			->leftjoin('designations', 'designations.id', 'employees.designation_id')
			->leftjoin('companies as designation_companies', 'designation_companies.id', 'designations.company_id')
			->leftjoin('lobs', 'lobs.id', 'employees.lob_id')
			->leftjoin('companies as lob_companies', 'lob_companies.id', 'lobs.company_id')
			->leftjoin('sbus', 'sbus.id', 'employees.sbu_id')
			->leftjoin('employee_details', 'employee_details.employee_id', 'employees.id')
			->leftjoin('genders', 'genders.id', 'employees.gender_id')
			->leftjoin('funcs', 'funcs.id', 'employees.func_id')
			->leftjoin('companies as funcs_companies', 'funcs_companies.id', 'funcs.company_id')
			->leftjoin('employees as reporting_to_employees', 'reporting_to_employees.id', 'employees.reporting_to_id')
			->leftjoin('companies as reporting_to_companies', 'reporting_to_companies.id', 'reporting_to_employees.company_id')
			->leftjoin('outlets as reporting_to_outlets', 'reporting_to_outlets.id', 'reporting_to_employees.outlet_id')
			->leftjoin('companies as reporting_to_outlet_companies', 'reporting_to_outlet_companies.id', 'reporting_to_outlets.company_id')
			->leftjoin('grades as reporting_to_grades', 'reporting_to_grades.id', 'reporting_to_employees.grade_id')
			->leftjoin('companies as reporting_to_grade_companies', 'reporting_to_grade_companies.id', 'reporting_to_grades.company_id')
			->where('employees.company_id', $hrmsCompanyId)
			->where('employees.code', $request->employee_code)
			->first();

		if (empty($hrmsEmployee)) {
			return response()->json([
				'success' => false,
				'error' => 'Validation Error',
				'errors' => ['Employee details not found in HRMS for this : ' . $request->employee_code],
			]);
		}

		$outletHrEmail = null;
		$outletHrRoleId = DB::table('roles')
			->where('company_id', $hrmsEmployee->company_id)
			->where('name', 'Outlet HR')
			->pluck('id')
			->first();
		// ->id;
		if ($outletHrRoleId) {
			$outletHrEmployeeIds = DB::table('user_has_roles')
				->join('employees', 'employees.user_id', 'user_has_roles.user_id')
				->where('user_has_roles.role_id', $outletHrRoleId)
				->pluck('employees.id');
			if ($outletHrEmployeeIds) {
				$outletHrEmail = DB::table('employee_outlets')->select([
					'employee_outlets.id',
					'employees.code',
					'employees.email',
				])
					->join('employees', 'employees.id', 'employee_outlets.employee_id')
					->whereIn('employee_outlets.employee_id', $outletHrEmployeeIds)
					->where('employee_outlets.outlet_id', $hrmsEmployee->outlet_id)
					->pluck('email')
					->first();
				// ->email;
			}
		}
		$hrmsEmployee->outlet_hr_email = $outletHrEmail;

		DB::setDefaultConnection('mysql');

		$employeeSyncedData = [];
		DB::beginTransaction();
		try {
			//CHECK EMPLOYEE LOB IS NOT DLOB, OESL
			if (!in_array($hrmsEmployee->lob_id, [4, 15])) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['Employee LOB should be DLOB, OESL'],
				]);
			}

			//EMPLOYEE COMPANY
			if (!$hrmsEmployee->adre_code) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['The employee company is required'],
				]);
			} else {
				$companyId = Company::where('code', $hrmsEmployee->adre_code)->pluck('id')->first();
				if (!$companyId) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => ['The employee company not found in travelex'],
					]);
				} else {
					$employeeExistId = Employee::withTrashed()
						->where('company_id', $companyId)
						->where('code', $hrmsEmployee->employee_code)
						->pluck('id')
						->first();
					if ($employeeExistId) {
						return response()->json([
							'success' => false,
							'error' => 'Validation Error',
							'errors' => ['The employee detail already available in travelex'],
						]);
					}
				}
			}

			//EMPLOYEE MOBILE NUMBER
			if (!$hrmsEmployee->mobile_number) {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['The employee mobile number is requried'],
				]);
			} else {
				if (!preg_match('/^[0-9]{10}+$/', $hrmsEmployee->mobile_number)) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => ['The employee mobile number is invalid'],
					]);
				}
			}

			//EMPLOYEE OUTLET
			if ($hrmsEmployee->outlet_code) {
				$outletCompanyId = Company::where('code', $hrmsEmployee->outlet_company_adre_code)->pluck('id')->first();
				if (!$outletCompanyId) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => ['The employee outlet company not found in travelex'],
					]);
				} else {
					$outlet = Outlet::withTrashed()->firstOrNew([
						'company_id' => $outletCompanyId,
						'code' => $hrmsEmployee->outlet_code,
					]);
					if ($outlet->exists) {
						$outlet->updated_by = Auth::id();
						$outlet->updated_at = Carbon::now();
					} else {
						$outlet->created_by = Auth::id();
						$outlet->created_at = Carbon::now();
					}
					$outlet->name = $hrmsEmployee->outlet_name;
					$outlet->save();
				}
			} else {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['The employee outlet code is required'],
				]);
			}

			//EMPLOYEE GRADE
			if ($hrmsEmployee->grade_code) {
				$gradeCompanyId = Company::where('code', $hrmsEmployee->grade_company_adre_code)->pluck('id')->first();
				if (!$gradeCompanyId) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => ['The employee grade company not found in travelex'],
					]);
				} else {
					$grade = Entity::withTrashed()->firstOrNew([
						'company_id' => $gradeCompanyId,
						'entity_type_id' => 500, //GRADE
						'name' => $hrmsEmployee->grade_code,
					]);
					if ($grade->exists) {
						$grade->updated_by = Auth::id();
						$grade->updated_at = Carbon::now();
					} else {
						$grade->created_by = Auth::id();
						$grade->created_at = Carbon::now();
					}
					$grade->save();

					//EMPLOYEE DESIGNATION
					if ($hrmsEmployee->designation_code) {
						$designationCompanyId = Company::where('code', $hrmsEmployee->designation_company_adre_code)->pluck('id')->first();
						if (!$designationCompanyId) {
							return response()->json([
								'success' => false,
								'error' => 'Validation Error',
								'errors' => ['The employee designation company not found in travelex'],
							]);
						} else {
							$designation = Designation::withTrashed()->firstOrNew([
								'company_id' => $designationCompanyId,
								'name' => $hrmsEmployee->designation_code,
								'grade_id' => $grade->id,
							]);
							if ($designation->exists) {
								$designation->updated_by = Auth::id();
								$designation->updated_at = Carbon::now();
							} else {
								$designation->created_by = Auth::id();
								$designation->created_at = Carbon::now();
							}
							$designation->save();
						}
					}
				}
			} else {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['The employee grade is required'],
				]);
			}

			//EMLOYEE LOB & SBU
			if ($hrmsEmployee->lob_code) {
				$lobCompanyId = Company::where('code', $hrmsEmployee->lob_company_adre_code)->pluck('id')->first();
				if (!$lobCompanyId) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => ['The employee lob company not found in travelex'],
					]);
				} else {
					$lob = Lob::firstOrNew([
						'company_id' => $lobCompanyId,
						'name' => $hrmsEmployee->lob_code,
					]);
					if ($lob->exists) {
						$lob->updated_by = Auth::id();
						$lob->updated_at = Carbon::now();
					} else {
						$lob->created_by = Auth::id();
						$lob->created_at = Carbon::now();
					}
					$lob->save();

					if ($hrmsEmployee->sbu_code) {
						$sbu = Sbu::firstOrNew([
							'lob_id' => $lob->id,
							'name' => $hrmsEmployee->sbu_code,
						]);
						if ($sbu->exists) {
							$sbu->updated_by = Auth::id();
							$sbu->updated_at = Carbon::now();
						} else {
							$sbu->created_by = Auth::id();
							$sbu->created_at = Carbon::now();
						}
						$sbu->save();

						//EMPLOYEE DEPARTMENT
						$businessId = null;
						if ($hrmsEmployee->lob_code == 'DLOB') {
							if (strpos($hrmsEmployee->sbu_code, 'honda') !== false) {
								$businessId = 3; //HONDA
							} else {
								$businessId = 1; //DLOB
							}
						} else if ($hrmsEmployee->lob_code == 'OESL') {
							$businessId = 2; //OESL
						}

						if ($businessId && $hrmsEmployee->function_name) {
							$funcCompanyId = Company::where('code', $hrmsEmployee->func_company_adre_code)->pluck('id')->first();
							if (!$funcCompanyId) {
								return response()->json([
									'success' => false,
									'error' => 'Validation Error',
									'errors' => ['The employee function company not found in travelex'],
								]);
							} else {
								$department = Department::withTrashed()->firstOrNew([
									'company_id' => $funcCompanyId,
									'business_id' => $businessId,
									'name' => $hrmsEmployee->function_name,
								]);
								if ($department->exists) {
									$department->updated_by = Auth::id();
									$department->updated_at = Carbon::now();
								} else {
									$department->created_by = Auth::id();
									$department->created_at = Carbon::now();
									$department->short_name = $hrmsEmployee->function_name;
								}
								$department->save();
							}
						}
					} else {
						return response()->json([
							'success' => false,
							'error' => 'Validation Error',
							'errors' => ['The employee sbu is required'],
						]);
					}
				}
			}

			//EMPLOYEE REPORTING TO DETAILS
			if ($hrmsEmployee->reporting_to_employee_code) {
				$reportingToCompanyId = Company::where('code', $hrmsEmployee->reporting_to_company_adre_code)->pluck('id')->first();
				if (!$reportingToCompanyId) {
					return response()->json([
						'success' => false,
						'error' => 'Validation Error',
						'errors' => ['The reporting to employee company not found in travelex'],
					]);
				} else {
					$reportingToEmployeeExistId = Employee::withTrashed()
						->where('company_id', $reportingToCompanyId)
						->where('code', $hrmsEmployee->reporting_to_employee_code)
						->pluck('id')
						->first();
					if (!$reportingToEmployeeExistId) {
						return response()->json([
							'success' => false,
							'error' => 'Validation Error',
							'errors' => ['The reporting to employee detail not available in travelex'],
						]);
					}
				}
			} else {
				return response()->json([
					'success' => false,
					'error' => 'Validation Error',
					'errors' => ['The reporting to employee detail is required'],
				]);
			}

			//REPORTING TO EMPLOYEE SAVE
			if ($hrmsEmployee->reporting_to_employee_code) {
				$reportingToEmployee = Employee::withTrashed()
					->where('id', $reportingToEmployeeExistId)
					->first();
				if ($reportingToEmployee) {
					$reportingToUser = User::withTrashed()
						->where('entity_id', $reportingToEmployee->id)
						->where('user_type_id', 3121) //EMPLOYEE
						->first();
					if ($reportingToUser) {
						if ($hrmsEmployee->reporting_to_employee_name) {
							$reportingToUser->name = $hrmsEmployee->reporting_to_employee_name;
						}
						if ($hrmsEmployee->reporting_to_email) {
							$reportingToUser->email = $hrmsEmployee->reporting_to_email;
						}
						$reportingToUser->save();
					}
				}
			}

			//EMPLOYEE SAVE
			$employee = Employee::withTrashed()->firstOrNew([
				'company_id' => $companyId,
				'code' => $hrmsEmployee->employee_code,
			]);
			if ($employee->exists) {
				$employee->updated_by = Auth::id();
				$employee->updated_at = Carbon::now();
			} else {
				$employee->created_by = Auth::id();
				$employee->created_at = Carbon::now();
			}
			$employee->outlet_id = $outlet->id;
			if (isset($reportingToEmployee)) {
				$employee->reporting_to_id = $reportingToEmployee->id;
			}
			$employee->grade_id = $grade->id;
			if (isset($designation)) {
				$employee->designation_id = $designation->id;
			}
			if (isset($department)) {
				$employee->department_id = $department->id;
			}
			if ($hrmsEmployee->doj) {
				$employee->date_of_joining = $hrmsEmployee->doj;
			}
			if ($hrmsEmployee->aadhar) {
				$employee->aadhar_no = $hrmsEmployee->aadhar;
			}
			if ($hrmsEmployee->pan) {
				$employee->pan_no = $hrmsEmployee->pan;
			}
			if ($hrmsEmployee->gender_name) {
				$employee->gender = $hrmsEmployee->gender_name;
			}
			if ($hrmsEmployee->dob) {
				$employee->date_of_birth = $hrmsEmployee->dob;
			}
			if (isset($sbu)) {
				$employee->sbu_id = $sbu->id;
			}
			$employee->save();

			//USER SAVE
			$user = User::withTrashed()->firstOrNew([
				'entity_id' => $employee->id,
				'user_type_id' => 3121, //EMPLOYEE
			]);
			if ($user->exists) {
				$user->updated_by = Auth::id();
				$user->updated_at = Carbon::now();
				if ($hrmsEmployee->dob) {
					$user->password = $hrmsEmployee->dob;
				}
			} else {
				$user->created_by = Auth::id();
				$user->created_at = Carbon::now();
				if ($hrmsEmployee->dob) {
					$user->password = $hrmsEmployee->dob;
				} else {
					$user->password = $employee->code;
				}
			}
			$user->company_id = $employee->company_id;
			$user->entity_type = 0;
			$user->username = $employee->code;
			$user->name = $hrmsEmployee->employee_name;
			$user->mobile_number = $hrmsEmployee->mobile_number;
			if ($hrmsEmployee->email) {
				$user->email = $hrmsEmployee->email;
			}
			$user->save();

			$employeeAdditionData = self::hrmsToDemsEmployeeData($employee, 'New Addition');

			//EMPLOYEE ADDITION MAIL TO EMPLOYEE, CC REPORTING MANAGE AND OUTLET HR
			if ($employeeAdditionData && $user->email) {
				$toEmail = [$user->email];
				$ccEmail = [];
				if (isset($reportingToUser->email)) {
					$ccEmail[] = $reportingToUser->email;
				}
				if (isset($hrmsEmployee->outlet_hr_email)) {
					$ccEmail[] = $hrmsEmployee->outlet_hr_email;
				}

				$arr = [];
				$arr['from_mail'] = env('MAIL_FROM_ADDRESS', 'travelex@tvs.in');
				$arr['from_name'] = 'DEMS-Admin';
				$arr['to_email'] = $toEmail;
				$arr['cc_email'] = $ccEmail;
				$arr['subject'] = 'Travelex login access enabled';
				$arr['content'] = 'Hi ' . $user->name . ', we have enabled travelex access for your login, please give your ecode with  AD password  to login travelex application.';
				$arr['blade_file'] = 'mail.hrms_to_travelex_employee_report';
				$arr['type'] = 1;
				$arr['employee'] = $employeeAdditionData;
				$mail_instance = new TravelexConfigMail($arr);
				$mail = Mail::send($mail_instance);
			}

			//EMPLOYEE ADDITION MAIL TO AX
			if ($employeeAdditionData) {
				$mailConfig = MailConfiguration::select(
					'to_email',
					'cc_email'
				)
					->where('company_id', $request->company_id)
					->where('config_id', 3945) //HRMS To Travelex Employee Manual Addition Mail
					->first();

				$to = explode(',', $mailConfig->to_email);
				$cc = explode(',', $mailConfig->cc_email);

				$arr = [];
				$arr['from_mail'] = env('MAIL_FROM_ADDRESS', 'travelex@tvs.in');
				$arr['from_name'] = 'DEMS-Admin';
				$arr['to_email'] = $to;
				$arr['cc_email'] = $cc;
				$arr['subject'] = 'Employee Addition';
				$arr['content'] = 'The below employee please create in Axapta vendor master immediately';
				$arr['blade_file'] = 'mail.hrms_to_travelex_employee_report';
				$arr['type'] = 1;
				$arr['employee'] = $employeeAdditionData;
				$mail_instance = new TravelexConfigMail($arr);
				$mail = Mail::send($mail_instance);
			}
			DB::commit();
			return response()->json([
				'success' => true,
				'employee' => $employeeAdditionData,
				'message' => ['Employee synced successfully'],
			]);
		} catch (\Exception $e) {
			DB::rollBack();
			return response()->json([
				'success' => false,
				'error' => 'Server Error',
				'errors' => [
					'Error : ' . $e->getMessage() . '. Line : ' . $e->getLine() . '. File : ' . $e->getFile(),
				],
			]);
		}
	}
}
