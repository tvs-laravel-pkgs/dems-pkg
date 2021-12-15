<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use App\User;
use Auth;
use Carbon\Carbon;
use DB;
use Entrust;
use Illuminate\Http\Request;
use Uitoux\EYatra\Address;
use Uitoux\EYatra\Agent;
use Uitoux\EYatra\ChequeDetail;
use Uitoux\EYatra\Entity;
use Uitoux\EYatra\NCity;
use Uitoux\EYatra\NCountry;
use Uitoux\EYatra\NState;
use Validator;
use Yajra\Datatables\Datatables;
use phpseclib\Crypt\RSA as Crypt_RSA;

class AgentController extends Controller {

	public function eyatraAgentsfilter() {

		$option = new Entity;
		$option->name = 'Select Travel Mode';
		$option->id = NULL;
		$this->data['tm_list'] = $tm_list = Entity::select('name', 'id')->where('entity_type_id', 502)->where('company_id', Auth::user()->company_id)->get()->keyBy('id');

		$this->data['tm_list'] = $tm_list->prepend($option);

		$this->data['status_list'] = array(
			array('name' => "Select Status", 'id' => null),
			array('name' => "All", 'id' => "-1"),
			array('name' => "Active", 'id' => "2"),
			array('name' => "Inactive", 'id' => "1"),
		);
		return response()->json($this->data);
	}

	public function listEYatraAgent(Request $r) {

		if (!empty($r->agent)) {
			$agent = $r->agent;
		} else {
			$agent = null;
		}

		if (!empty($r->tm)) {
			$tm = $r->tm;
		} else {
			$tm = null;
		}
		if (!empty($r->status_id)) {
			$status = $r->status_id;
		} else {
			$status = null;
		}
		// dd($status);

		$agent_list = Agent::withTrashed()->select(
			'agents.id',
			'agents.code',
			'users.name',
			DB::raw('IF(agents.gstin IS NULL,"---",agents.gstin) as gstin'),
			'users.mobile_number',
			DB::raw('IF(agents.deleted_at IS NULL,"Active","In-Active") as status'),
			DB::raw('GROUP_CONCAT(DISTINCT(tm.name)) as travel_name'))
			->leftJoin('users', 'users.entity_id', 'agents.id')
			->leftJoin('agent_travel_mode', 'agent_travel_mode.agent_id', 'agents.id')
			->leftJoin('entities as tm', 'tm.id', 'agent_travel_mode.travel_mode_id')
			->where('users.user_type_id', 3122)
			->where(function ($query) use ($r, $agent) {
				if (!empty($agent)) {
					$query->where('agents.id', $agent);
				}
			})
			->where(function ($query) use ($r, $tm) {
				if (!empty($tm)) {
					$query->where('tm.id', $tm);
				}
			})
			->where(function ($query) use ($r, $status) {
				if ($status == '2') {
					$query->whereNull('agents.deleted_at');
				} elseif ($status == '1') {
					$query->whereNotNull('agents.deleted_at');
				}
			})
			->where('agents.company_id', Auth::user()->company_id)
			->groupby('agent_travel_mode.agent_id')
			->orderby('agents.id', 'desc')
		;
		return Datatables::of($agent_list)
			->addColumn('action', function ($agent) {

				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2 = asset('public/img/content/yatra/table/view.svg');
				$img2_active = asset('public/img/content/yatra/table/view-active.svg');
				$img3 = asset('public/img/content/yatra/table/delete.svg');
				$img3_active = asset('public/img/content/yatra/table/delete-active.svg');

				$action = '';
				$edit_class = "visibility:hidden";
				if (Entrust::can('eyatra-agent-edit')) {
					$edit_class = "";
				}
				$delete_class = "visibility:hidden";
				if (Entrust::can('eyatra-agent-delete')) {
					$delete_class = "";
				}

				$action .= '<a style="' . $edit_class . '" href="#!/agent/edit/' . $agent->id . '"><img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '"></a> ';
				$action .= '<a href="#!/agent/view/' . $agent->id . '"><img src="' . $img2 . '" alt="View" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '" ></a> ';
				$action .= '<a style="' . $delete_class . '" href="javascript:;" data-toggle="modal" data-target="#agent_confirm_box"
				onclick="angular.element(this).scope().deleteAgentConfirm(' . $agent->id . ')" dusk = "delete-btn" title="Delete"><img src="' . $img3 . '" alt="delete" class="img-responsive" onmouseover=this.src="' . $img3_active . '" onmouseout=this.src="' . $img3 . '" ></a>';

				return $action;
			})
			->addColumn('status', function ($agent) {
				if ($agent->status == 'In-Active') {
					return '<span style="color:#ea4335;">Inactive</span>';
				} else {
					return '<span style="color:#63ce63;">Active</span>';
				}
			})
			->make(true);
	}

	public function eyatraAgentFormData($agent_id = NULL) {
		//dd($agent_id);
		if (!$agent_id) {
			$this->data['action'] = 'Add';
			$agent = new Agent;
			$address = new Address;
			$user = new User;
			$user->password_change = 'yes';
			$this->data['success'] = true;
			$this->data['travel_list'] = [];
		} else {
			$this->data['action'] = 'Edit';
			$agent = Agent::withTrashed()->with('bankDetail', 'walletDetail', 'address', 'address.city', 'address.city.state', 'user', 'chequeDetail')->find($agent_id);

			$user = User::where('entity_id', $agent_id)->where('user_type_id', 3122)->first();
			// dd($user);
			if (!$agent) {
				$this->data['success'] = false;
				$this->data['message'] = 'Agent not found';
			} else {
				$this->data['success'] = true;
			}
			$this->data['travel_list'] = $agent->travelModes()->pluck('travel_mode_id')->toArray();
		}

		$payment_mode_list = collect(Config::paymentModeList())->prepend(['id' => '', 'name' => 'Select Payment Mode']);
		$wallet_mode_list = collect(Entity::walletModeList())->prepend(['id' => '', 'name' => 'Select Wallet Type']);

		$this->data['extras'] = [
			'travel_mode_list' => Entity::travelModeListClaim(),
			'country_list' => NCountry::getList(),

			'state_list' => $this->data['action'] == 'Add' ? [] : NState::getList($agent->address->city->state->country_id),
			'city_list' => $this->data['action'] == 'Add' ? [] : NCity::getList($agent->address->city->state_id),
			'payment_mode_list' => $payment_mode_list,
			'wallet_mode_list' => $wallet_mode_list,
		];
		//dd($agent);
		$this->data['agent'] = $agent;
		$this->data['address'] = $agent->address;
		$this->data['user'] = $user;

		return response()->json($this->data);
	}

	public function saveEYatraAgent(Request $request) {
		//dd($request->all());
		try {
			if (empty(count($request->travel_mode))) {
				return response()->json(['success' => false, 'errors' => ['Travel Mode is Required']]);
			}
			$error_messages = [
				'agent_code.required' => 'Agent Code is Required',
				'agent_code.unique' => 'Agent Code is already taken',
				'agent_name.required' => 'Agent Name is Required',
				'gstin.required' => 'GSTIN is Required',
				'gstin.unique' => 'GSTIN is already taken',
				'address_line1.required' => 'Address Line1 is Required',
				'country.required' => 'Country is Required',
				'state.required' => 'State is Required',
				'city_id.required' => 'City is Required',
				'pincode.required' => 'Pincode is Required',
				'username.required' => "User Name is Required",
				'mobile_number.required' => "Mobile Number is Required",
				'mobile_number.unique' => "Mobile Number is already taken",
			];

			$validator = Validator::make($request->all(), [
				'agent_code' => [
					'required:true',
					'unique:agents,code,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'gstin' => [
					'required:true',
					'unique:agents,gstin,' . $request->id . ',id,company_id,' . Auth::user()->company_id,
				],
				'mobile_number' => [
					'required:true',
					'unique:users,mobile_number,' . $request->user_id . ',id,company_id,' . Auth::user()->company_id,
				],
				'agent_name' => 'required',
				'address_line1' => 'required',
				'country' => 'required',
				'state' => 'required',
				'city_id' => 'required',
				'pincode' => 'required',
				'username' => 'required',
			], $error_messages);
			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}

			if ($request->password_change == 'Yes' && $request->password == '') {
				return response()->json(['success' => false, 'errors' => ['Password is Required']]);
			}

			DB::beginTransaction();
			$company_id = Auth::user()->company_id;
			if (!$request->id) {
				$agent = new Agent;
				$user = new User;
				$address = new Address;
				$agent->created_by = Auth::user()->id;
				$agent->created_at = Carbon::now();
				$agent->updated_at = NULL;
			} else {
				$agent = Agent::withTrashed()->find($request->id);
				$user = User::where('id', $request->user_id)->first();
				$address = Address::where('entity_id', $request->id)->first();
				$agent->updated_by = Auth::user()->id;
				$agent->updated_at = Carbon::now();
			}
			$agent->company_id = $company_id;
			$agent->code = $request->agent_code;
			if ($request->status == 'Active') {
				$agent->deleted_by = NULL;
				$agent->deleted_at = NULL;
			} else if ($request->status == 'Inactive') {
				$agent->deleted_by = Auth()->user()->id;
				$agent->deleted_at = Carbon::now();
			}
			$agent->gstin = $request->gstin;
            
            $gstin = $request->gstin;
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
                //'gstin:33AABCT0159K1ZG',
            ));

            // Return response instead of outputting
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Execute the POST request
            $get_gstin_output_data = curl_exec($ch);

            $get_gstin_output = json_decode($get_gstin_output_data);

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
            $agent->fill($request->all());
			$agent->save();
			//$e_name = EntityType::where('id', $request->type_id)->first();
			$activity['entity_id'] = $agent->id;
			$activity['entity_type'] = "Agent";
			$activity['details'] = empty($request->id) ? "Agent is  Added" : "Agent is  updated";
			$activity['activity'] = empty($request->id) ? "Add" : "Edit";
			$activity_log = ActivityLog::saveLog($activity);
			//ADD ADDRESS
			$address->address_of_id = 3161;
			$address->entity_id = $agent->id;
			$address->name = 'Primary';
			$address->line_1 = $request->address_line1;
			$address->line_2 = $request->address_line2;

			// $address->country_id = $request->country;
			// $address->city_id = $request->city;
			$address->fill($request->all());
			$address->save();

			//ADD USER
			$user->mobile_number = $request->mobile_number;
			$user->name = $request->agent_name;
			$user->entity_type = 0;
			$user->user_type_id = 3122;
			$user->company_id = $company_id;
			$user->name = $request->agent_name;
			$user->entity_id = $agent->id;
			$user->fill($request->all());
			if ($request->password_change == 'Yes') {
				if (!empty($request->user['password'])) {
					$user->password = $request->user['password'];
				}
				$user->force_password_change = 1;
			}
            
			// $user->fill($request->all());
			$user->save();

			$user->roles()->sync([503]);

			$agent->travelModes()->sync($request->travel_mode);

			//BANK DETAIL SAVE
			if ($request->bank_name) {
				$bank_detail = BankDetail::firstOrNew(['entity_id' => $agent->id]);
				$bank_detail->fill($request->all());
				$bank_detail->detail_of_id = 3122;
				$bank_detail->entity_id = $agent->id;
				$bank_detail->account_type_id = 3243;
				$bank_detail->save();
			}
			//CHEQUE DETAIL SAVE
			if ($request->check_favour) {
				$cheque_detail = ChequeDetail::firstOrNew(['entity_id' => $agent->id]);
				// $cheque_detail->fill($request->all());
				$cheque_detail->detail_of_id = 3122;
				$cheque_detail->entity_id = $agent->id;
				$cheque_detail->account_type_id = 3243;
				$cheque_detail->cheque_favour = $request->check_favour;
				$cheque_detail->save();
				// dd($cheque_detail);
			}
			//WALLET SAVE
			if ($request->type_id) {
				$wallet_detail = WalletDetail::firstOrNew(['entity_id' => $agent->id]);
				$wallet_detail->fill($request->all());
				$wallet_detail->wallet_of_id = 3122;
				$wallet_detail->save();
			}

			DB::commit();
			if (empty($request->id)) {
				return response()->json(['success' => true, 'message' => 'Agent Added successfully']);
			} else {
				return response()->json(['success' => true, 'message' => 'Agent Updated Successfully']);
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

	public function viewEYatraAgent($agent_id) {

		// $this->data['agent'] = $agent = Agent::find($agent_id);
		$this->data['agent'] = $agent = Agent::withTrashed()->with('chequeDetail', 'bankDetail', 'walletDetail', 'walletDetail.type', 'address', 'address.city', 'address.city.state')->find($agent_id);
		$this->data['address'] = $address = Address::join('ncities', 'ncities.id', 'ey_addresses.city_id')
			->join('nstates', 'nstates.id', 'ncities.state_id')
			->join('countries as c', 'c.id', 'nstates.country_id')
			->where('entity_id', $agent_id)->where('address_of_id', 3161)
			->select('ey_addresses.*', 'ncities.name as city_name', 'nstates.name as state_name', 'c.name as country_name')
			->first();

		$this->data['user_details'] = $user = User::where('entity_id', $agent_id)->where('user_type_id', 3122)->first();

		$this->data['travel_list'] = $agent->travelModes;

		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function deleteEYatraAgent($agent_id) {

		$user = User::withTrashed()->where('entity_id', $agent_id)->where('user_type_id', 3122)->first();
		if (!$user) {
			return response()->json(['success' => false, 'errors' => ['Agent not found']]);
		}
		$user->forceDelete();
		$agent = Agent::where('id', $agent_id)->first();
		$activity['entity_id'] = $agent->id;
		$activity['entity_type'] = "Agent";
		$activity['details'] = "Agent is Deleted";
		$activity['activity'] = "delete";
		$activity_log = ActivityLog::saveLog($activity);
		$agent->forceDelete();
		if (!$agent) {
			return response()->json(['success' => false, 'errors' => ['Agent not found']]);
		}
		return response()->json(['success' => true]);
	}

}
