<?php

namespace Uitoux\EYatra;
use Uitoux\EYatra\Outlet;
use DB;
use Auth;
use Illuminate\Database\Eloquent\Model;

class Sbu extends Model {
	protected $table = 'sbus';
	protected $fillable = [
		// 'id',
		'lob_id',
		'name',
	];

	public function lob() {
		return $this->belongsTo('Uitoux\EYatra\Lob');
	}

	public static function getList($r) {
		//dd($r->all());
			$outlet=Outlet::find($r->outlet_id);
			if(!$outlet){
				$outlet=New Outlet();
			}
			$ckecked_lob_ids_unique=array_unique($r->lob_ids);
			$data['sbu_outlet'] = Sbu::select('name','id')
			->whereIn('lob_id', $ckecked_lob_ids_unique)
			->orderBy('id')
			->get()
			;
			$checked_sbu_ids=[];
			$ckecked_sbu_ids_unique=[];
			if(count($outlet->outletBudgets) > 0){
				foreach ($outlet->outletBudgets as $key => $outlet_budget) {
					$checked_sbu_ids[]=$outlet_budget->id;
				}
			}
			$ckecked_sbu_ids_unique=array_unique($checked_sbu_ids);
			foreach ($data['sbu_outlet'] as $key1 => $sbu) {
					if(in_array($sbu->id,$ckecked_sbu_ids_unique)){
						//dump('true');
						$outlet_budget=DB::table('outlet_budget')
						->select('sbu_id',
							'outstation_budget_amount',
							'local_budget_amount'
						)
						->where('outlet_id',$outlet->id)
						->where('sbu_id',$sbu->id)
						->first();
						$data['sbu_outlet'][$key1]->checked = true;
						$data['sbu_outlet'][$key1]->sbu_id = $sbu->id;
						$data['sbu_outlet'][$key1]->outstation_budget_amount = $outlet_budget->outstation_budget_amount;
						$data['sbu_outlet'][$key1]->local_budget_amount = $outlet_budget->local_budget_amount;

					}else{
						//dump('false');
						$data['sbu_outlet'][$key1]->checked = false;
						$data['sbu_outlet'][$key1]->sbu_id = $sbu->id;
						$data['sbu_outlet'][$key1]->outstation_budget_amount = '';
						$data['sbu_outlet'][$key1]->local_budget_amount = '';
					}
			
			}
		return $data;
	}

	public static function getSbuList() {
		$list = Collect(
				Sbu::select(
					'sbus.id',
					DB::raw('CONCAT(sbus.name) as name')
				)->join('lobs', 'lobs.id', 'sbus.lob_id')
				->where('lobs.functional_support', 0)
				->where('lobs.company_id', Auth::user()->company_id)
				->orderBy('sbus.id', 'ASC')
				->get()
			)->prepend(['id' => null, 'name' => 'Select Any Debit to Business Unit']);
		return $list;
	}

}
