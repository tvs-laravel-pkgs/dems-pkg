<?php

namespace Uitoux\EYatra;
use App\User;
use Auth;
use DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

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
		return $this->belongsTo('Uitoux\EYatra\Department');
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

}
