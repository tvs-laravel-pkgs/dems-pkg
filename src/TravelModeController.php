<?php

namespace Uitoux\EYatra;
use App\Http\Controllers\Controller;
use Auth;
use DB;
use Illuminate\Http\Request;
use Uitoux\EYatra\Config;
use Uitoux\EYatra\Entity;
use Validator;
use Yajra\Datatables\Datatables;

class TravelModeController extends Controller {

	public function listEYatraTravelMode(Request $r) {
		$entities = Entity::withTrashed()->from('entities')
			->select(
				'entities.id',
				'entities.entity_type_id',
				'entities.name',
				'c.name as category_name',
				'users.username as created_by',
				DB::raw('IF(updater.username IS NULL,"---",updater.username) as updated_by'),
				DB::raw('IF(deactivator.username IS NULL,"---",deactivator.username) as deleted_by'),
				'entities.created_at',
				//'entities.updated_at',
				DB::raw('IF(entities.updated_at IS NULL,"---",entities.updated_at) as updated_at1'),
				DB::raw('IF(entities.deleted_at IS NULL,"---",entities.deleted_at) as deleted_at'),
				DB::raw('IF(entities.deleted_at IS NULL,"Active","Inactive") as status')
			)

			->join('users', 'users.id', '=', 'entities.created_by')
			->leftjoin('users as updater', 'updater.id', '=', 'entities.updated_by')
			->leftjoin('users as deactivator', 'deactivator.id', '=', 'entities.deleted_by')
			->leftjoin('travel_mode_category_type as tm', 'tm.travel_mode_id', '=', 'entities.id')
			->leftjoin('configs as c', function ($join) {
				$join->on('c.id', '=', 'tm.category_id')
					->where('c.config_type_id', 525);
			})
			->where('entities.company_id', Auth::user()->company_id)
			->where('entities.entity_type_id', 502)
		// ->where('c.config_type_id', 525)
			->orderBy('entities.id', 'desc');

		// dd($entities->get());

		return Datatables::of($entities)
			->addColumn('action', function ($entity) {
				$img1 = asset('public/img/content/yatra/table/edit.svg');
				$img1_active = asset('public/img/content/yatra/table/edit-active.svg');
				$img2 = asset('public/img/content/yatra/table/delete.svg');
				$img2_active = asset('public/img/content/yatra/table/delete-active.svg');
				return '

				<a href="#!/eyatra/travel-mode/edit/' . $entity->id . '">
					<img src="' . $img1 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img1_active . '" onmouseout=this.src="' . $img1 . '">
				</a>
				 <a href="javascript:;"  data-toggle="modal" data-target="#delete_travel_mode" onclick="angular.element(this).scope().deleteTravelMode(' . $entity->id . ')" title="Delete"><img src="' . $img2 . '" alt="Edit" class="img-responsive" onmouseover=this.src="' . $img2_active . '" onmouseout=this.src="' . $img2 . '"></a>';

			})
			->addColumn('status', function ($entity) {
				if ($entity->status == 'Inactive') {
					return '<span style="color:#ea4335;">Inactive</span>';
				} else {
					return '<span style="color:#63ce63;">Active</span>';
				}

			})
			->make(true);
	}

	public function eyatraTravelModeFormData($travel_mode_id = NULL) {

		if (!$travel_mode_id) {
			$entity = new Entity;
			$this->data['action'] = 'Add';
			$entity->status = 'Active';

		} else {
			$entity = Entity::withTrashed()->find($travel_mode_id);
			$entity->category_id = DB::table('travel_mode_category_type')->where('travel_mode_id', $entity->id)->pluck('category_id')->first();
			if (!$entity) {
				return response()->json(['success' => false, 'error' => 'Travel Mode not found']);
			}

			$entity->status = $entity->deleted_at == NULL ? 'Active' : 'Inactive';
			$this->data['action'] = 'Edit';
		}
		$type_list = collect(Config::categoryList())->prepend(['id' => '', 'name' => 'Select Category']);

		$this->data['extras'] = [
			'category_type_list' => $type_list,
		];

		$this->data['entity'] = $entity;
		$this->data['success'] = true;
		return response()->json($this->data);
	}

	public function saveEYatraTravelMode(Request $request) {
		// dd($request->all());

		try {
			$error_messages = [
				'name.required' => 'Name is required',
				'name.unique' => 'Name has already been taken',
			];

			$validator = Validator::make($request->all(), [
				'name' => 'required|unique:entities,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id . ',entity_type_id,502',
			], $error_messages);

			// $validator = Validator::make($request->all(), [
			// 	'name' => [
			// 		'required',

			// 		'unique:entities,name,' . $request->id . ',id,company_id,' . Auth::user()->company_id . ',entity_type_id,' . 502,
			// 		'max:191',
			// 	],

			// ], $error_messages);

			if ($validator->fails()) {
				return response()->json(['success' => false, 'errors' => $validator->errors()->all()]);
			}
			//validate

			DB::beginTransaction();
			//dd($request->all());
			if (!$request->id) {
				$entity = new Entity;
				$entity->created_by = Auth::user()->id;
				$entity->updated_at = NULL;

			} else {
				$entity = Entity::withTrashed()->find($request->id);
				if (!$entity) {
					return response()->json(['success' => false, 'errors' => ['Travel Mode not found']]);
				}
				$entity->updated_by = Auth::user()->id;
			}

			$entity->company_id = Auth::user()->company_id;
			$entity->entity_type_id = 502;
			$entity->name = $request->name;

			if ($request->status == 0) {
				$entity->deleted_at = date('Y-m-d H:i:s');
				$entity->deleted_by = Auth::user()->id;
			} else {
				$entity->deleted_by = NULL;
				$entity->deleted_at = NULL;
			}
			$entity->save();
			$e_name = DB::table('entity_types')->where('id', $entity->entity_type_id)->first();
			$activity['entity_id'] = $entity->id;
			$activity['entity_type'] = "Travel Modes"; //entity_type_id =511
			$activity['details'] = empty($request->id) ? "Travel Mode is added" : "Travel Mode is Updated";
			$activity['activity'] = empty($request->id) ? "add" : "edit";
			$activity_log = ActivityLog::saveLog($activity);
			//SAVING travel_mode_category

			$entity->categories()->sync($request->category_id);
			DB::commit();
			if (empty($request->id)) {
				return response()->json(['success' => true, 'message' => 'Travel Mode added successfully']);
			} else {
				return response()->json(['success' => true, 'message' => 'Travel Mode updated successfully']);
			}
		} catch (Exception $e) {
			DB::rollBack();
			return response()->json(['success' => false, 'errors' => [$e->getMessage()]]);
		}
	}

	public function deleteEYatraTravelMode($travel_mode_id) {
		$entity = Entity::withTrashed()->where('id', $travel_mode_id)->first();
		$e_name = DB::table('entity_types')->where('id', $entity->entity_type_id)->first();
		$activity['entity_id'] = $entity->id;
		$activity['entity_type'] = "Travel Modes"; //entity_type_id =511
		$activity['details'] = "Travel Mode is deleted";
		$activity['activity'] = "delete";
		$activity_log = ActivityLog::saveLog($activity);
		$entity->forceDelete();
		if (!$entity) {
			return response()->json(['success' => false, 'errors' => ['Travel Mode not found']]);
		}

		return response()->json(['success' => true]);
	}

}
