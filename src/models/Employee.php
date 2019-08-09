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
		'created_by',
		'updated_by',
		'deleted_by',
	];

	public function company() {
		return $this->belongsTo('App\Company');
	}

	public function trips() {
		return $this->hasMany('Uitoux\EYatra\Trip');
	}

	public function grade() {
		return $this->belongsTo('Uitoux\EYatra\Entity');
	}

	public function outlet() {
		return $this->belongsTo('Uitoux\EYatra\Outlet');
	}

	public function bankDetail() {
		return $this->hasOne('Uitoux\EYatra\BankDetail', 'entity_id');
	}

	public function reportingTo() {
		return $this->belongsTo('Uitoux\EYatra\Employee', 'reporting_to_id');
	}

	public function user() {
		return $this->hasOne('App\User', 'entity_id')->where('user_type_id', 3121);
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
		$employee->created_by = $admin->id;
		$employee->save();

		return $employee;
	}
	public static function createUser($company, $user_type_id, $entity, $faker, $roles) {
		$user = new User();
		$user->company_id = $company->id;
		$user->user_type_id = $user_type_id;
		$user->entity_id = $entity->id;
		$user->username = $entity->code;
		$user->mobile_number = $faker->unique()->numberBetween(9842000000, 9842099999);
		$user->password = '$2y$10$N9pYzAbL2spl7vX3ZE1aBeekppaosAdixk04PTkK5obng7.KsLAQ2';
		$user->save();
		$user->roles()->sync($roles);
		return $user;

	}

}
