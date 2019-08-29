<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use App\Role;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\BankDetail;
use Uitoux\EYatra\Config;
use Uitoux\EYatra\Designation;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\Entity;
use Uitoux\EYatra\Lob;
use Uitoux\EYatra\Sbu;
use Uitoux\EYatra\WalletDetail;
use Validator;
use Yajra\Datatables\Datatables;

class EmployeeController extends Controller {

	public function filterEYatraEmployee() {
		$this->data['outlet_list'] = $outlet_list = collect(Outlet::getList())->prepend(['id' => '', 'name' => 'Select Outlet']);
		$this->data['grade_list'] = $grade_list = collect(Entity::getGradeList())->prepend(['id' => '', 'name' => 'Select Grade']);
		$this->data['role_list'] = $role_list = collect(Role::getList())->prepend(['id' => '', 'name' => 'Select Role']);

		return response()->json($this->data);
	}

	public function listEYatraEmployee(Request $r) {
		//dd($r->all());
		if (!empty($r->outlet)) {
			$outlet = $r->outlet;
		} else {
			$outlet = null;
		}
		if (!empty($r->role)) {
			$role = $r->role;
		} else {
			$role = null;
		}
		if (!empty($r->grade)) {
			$grade = $r->grade;
		} else {
			$grade = null;
		}
		$employees = Employee::withTrashed()->from('employees as e')
			->join('entities as grd', 'grd.id', 'e.grade_id')
			->join('users as u', 'u.entity_id', 'e.id')
			->leftJoin('employees as m', 'e.reporting_to_id', 'm.id')
			->join('outlets as o', 'o.id', 'e.outlet_id')
			->withTrashed()
			->select(
				'e.id',
				'e.code',
				'u.name',
				'o.code as outlet_code',
				DB::raw('IF(m.code IS NULL,"--",m.code) as manager_code'),
				'grd.name as grade',
				DB::raw('IF(e.deleted_at IS NULL, "Active","Inactive") as status')
			)
			->where(function ($query) use ($r, $outlet) {
				if (!empty($outlet)) {
					$query->where('o.id', $outlet);
				}
			})
		// ->where(function ($query) use ($r, $role) {
		// 	if (!empty($role)) {
		// 		$query->where('roles.id', $role);
		// 	}
		// })
			->where(function ($query) use ($r, $grade) {
				if (!empty($grade)) {
					$query->where('grd.id', $grade);
				}
			})
			->where('u.user_type_id', 3121)
			->where('e.company_id', Auth::user()->company_id)
			->orderBy('e.code', 'asc');

		return Datatables::of($employees)
			->addColumn('action', function ($employee) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');

				return '
				<a href="#!/eyatra/employee/edit/' . $employee->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/eyatra/employee/view/' . $employee->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#delete_emp"
				onclick="angular.element(this).scope().deleteEmployee(' . $employee->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" >
                </a>';

			})
			->addColumn('status', function ($agent) {
				if ($agent->status == 'Inactive') {
					return '<span style="color:#ea4335;">Inactive</span>';
				} else {
					return '<span style="color:#63ce63;">Active</span>';
				}
			})
			->make(true);
	}

	public function eyatraEmployeeFormData($employee_id = NULL) {

		if (!$employee_id) {
			$this->data['action'] = 'Add';
			$employee = new Employee;
			//dd($employee);
			$this->data['success'] = true;
		} else {
			$this->data['action'] = 'Edit';
			$employee = Employee::withTrashed()->with('sbu', 'bankDetail', 'reportingTo', 'walletDetail', 'user')->find($employee_id);
			if (!$employee) {
				$this->data['success'] = false;
				$this->data['message'] = 'Employee not found';
			}
			$employee->roles = $employee->user->roles()->pluck('role_id')->toArray();
			$this->data['success'] = true;
		}
		$outlet_list = collect(Outlet::getList())->prepend(['id' => '', 'name' => 'Select Outlet']);
		$payment_mode_list = collect(Config::paymentModeList())->prepend(['id' => '', 'name' => 'Select Payment Mode']);
		$wallet_mode_list = collect(Entity::walletModeList())->prepend(['id' => '', 'name' => 'Select Wallet Mode']);
		$role_list = collect(Role::getList())->prepend(['id' => '', 'name' => 'Select Role']);
		$grade_list = collect(Entity::getGradeList())->prepend(['id' => '', 'name' => 'Select Grade']);
		$designation_list = [];
		// dd($designation_list);
		$lob_list = collect(Lob::select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Lob']);
		$sbu_list = [];
		$this->data['extras'] = [
			'manager_list' => Employee::getList(),
			'outlet_list' => $outlet_list,
			'payment_mode_list' => $payment_mode_list,
			'wallet_mode_list' => $wallet_mode_list,
			'role_list' => $role_list,
			'lob_list' => $lob_list,
			'sbu_list' => $sbu_list,
			'grade_list' => $grade_list,
			'designation_list' => $designation_list,
		];
		// dd($this->data['extras']);
		$this->data['employee'] = $employee;

		return response()->json($this->data);
	}

	public function saveEYatraEmployee(Request $request) {
		//validation
		try {
			$error_messages = [
				'mobile_number.required' => "Mobile Number is Required",
				'username.required' => "Username is Required",
				'mobile_number.unique' => "Mobile Number is already taken",
			];

			$validator = Validator::make($request->all(), [
				'code' => [
					'unique:employees,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
					'required:true',
				],
				'mobile_number' => [
					'required:true',
					'unique:users,mobile_number,' . $request->user_id . ',id,company_id,' . Auth::user()->company_id,
				],
				'name' => [
					'required:true',
				],
				'username' => [
					'required:true',
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			//ROLE VALIDATION
			$roles_valid = json_decode($request->roles);
			if (empty($roles_valid)) {
				return response()->json(['success' => false, 'errors' => ['Role is required']]);
			}

			DB::beginTransaction();
			if (!$request->id) {
				$employee = new Employee;
				$employee->created_by = Auth::user()->id;
				$employee->created_at = Carbon::now();
				$employee->updated_at = NULL;
			} else {
				$employee = Employee::withTrashed()->find($request->id);
				$employee->updated_by = Auth::user()->id;
				$employee->updated_at = Carbon::now();
			}
			$employee->fill($request->all());
			$employee->company_id = Auth::user()->company_id;
			if ($request->status == 0) {
				$employee->deleted_at = date('Y-m-d H:i:s');
				$employee->deleted_by = Auth::user()->id;
			} else {
				$employee->deleted_by = NULL;
				$employee->deleted_at = NULL;
			}
			$employee->save();

			//USER ACCOUNT
			$user = User::withTrashed()->firstOrNew([
				'id' => $request->user_id,
			]);
			$user->mobile_number = $request->mobile_number;
			$user->entity_type = 0;
			$user->user_type_id = 3121;
			$user->company_id = Auth::user()->company_id;
			$user->entity_id = $employee->id;
			$user->fill($request->all());
			//dd($request->password_change);
			if ($request->password_change == 'Yes') {
				if (!empty($request->user['password'])) {
					$user->password = $request->user['password'];
				}
				$user->force_password_change = 1;
			} else {
				$user->force_password_change = 0;
			}
			if ($request->status == 0) {
				$user->deleted_at = date('Y-m-d H:i:s');
				$user->deleted_by = Auth::user()->id;
			} else {
				$user->deleted_by = NULL;
				$user->deleted_at = NULL;
			}
			$user->save();
//dd($user);
			//USER ROLE SYNC
			$user->roles()->sync(json_decode($request->roles));

			//BANK DETAIL SAVE
			if ($request->bank_name) {
				$bank_detail = BankDetail::firstOrNew(['entity_id' => $employee->id]);
				$bank_detail->fill($request->all());
				$bank_detail->detail_of_id = 3243;
				$bank_detail->entity_id = $employee->id;
				$bank_detail->account_type_id = 3243;
				$bank_detail->save();
			}

			//WALLET SAVE
			if ($request->type_id) {
				$wallet_detail = WalletDetail::firstOrNew(['entity_id' => $employee->id]);
				$wallet_detail->fill($request->all());
				$wallet_detail->wallet_of_id = 3243;
				$wallet_detail->entity_id = $employee->id;
				$wallet_detail->save();
			}
			DB::commit();
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}

	public function viewEYatraEmployee($employee_id) {

		$employee = Employee::withTrashed()->with([
			'Sbu',
			'Sbu.lob',
			'reportingTo',
			'outlet',
			'grade',
			'bankDetail',
			'walletDetail',
			'walletDetail.type',
			'user',
			'paymentMode',
		])
			->find($employee_id);
		if (!$employee) {
			$this->data['success'] = false;
			$this->data['errors'] = ['Employee not found'];
			return response()->json($this->data);
		}
		$employee->roles = $employee->user->roles()->pluck('name')->toArray();
		$this->data['employee'] = $employee;
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function deleteEYatraEmployee($employee_id) {
		$user = User::withTrashed()->where('entity_id', $employee_id)->forcedelete();
		if (!$user) {
			return response()->json(['success' => false, 'errors' => ['User not found']]);
		}
		$employee = Employee::withTrashed()->where('id', $employee_id)->forcedelete();
		if (!$employee) {
			return response()->json(['success' => false, 'errors' => ['Employee not found']]);
		}
		return response()->json(['success' => true]);
	}

	public function searchManager(Request $r) {
		$key = $r->key;
		$manager_list = Employee::select(
			'name',
			'code',
			'id'
		)
			->where(function ($q) use ($key) {
				$q->where('name', 'like', '%' . $key . '%')
					->orWhere('code', 'like', '%' . $key . '%')
				;
			})
			->get();
		return response()->json($manager_list);
	}
	public function getDesignationByGrade(Request $request) {
		if (!empty($request->grade_id)) {

			$designation_list = collect(Designation::where('grade_id', $request->grade_id)->select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Designation']);
		} else {
			$designation_list = [];
		}
		return response()->json(['designation_list' => $designation_list]);
	}
	public function getSbuByLob(Request $request) {
		//dd($request);
		if (!empty($request->lob_id)) {
			$sbu_list = collect(Sbu::where('lob_id', $request->lob_id)->select('name', 'id')->get())->prepend(['id' => '', 'name' => 'Select Sbu']);
		} else {
			$sbu_list = [];
		}
		return response()->json(['sbu_list' => $sbu_list]);
	}

}
