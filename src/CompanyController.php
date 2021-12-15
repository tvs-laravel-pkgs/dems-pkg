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
use phpseclib\Crypt\RSA as Crypt_RSA;

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
				'gst_number.required' => 'GSTIN is Required',
				'gst_number.unique' => 'GSTIN is already taken',
				'code.unique' => "Company Code is already taken",
				'name.unique' => "Company Name is already taken",
				'company_budgets.*.financial_year_id.distinct' => 'Same Financial year multiple times entered',

			];
			$validator = Validator::make($request->all(), [
				'code' => 'required',
				'name' => 'required',
				'gst_number' => [
                    'required',
                    'min:6',
                ],
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
			$company->gst_number = $request->gst_number;
            
            $gstin = $request->gst_number;
            $errors = [];
            if (!$gstin) {
                return response()->json([
                    'success' => false,
                    'error' => 'GSTIN is Empty!',
                ]);
            }
            $rsa = new Crypt_RSA;
            $encrypter = app('Illuminate\Contracts\Encryption\Encrypter');
            $public_key = 'MIICIjANBgkqhkiG9w0BAQEFAAOCAg8AMIICCgKCAgEAxqHazGS4OkY/bDp0oklL+Ser7EpTpxyeMop8kfBlhzc8dzWryuAECwu8i/avzL4f5XG/DdSgMz7EdZCMrcxtmGJlMo2tUqjVlIsUslMG6Cmn46w0u+pSiM9McqIvJgnntKDHg90EIWg1BNnZkJy1NcDrB4O4ea66Y6WGNdb0DxciaYRlToohv8q72YLEII/z7W/7EyDYEaoSlgYs4BUP69LF7SANDZ8ZuTpQQKGF4TJKNhJ+ocmJ8ahb2HTwH3Ol0THF+0gJmaigs8wcpWFOE2K+KxWfyX6bPBpjTzC+wQChCnGQREhaKdzawE/aRVEVnvWc43dhm0janHp29mAAVv+ngYP9tKeFMjVqbr8YuoT2InHWFKhpPN8wsk30YxyDvWkN3mUgj3Q/IUhiDh6fU8GBZ+iIoxiUfrKvC/XzXVsCE2JlGVceuZR8OzwGrxk+dvMnVHyauN1YWnJuUTYTrCw3rgpNOyTWWmlw2z5dDMpoHlY0WmTVh0CrMeQdP33D3LGsa+7JYRyoRBhUTHepxLwk8UiLbu6bGO1sQwstLTTmk+Z9ZSk9EUK03Bkgv0hOmSPKC4MLD5rOM/oaP0LLzZ49jm9yXIrgbEcn7rv82hk8ghqTfChmQV/q+94qijf+rM2XJ7QX6XBES0UvnWnV6bVjSoLuBi9TF1ttLpiT3fkCAwEAAQ=='; //PROVIDE FROM BDO COMPANY

            $clientid = "61b27a26bd86cbb93c5c11be0c2856"; //LIVE

            $rsa->loadKey($public_key);
            $rsa->setEncryptionMode(2);
            $client_encryption_key = '7dd55886594bccadb03c48eb3f448e'; // LIVE
            
            $ClientSecret = $rsa->encrypt($client_encryption_key);
            $clientsecretencrypted = base64_encode($ClientSecret);
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
            $app_secret_key = substr(str_shuffle($characters), 0, 32); // RANDOM KEY GENERATE
            $AppSecret = $rsa->encrypt($app_secret_key);
            $appsecretkey = base64_encode($AppSecret);
            $bdo_login_url = 'https://sandboxeinvoiceapi.bdo.in/bdoauth/bdoauthenticate';
            $bdo_login_url = 'https://einvoiceapi.bdo.in/bdoauth/bdoauthenticate'; //LIVE
            
            $ch = curl_init($bdo_login_url);
            // Setup request to send json via POST`
            $params = json_encode(array(
                'clientid' => $clientid,
                'clientsecretencrypted' => $clientsecretencrypted,
                'appsecretkey' => $appsecretkey,
            ));

            // Attach encoded JSON string to the POST fields
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params);

            // Set the content type to application/json
            curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));

            // Return response instead of outputting
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Execute the POST request
            $server_output_data = curl_exec($ch);

            // Get the POST request header status
            $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            // dd($server_output_data);
            // If header status is not Created or not OK, return error message
            if ($status != 200) {
                $errors[] = 'Connection Error!';
                return response()->json(['success' => false, 'error' => 'Connection Error!']);
            }

            curl_close($ch);

            $server_output = json_decode($server_output_data);

            if ($server_output->status == 0) {
                $errors[] = 'Something went on Server.Please Try again later!!';
                return response()->json([
                    'success' => false,
                    'error' => $server_output->ErrorMsg,
                    'errors' => $errors,
                ]);
            }
            $expiry = $server_output->expiry;
            $bdo_authtoken = $server_output->bdo_authtoken;
            $status = $server_output->status;
            $bdo_sek = $server_output->bdo_sek;

            //DECRYPT WITH APP KEY AND BDO SEK KEY
            $decrypt_data_with_bdo_sek =$this->decryptAesData($app_secret_key, $bdo_sek);
            if (!$decrypt_data_with_bdo_sek) {
                $errors[] = 'Decryption Error!';
                return response()->json(['success' => false, 'error' => 'Decryption Error!']);
            }
            $bdo_check_gstin_url = 'https://einvoiceapi.bdo.in/bdoapi/public/syncGstinDetailsFromCP/' . $gstin; //LIVE
            //dd($bdo_check_gstin_url);

            $ch = curl_init($bdo_check_gstin_url);
            // Setup request to send json via POST`

            // Attach encoded JSON string to the POST fields
            curl_setopt($ch, CURLOPT_URL, $bdo_check_gstin_url);

            // Set the content type to application/json
            $params = json_encode(array(
                'Content-Type' => 'application/json',
                'client_id' => $clientid,
                'bdo_authtoken' => $bdo_authtoken,
                // 'gstin: ' . $r->outlet_gstin,
                'gstin' =>$gstin,
            ));

            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'client_id:' . $clientid,
                'bdo_authtoken:' . $bdo_authtoken,
                 'gstin:'.$gstin,
                //'gstin'=>$gstin,
            ));

            // Return response instead of outputting
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Execute the POST request
            $get_gstin_output_data = curl_exec($ch);

            $get_gstin_output = json_decode($get_gstin_output_data);
            //dd($get_gstin_output);
            

            if ($get_gstin_output->Status == 0) {
                $errors[] = 'Invalid GSTIN for this user!!';
                return response()->json([
                    'success' => false,
                    'error' => $get_gstin_output->Error,
                    'errors' => $errors,
                ]);
            }
            curl_close($ch);
            //AES DECRYPTION AFTER GENERATE IRN (DECRYPT WITH DECRYPT ENCODED DATA FROM AES DECRYPTION AND GSTIN DATA RESPONSE)
            $gstin_decrypt_data = $this->decryptAesData($decrypt_data_with_bdo_sek, $get_gstin_output->Data);
            if (!$gstin_decrypt_data) {
                $errors[] = 'Decryption Error!';
                return response()->json(['success' => false, 'error' => 'Decryption Error!']);
            }
            $gst_validate = json_decode($gstin_decrypt_data, true);
            if ($gst_validate) {
                if (key($gst_validate) == 'ErrorCodes') {
                    $errors[] = isset($gst_validate['ErrorMsg']) ? $gst_validate['ErrorMsg'] : 'Something went on Server.Please Try again later!!';
                    return response()->json([
                        'success' => false,
                        'error' => 'GSTIN Validation Error!',
                        'errors' => $errors,
                    ]);
                }
            }
            
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
	public static function encryptAesData($encryption_key, $data) {
		$method = 'aes-256-ecb';

		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));

		$encrypted = openssl_encrypt($data, $method, $encryption_key, 0, $iv);

		return $encrypted;
	}

	public static function decryptAesData($encryption_key, $data) {
		$method = 'aes-256-ecb';

		$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));

		$decrypted = openssl_decrypt(base64_decode($data), $method, $encryption_key, OPENSSL_RAW_DATA, $iv);
		return $decrypted;
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

}