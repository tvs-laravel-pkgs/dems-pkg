<?php
namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\Company;
use Uitoux\EYatra\Config;
use Validator;
use Yajra\Datatables\Datatables;
use App\Http\Controllers\AngularController;

class CompanyController extends Controller {

	public function listEYatraCompany(Request $r) {
		$companies = Company::withTrashed()->select(
			'companies.id',
			'companies.code',
			'companies.name',
			'companies.address',
			'companies.cin_number',
			'companies.tn_gst_number',
			'companies.puducherry_gst_number',
			'companies.kerala_gst_number',
			'companies.karnataka_gst_number',
			'companies.mp_gst_number',
			'companies.up_gst_number',
			'companies.telangana_gst_number',
			'companies.customer_care_email',
			'companies.customer_care_phone',
			DB::raw('IF(companies.deleted_at IS NULL,"Active","Inactive") as status'),
			'users.name as created_by'
		)
			->join('users', 'companies.created_by', 'users.id')
			->groupBy('companies.id');

		if (!Entrust::can('eyatra-all-company-view')) {
			$companies->where('companies.id', Auth::user()->company_id);
		}
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
				// $action .= '<a style="' . $delete_class . '" href="javascript:;" data-toggle="modal" data-target="#delete_company" onclick="angular.element(this).scope().deleteCompanyConfirm(' . $companies->id . ')" dusk = "delete-btn" title="Delete"><img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" ></a>';

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
			//dd($company);
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
		$this->data['financial_year_list'] = $financial_year_list = collect(Config::select('name', 'id')->where('config_type_id', 536)->get());
		//dd($company);
		$this->data['company'] = $company;
		$this->data['success'] = true;

		return response()->json($this->data);
	}
	public function eyatraCompanyFilterData() {
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function saveEYatraCompany(Request $request) {
		//dd($request->all());
		//validation
		try {
			$error_messages = [
				'code.required' => 'Company Code is Required',
				'name.required' => 'Company Name is Required',
				/*'gst_number.required' => 'GSTIN is Required',
				'gst_number.unique' => 'GSTIN is already taken',*/
				'code.unique' => "Company Code is already taken",
				'name.unique' => "Company Name is already taken",
				'company_budgets.*.financial_year_id.distinct' => 'Same Financial year multiple times entered',

			];
			$validator = Validator::make($request->all(), [
				'code' => 'required',
				'name' => 'required',
				/*'gst_number' => [
                    'required',
                    'min:6',
                ],*/
				'code' => 'required|unique:companies,code,' . $request->id . ',id',
				'name' => 'required|unique:companies,name,' . $request->id . ',id',
				'company_budgets.*.financial_year_id' => [
					'integer',
					'exists:configs,id',
					'distinct',
				],
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
			if(empty($request->tn_gst_number||$request->puducherry_gst_number||$request->kerala_gst_number||$request->karnataka_gst_number||$request->mp_gst_number||$request->telangana_gst_number||$request->up_gst_number)){
				return response()->json([
                        'success' => false,
                        'errors' => [
                          'Enter Atleast one GSTIN Number'
                        ],
                    ]);
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
			$company->tn_gst_number=!empty($request->tn_gst_number) ? $request->tn_gst_number : NULL;
			$company->puducherry_gst_number=!empty($request->puducherry_gst_number) ? $request->puducherry_gst_number : NULL;
			$company->kerala_gst_number=!empty($request->kerala_gst_number) ? $request->kerala_gst_number : NULL;
			$company->karnataka_gst_number=!empty($request->karnataka_gst_number) ? $request->karnataka_gst_number : NULL;
			$company->mp_gst_number=!empty($request->mp_gst_number) ? $request->mp_gst_number : NULL;
			$company->telangana_gst_number=!empty($request->telangana_gst_number) ? $request->telangana_gst_number : NULL;
			$company->up_gst_number=!empty($request->up_gst_number) ? $request->up_gst_number : NULL;
			$company->name=$request->name;
      $company->fill($request->all());
			$company->save();
			$company->companyBudgets()->sync([]);

			//SAVING COMPANY BUDGET
			if ($request->company_budgets) {
				if (count($request->company_budgets) > 0) {
					foreach ($request->company_budgets as $company_budget) {
						$company->companyBudgets()->attach(
							$company_budget['financial_year_id'],
							['outstation_budget_amount' => isset($company_budget['outstation_budget_amount']) ? $company_budget['outstation_budget_amount'] : 0, 'local_budget_amount' => isset($company_budget['local_budget_amount']) ? $company_budget['local_budget_amount'] : 0]
						);
					}
				}
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
		$company_budget = DB::table('company_budget')->select('configs.name as financial_year', DB::raw('format(outstation_budget_amount,2,"en_IN") as outstation_budget_amount'), DB::raw('format(local_budget_amount,2,"en_IN") as local_budget_amount'))->where('company_budget.company_id', $company->id)
			->leftJoin('configs', 'configs.id', 'company_budget.financial_year_id')
			->get()->toArray();
		$this->data['financial_year'] = $lob_name = array_column($company_budget, 'financial_year');
		$this->data['outstation_budget_amount'] = $amount = array_column($company_budget, 'outstation_budget_amount');
		$this->data['local_budget_amount'] = $amount = array_column($company_budget, 'local_budget_amount');

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

		$this->data['action'] = 'View';
		$this->data['company'] = $company;
		$this->data['success'] = true;

		return response()->json($this->data);
	}
	public function deleteEYatraCompany($id) {
		$company = Company::where('id', $id)->first();
		$company->forceDelete();
		if (!$company) {
			return response()->json(['success' => false, 'errors' => ['Company not found']]);
		}
		return response()->json(['success' => true]);
	}
	public function validateTnGstin(Request $r) {
		if (empty($r->tn_gst_number)) {
			return response()->json(['success' => false, 'errors' => ['Gstin is empty']]);
		}elseif(substr($r->tn_gst_number, 0, 2) != 33){
      return response()->json(['success' => false, 'errors' => ['Please Provide TamilNadu Gstin Number']]);
		}else{
			$response=app('App\Http\Controllers\AngularController')->verifyGSTIN($r->tn_gst_number,$r->name,true);
			if(!$response['success']){
				return response()->json([
                        'success' => false,
                        'errors' => [
                          $response['error']
                        ],
                    ]);
			}
		}
		return response()->json(['success' => true,'gst_number'=>$response]);
	}
	public function validatePuducherryGstin(Request $r) {
		if (empty($r->puducherry_gst_number)) {
			return response()->json(['success' => false, 'errors' => ['Gstin is empty']]);
		}elseif(substr($r->puducherry_gst_number, 0, 2) != 34){
      return response()->json(['success' => false, 'errors' => ['Please Provide Puducherry GSTIN Number']]);
		}else{
			$response=app('App\Http\Controllers\AngularController')->verifyGSTIN($r->puducherry_gst_number,$r->name,true);
			if(!$response['success']){
				return response()->json([
                        'success' => false,
                        'errors' => [
                          $response['error']
                        ],
                    ]);
			}
		}
		return response()->json(['success' => true,'gst_number'=>$response]);
	}
	public function validateKeralaGstin(Request $r) {
		if (empty($r->kerala_gst_number)) {
			return response()->json(['success' => false, 'errors' => ['Gstin is empty']]);
		}elseif(substr($r->kerala_gst_number, 0, 2) != 32){
      return response()->json(['success' => false, 'errors' => ['Please Provide Kerala GSTIN Number']]);
		}else{
			$response=app('App\Http\Controllers\AngularController')->verifyGSTIN($r->kerala_gst_number,$r->name,true);
			if(!$response['success']){
				return response()->json([
                        'success' => false,
                        'errors' => [
                          $response['error']
                        ],
                    ]);
			}
		}
		return response()->json(['success' => true,'gst_number'=>$response]);
	}
	public function validateKarnatakaGstin(Request $r) {
		if (empty($r->karnataka_gst_number)) {
			return response()->json(['success' => false, 'errors' => ['Gstin is empty']]);
		}elseif(substr($r->karnataka_gst_number, 0, 2) != 29){
      return response()->json(['success' => false, 'errors' => ['Please Provide Karnataka GSTIN Number']]);
		}else{
			$response=app('App\Http\Controllers\AngularController')->verifyGSTIN($r->karnataka_gst_number,$r->name,true);
			if(!$response['success']){
				return response()->json([
                        'success' => false,
                        'errors' => [
                          $response['error']
                        ],
                    ]);
			}
		}
		return response()->json(['success' => true,'gst_number'=>$response]);
	}
	public function validateMpGstin(Request $r) {
		if (empty($r->mp_gst_number)) {
			return response()->json(['success' => false, 'errors' => ['Gstin is empty']]);
		}elseif(substr($r->mp_gst_number, 0, 2) != 23){
      return response()->json(['success' => false, 'errors' => ['Please Provide Madhya Pradesh GSTIN Number']]);
		}else{
			$response=app('App\Http\Controllers\AngularController')->verifyGSTIN($r->mp_gst_number,$r->name,true);
			if(!$response['success']){
				return response()->json([
                        'success' => false,
                        'errors' => [
                          $response['error']
                        ],
                    ]);
			}
		}
		return response()->json(['success' => true,'gst_number'=>$response]);
	}
	public function validateTelanganaGstin(Request $r) {
		if (empty($r->telangana_gst_number)) {
			return response()->json(['success' => false, 'errors' => ['Gstin is empty']]);
		}elseif(substr($r->gst_number, 0, 2) != 36){
      return response()->json(['success' => false, 'errors' => ['Please Provide Telangana GSTIN Number']]);
		}else{
			$response=app('App\Http\Controllers\AngularController')->verifyGSTIN($r->telangana_gst_number,$r->name,true);
			if(!$response['success']){
				return response()->json([
                        'success' => false,
                        'errors' => [
                          $response['error']
                        ],
                    ]);
			}
		}
		return response()->json(['success' => true,'gst_number'=>$response]);
	}
	public function validateUpGstin(Request $r) {
		if (empty($r->up_gst_number)) {
			return response()->json(['success' => false, 'errors' => ['Gstin is empty']]);
		}elseif(substr($r->up_gst_number, 0, 2) != '09'){
      return response()->json(['success' => false, 'errors' => ['Please Provide Uttar Pradesh GSTIN Number']]);
		}else{
			$response=app('App\Http\Controllers\AngularController')->verifyGSTIN($r->up_gst_number,$r->name,true);
			if(!$response['success']){
				return response()->json([
                        'success' => false,
                        'errors' => [
                          $response['error']
                        ],
                    ]);
			}
		}
		return response()->json(['success' => true,'gst_number'=>$response]);
	}
}