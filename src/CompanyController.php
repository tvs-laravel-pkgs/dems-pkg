<?php
namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\Address;
use Uitoux\EYatra\Employee;
use Uitoux\EYatra\Lob;
use Uitoux\EYatra\NCity;
use Uitoux\EYatra\NState;
use Uitoux\EYatra\Company;
use Uitoux\EYatra\Sbu;
use Uitoux\EYatra\Config;
use Validator;
use Yajra\Datatables\Datatables;

class CompanyController extends Controller {

	public function listEYatraCompany(Request $r) {
		$companies = Company::withTrashed()->select(
				'companies.id',
				'companies.code',
				'companies.name',
				'companies.address',
				'companies.cin_number',
				'companies.gst_number',
				'companies.customer_care_email',
				'companies.customer_care_phone',
				'companies.reference_code',
				DB::raw('IF(companies.deleted_at IS NULL,"Active","Inactive") as status'),
				'users.name as created_by'
			)
			->join('users','companies.created_by','users.id')
			/*->where(function ($query) use ($r) {
				if ($r->get('cashier_id')) {
					$query->where("outlets.cashier_id", $r->get('cashier_id'))->orWhere(DB::raw("-1"), $r->get('cashier_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('region_id')) {
					$query->where("r.id", $r->get('region_id'))->orWhere(DB::raw("-1"), $r->get('region_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('city_id')) {
					$query->where("city.id", $r->get('city_id'))->orWhere(DB::raw("-1"), $r->get('city_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('state_id')) {
					$query->where("s.id", $r->get('state_id'))->orWhere(DB::raw("-1"), $r->get('state_id'));
				}
			})
			->where(function ($query) use ($r) {
				if ($r->get('country_id')) {
					$query->where("c.id", $r->get('country_id'))->orWhere(DB::raw("-1"), $r->get('country_id'));
				}
			})*/
			->groupBy('companies.id');
		// if (!Entrust::can('view-all-trips')) {
		// $trips->where('trips.employee_id', Auth::user()->entity_id);
		// }
		return Datatables::of($companies)
			->addColumn('action', function ($companies) {
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');

				$action = '';
				$edit_class = "visibility:hidden";
				if (Entrust::can('eyatra-outlet-edit')) {
					$edit_class = "";
				}
				$delete_class = "visibility:hidden";
				if (Entrust::can('eyatra-outlet-delete')) {
					$delete_class = "";
				}

				$action .= '<a style="' . $edit_class . '" href="#!/company/edit/' . $companies->id . '"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '"></a> ';
				$action .= '<a href="#!/company/view/' . $companies->id . '"><img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" ></a> ';
				$action .= '<a style="' . $delete_class . '" href="javascript:;" data-toggle="modal" data-target="#delete_company" onclick="angular.element(this).scope().deleteCompanyConfirm(' . $companies->id . ')" dusk = "delete-btn" title="Delete"><img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" ></a>';

				return $action;
			})
			->addColumn('status', function ($companies) {
				if ($companies->status == 'Inactive') {
					return '<span style="color:red">Inactive</span>';
				} else {
					return '<span style="color:green">Active</span>';
				}
			})
			->make(true);
	}

	public function eyatraCompanyFormData($id = NULL) {
		if (!$id) {
			$this->data['action'] = 'Add';
			$company = new Company;
			$this->data['status'] = 'Active';
			$this->data['success'] = true;

		} else {
			$this->data['action'] = 'Edit';
			$company = Company::with('companyBudgets')->find($id);
			if (!$company) {
				$this->data['success'] = false;
				$this->data['message'] = 'Company not found';
			}
			if ($company->deleted_at == NULL) {
				$this->data['status'] = 'Active';
			} else {
				$this->data['status'] = 'Inactive';
			}
		}
		$this->data['financial_year_list']=$financial_year_list = collect(Config::select('name', 'id')->where('config_type_id', 536)->get());
		$this->data['company'] = $company;
		$this->data['success'] = true;
		
		return response()->json($this->data);
	}
	public function eyatraCompanyFilterData() {
		$this->data['region_list'] = Region::getList();
		$this->data['city_list'] = NCity::getList();
		$this->data['country_list'] = $country = NCountry::getList();
		$option = new NState;
		$option->name = 'Select State';
		$option->id = null;
		$this->data['state_list'] = $state_list = collect(NState::select('name', 'id')
				->get())->prepend($option);
		$option = new Employee;
		$option->name = 'Select Cashier';
		$option->id = null;
		$this->data['cashier_list'] = $cashier_list = collect(Employee::join('users', 'users.entity_id', 'employees.id')->join('outlets', 'outlets.cashier_id', 'employees.id')->where('users.company_id', Auth::user()->company_id)->where('users.user_type_id', 3121)->select(
			DB::raw('concat(employees.code," - ",users.name) as name'), 'employees.id')
				->groupBy('employees.id')
				->get())->prepend($option);
		$this->data['success'] = true;
		//dd($this->data);
		return response()->json($this->data);
	}


	public function saveEYatraCompany(Request $request) {
		//dd($request->all());
		//validation
		try {
			$error_messages = [
				'code.required' => 'Company Code is Required',
				'name.required' => 'Company Name is Required',
				'code.unique' => "Company Code is already taken",
				'name.unique' => "Company Name is already taken",
			];
			$validator = Validator::make($request->all(), [
				'code' => 'required',
				'name' => 'required',
				//'city_id' => 'required',
				//'pincode' => 'required',
				'code' => 'required|unique:companies,code,' . $request->id . ',id',
				'name' => 'required|unique:companies,name,' . $request->id . ',id',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
			// dd($request->all());
			DB::beginTransaction();
			if (!$request->id) {
				$company = new Company;
				$company->created_by = Auth::user()->id;
				$company->created_at = Carbon::now();
				$company->updated_at = NULL;
			} else {
				$company = Company::withTrashed()->find($request->id);
				$company->updated_by = Auth::user()->id;
				$company->updated_at = Carbon::now();
				//$company->outletBudgets()->sync([]);
			}
			if ($request->status == 'Active') {
				$company->deleted_at = NULL;
				$company->deleted_by = NULL;
			} else {
				$company->deleted_at = date('Y-m-d H:i:s');
				$company->deleted_by = Auth::user()->id;
			}
			$company->fill($request->all());
			$company->save();

			// Check after

			/*$activity['entity_id'] = $company->id;
			$activity['entity_type'] = "Company";
			$activity['details'] = empty($request->id) ? "Company is  Added" : "Company is  updated";
			$activity['activity'] = empty($request->id) ? "Add" : "Edit";
			$activity_log = ActivityLog::saveLog($activity);*/


			
			//SAVING COMPANY BUDGET
			if (isset($request->financial_year_id) && !empty($request->financial_year_id)) {
				$company->companyBudgets()->sync([]);
				$company->companyBudgets()->attach(
				$request->financial_year_id,
				['outstation_budget_amount' => isset($request->outstation_budget_amount) ? $request->outstation_budget_amount : 0,'local_budget_amount' => isset($request->local_budget_amount) ?$request->local_budget_amount : 0]
				);
			}

			DB::commit();
			$request->session()->flash('success', 'Company saved successfully!');
			if (empty($request->id)) {
				return response()->json(['success' => true, 'message' => 'Company Added successfully']);
			} else {
				return response()->json(['success' => true, 'message' => 'Company Updated Successfully']);
			}
			return response()->json(['success' => true]);
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => ['Exception Error' => $e->getMessage()]]);
		}
	}
	public function viewEYatraCompany($id) {
		$company = Company::with('createdBy')->withTrashed()
			->find($id);
			//dd($company);
		/*$company_budget = DB::table('company_budget')->select('lobs.name as lob_name', 'sbus.name as sbu_name', DB::raw('format(amount,2,"en_IN") as amount'))->where('company_budget.company_id', $id)

			->join('config', 'config.id', 'company_budget.sbu_id')
			->get()->toArray();
		$this->data['lob_name'] = $lob_name = array_column($outlet_budget, 'lob_name');
		$this->data['sbu_name'] = $sbu_name = array_column($outlet_budget, 'sbu_name');
		$this->data['amount'] = $amount = array_column($outlet_budget, 'amount');*/
		if (!$company) {
			$this->data['success'] = false;
			$this->data['errors'] = ['Company not found'];
			return response()->json($this->data);
		}
		if ($company->deleted_at == NULL) {
				$this->data['status'] = 'Active';
			} else {
				$this->data['status'] = 'Inactive';
			}
		

		// dd($outlet);
		$this->data['action'] = 'View';
		$this->data['company'] = $company;
		$this->data['success'] = true;
		//dd($this->data);

		return response()->json($this->data);
	}
	public function deleteEYatraCompany($id) {
		$company = Company::where('id', $id)->first();
		/*$activity['entity_id'] = $company->id;
		$activity['entity_type'] = "outlet";
		$activity['details'] = "Company is deleted";
		$activity['activity'] = "Delete";
		$activity_log = ActivityLog::saveLog($activity);*/
		$company->forceDelete();
		if (!$company) {
			return response()->json(['success' => false, 'errors' => ['Company not found']]);
		}
		return response()->json(['success' => true]);
	}
	
}