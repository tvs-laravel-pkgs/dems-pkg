<?php
namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\Company;
use Uitoux\EYatra\NState;
use Uitoux\EYatra\Config;
use Validator;
use Yajra\Datatables\Datatables;

class CompanyController extends Controller {

	public function listEYatraCompany(Request $r) {
		$companies = Company::withTrashed()->select(
			'companies.id',
			'companies.code',
			'companies.name',
			'companies.cin_number',
			'operating_states.gst_number',
			'operating_states.address',
			'companies.customer_care_email',
			'companies.customer_care_phone',
			DB::raw('IF(companies.deleted_at IS NULL,"Active","Inactive") as status'),
			'users.name as created_by'
		)
			->join('users', 'companies.created_by', 'users.id')
			->join('operating_states','operating_states.company_id','companies.id')
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
			//$company = Company::with('companyBudgets')->find($id);
			$company = Company::with('operatingStates')->find($id);
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
			foreach($company->operatingStates as $key=>$operating_state){
			 $company->operatingStates[$key]->status=$operating_state->deleted_at?'Inactive':'Active';
			}
		}
		$this->data['financial_year_list'] = $financial_year_list = collect(Config::select('name', 'id')->where('config_type_id', 536)->get());
		$this->data['state_list']=$state_list=NState::select('name','id')->get();
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
				'code.unique' => "Company Code is already taken",
				'name.unique' => "Company Name is already taken",
				'company_budgets.*.financial_year_id.distinct' => 'Same Financial year multiple times entered',
				'operating_states.*.nstate_id.distinct' => 'Same State selected multiple Times',
				'operating_states.*.gst_number.distinct' => 'Same Gstin Number Entered Multiple Times',

			];
			$validator = Validator::make($request->all(), [
				'code' => 'required',
				'name' => 'required',
				'code' => 'required|unique:companies,code,' . $request->id . ',id',
				'name' => 'required|unique:companies,name,' . $request->id . ',id',
				'company_budgets.*.financial_year_id' => [
					'integer',
					'exists:configs,id',
					'distinct',
				],
				'operating_states.*.nstate_id' => [
					'integer',
					'distinct',
				],
				'operating_states.*.gst_number' => [
					'string',
					'distinct',
				],
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
			$company->name=$request->name;
			$company->gstin_enable = $request->gstin_enable;
            $company->fill($request->all());
			$company->save();
			//SAVING OPERATING STATES
			if ($request->operating_states) {
				if (count($request->operating_states) > 0) {
					foreach ($request->operating_states as $operating_state_data) {
						$operating_state = OperatingStates::firstOrNew(['id' => $operating_state_data['id']]);
						if (!$operating_state_data['id']) {
				        $operating_state->created_by = Auth::user()->id;
				        $operating_state->created_at = Carbon::now();
				        $operating_state->updated_at = NULL;
			      } else {
				        $operating_state = OperatingStates::withTrashed()->find($operating_state_data['id']);
				        $operating_state->updated_by = Auth::user()->id;
				        $operating_state->updated_at = Carbon::now();
				      }
			      if ($operating_state_data['status'] == 'Active') {
				        $operating_state->deleted_at = NULL;
				        $operating_state->deleted_by = NULL;
			      } else {
				        $operating_state->deleted_at = date('Y-m-d H:i:s');
				        $operating_state->deleted_by = Auth::user()->id;
			      }
			      $operating_state->company_id=$company->id;
			      $operating_state->nstate_id=$operating_state_data['nstate_id'];
						$operating_state->fill($operating_state_data);
						$operating_state->save();
				}
			}
		}
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
		$operating_state = DB::table('operating_states')->select('nstates.name','operating_states.gst_number','operating_states.legal_name','operating_states.address','operating_states.pincode')->where('operating_states.company_id',$company->id)->leftjoin('nstates','nstates.id','operating_states.nstate_id')->get()->toArray();
		 //dd($operating_state);
		$this->data['name'] = array_column($operating_state, 'name');
		$this->data['gst_number'] = array_column($operating_state, 'gst_number');
		$this->data['legal_name'] = array_column($operating_state, 'legal_name');
		$this->data['address'] = array_column($operating_state, 'address');
		$this->data['pincode'] = array_column($operating_state, 'pincode');
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
	public function validateGstin(Request $r) {
		$gstin_code=NState::where('id',$r->state_id)->pluck('gstin_state_code')->first();
		if(substr($r->gst_number, 0, 2) != $gstin_code){
			   return response()->json(['success' => false, 'errors' => ['The State and Gstin Number Not Matching']]);
		}else{
			$response=app('App\Http\Controllers\AngularController')->verifyGSTIN($r->gst_number,$r->name,true);
			   if(!$response['success']){
			       	return response()->json(['success' => false,'errors' => [$response['error']],]);
		      }
		}
		return response()->json(['success' => true, 'gst_number' => $response]);
	}
}