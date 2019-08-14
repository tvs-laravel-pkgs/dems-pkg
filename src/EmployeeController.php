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
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\Entity;
use Uitoux\EYatra\WalletDetail;
use Validator;
use Yajra\Datatables\Datatables;

class EmployeeController extends Controller {
	public function listEYatraEmployee(Request $r) {
		$employees = Employee::from('employees as e')
			->join('entities as grd', 'grd.id', 'e.grade_id')
			->leftJoin('employees as m', 'e.reporting_to_id', 'm.id')
			->join('outlets as o', 'o.id', 'e.outlet_id')
			->withTrashed()
			->select(
				'e.id',
				'e.code',
				'e.name',
				'o.code as outlet_code',
				'm.code as manager_code',
				'grd.name as grade',
				DB::raw('IF(e.deleted_at IS NULL, "Active","Inactive") as status')
			)
			->where('e.company_id', Auth::user()->company_id)
			->orderBy('e.code', 'asc');

		return Datatables::of($employees)
			->addColumn('action', function ($employee) {

				$img1 = asset('public/img/content/table/edit-yellow.svg');
				$img2 = asset('public/img/content/table/eye.svg');
				$img1_active = asset('public/img/content/table/edit-yellow-active.svg');
				$img2_active = asset('public/img/content/table/eye-active.svg');
				$img3 = asset('public/img/content/table/delete-default.svg');
				$img3_active = asset('public/img/content/table/delete-active.svg');
				return '
				<a href="#!/eyatra/employee/edit/' . $employee->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				<a href="#!/eyatra/employee/view/' . $employee->id . '">
					<img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" >
				</a>
				<a href="javascript:;" data-toggle="modal" data-target="#delete_emp"
				onclick="angular.element(this).scope().deleteEmployee(' . $employee->id . ')" dusk = "delete-btn" title="Delete">
                <img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover="this.src="' . $img3_active . '" onmouseout="this.src="' . $img3 . '" >
                </a>';

			})
			->make(true);
	}

	public function eyatraEmployeeFormData($employee_id = NULL) {

		if (!$employee_id) {
			$this->data['action'] = 'Add';
			$employee = new Employee;
			$this->data['success'] = true;
		} else {
			$this->data['action'] = 'Edit';
			$employee = Employee::withTrashed()->with('bankDetail', 'reportingTo', 'WalletDetail', 'user')->find($employee_id);
			if (!$employee) {
				$this->data['success'] = false;
				$this->data['message'] = 'Employee not found';
			}
			$employee->roles = $employee->user->roles()->pluck('id')->toArray();
			$this->data['success'] = true;
		}
		$outlet_list = collect(Outlet::getList())->prepend(['id' => '', 'name' => 'Select Outlet']);
		$grade_list = collect(Entity::getGradeList())->prepend(['id' => '', 'name' => 'Select Grade']);
		$payment_mode_list = collect(Config::paymentModeList())->prepend(['id' => '', 'name' => 'Select Payment Mode']);
		$wallet_mode_list = collect(Entity::walletModeList())->prepend(['id' => '', 'name' => 'Select Wallet Mode']);
		$role_list = collect(Role::getList())->prepend(['id' => '', 'name' => 'Select Role']);
		$this->data['extras'] = [
			'manager_list' => Employee::getList(),
			'outlet_list' => $outlet_list,
			'grade_list' => $grade_list,
			'payment_mode_list' => $payment_mode_list,
			'wallet_mode_list' => $wallet_mode_list,
			'role_list' => $role_list,
		];
		$this->data['employee'] = $employee;

		return response()->json($this->data);
	}

	public function saveEYatraEmployee(Request $request) {
		// dd($request->all());
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
			if ($request->password_change == 'Yes') {
				if (!empty($request->user['password'])) {
					$user->password = $request->user['password'];
				}
				$user->force_password_change = 1;
			}
			if ($request->status == 0) {
				$user->deleted_at = date('Y-m-d H:i:s');
				$user->deleted_by = Auth::user()->id;
			} else {
				$user->deleted_by = NULL;
				$user->deleted_at = NULL;
			}
			$user->save();

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
			'reportingTo',
			'outlet',
			'grade',
			'bankDetail',
		])
			->find($employee_id);
		if (!$employee) {
			$this->data['success'] = false;
			$this->data['errors'] = ['Employee not found'];
			return response()->json($this->data);
		}
		$this->data['employee'] = $employee;
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function deleteEYatraEmployee($employee_id) {
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

}
