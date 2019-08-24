<?php

namespace Uitoux\EYatra;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Employee extends Model {
	use SoftDeletes;

	protected $fillable = [
		'id',
		'code',
		'name',
		'outlet_id',
		'reporting_to_id',
		'grade_id',
		'payment_mode_id',
		'sbu_id',
		'designation_id',
		'date_of_joining',
		'aadhar_no',
		'pan_no',
		'created_by',
		'updated_by',
		'deleted_by',
	];

	public function company() {
		return $this->belongsTo('App\Company');
	}

	public function Sbu() {
		return $this->belongsTo('Uitoux\EYatra\Sbu');
	}

	public function trips() {
		return $this->hasMany('Uitoux\EYatra\Trip');
	}

	public function grade() {
		return $this->belongsTo('Uitoux\EYatra\Entity', 'grade_id');
	}

	public function designation() {
		return $this->belongsTo('Uitoux\EYatra\Entity', 'designation_id');
	}

	public function outlet() {
		return $this->belongsTo('Uitoux\EYatra\Outlet');
	}

	public function bankDetail() {
		return $this->hasOne('Uitoux\EYatra\BankDetail', 'entity_id');
	}

	public function walletDetail() {
		return $this->hasOne('Uitoux\EYatra\WalletDetail', 'entity_id');
	}

	public function reportingTo() {
		return $this->belongsTo('Uitoux\EYatra\Employee', 'reporting_to_id');
	}

	public function paymentMode() {
		return $this->belongsTo('Uitoux\EYatra\Config', 'payment_mode_id');
	}

	public function user() {
		return $this->hasOne('App\User', 'entity_id')->where('user_type_id', 3121)->withTrashed();
	}

	public static function getList() {
		return Employee::select('name', 'id')->get();
	}

	public static function create($company, $code, $outlet, $admin, $faker, $manager_id = null) {
		$employee = Employee::firstOrNew([
			'company_id' => $company->id,
			'code' => $code,
		]);
		$employee->name = $faker->name;
		$employee->outlet_id = $outlet->id;
		$employee->grade_id = $company->employeeGrades()->inRandomOrder()->first()->id;
		$employee->reporting_to_id = $manager_id;
		$employee->payment_mode_id = $company->employeePaymentModes()->inRandomOrder()->first()->id;
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
		$user->entity_id = $entity->id;
		// $user->mobile_number = $faker->unique()->numberBetween(1234500000, 1234599999);
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

}
