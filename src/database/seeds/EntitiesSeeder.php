<?php

use App\Company;
use App\Entity;
use App\EntityType;
use Auth;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use PHPExcel_IOFactory;

class EntitiesSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {
		$objPHPExcel = PHPExcel_IOFactory::load('public/excel-imports/entities.xlsx');
		$sheet = $objPHPExcel->getSheet(0);
		$highestRow = $sheet->getHighestDataRow();
		$highestColumn = $sheet->getHighestDataColumn();
		$header = $sheet->rangeToArray('A1:' . $highestColumn . '1', null, true, false);
		$header = $header[0];
		foreach ($header as $key => $column) {
			if ($column == null) {
				unset($header[$key]);
			}
		}

		$entities = $sheet->rangeToArray('A2:' . $highestColumn . $highestRow, null, true, false);
		if (!empty($entities)) {
			foreach ($entities as $key => $entitiesValue) {
				$val = [];
				foreach ($header as $headerKey => $column) {
					if (!$column) {
						continue;
					} else {
						$header_col = str_replace(' ', '_', strtolower($column));
						$val[$header_col] = $entitiesValue[$headerKey];
					}
				}
				$val = (object) $val;
				try {
					if (empty($val->company)) {
						dump('Record No: ' . ($key + 1) . ' - Company is required');
						continue;
					}if (empty($val->entity_type)) {
						dump('Record No: ' . ($key + 1) . ' - Entity Type is required');
						continue;
					}
					if (empty($val->name)) {
						dump('Record No: ' . ($key + 1) . ' - Name is required');
						continue;
					}if (empty($val->display_order)) {
						dump('Record No: ' . ($key + 1) . ' - Display Order is required');
						continue;
					}
					dump($val->company, $val->entity_type, $val->name, $val->display_order);

					$validator = Validator::make((array) $val, [
						'company' => [
							'string',
							'max:20',
						],
						'entity_type' => [
							'string',
							'max:20',
						],
						'name' => [
							'string',
							'max:255',
						],
						'display_order' => [
							'string',
							'max:20',
						],
					]);
					if ($validator->fails()) {
						dump('Record No: ' . ($key + 1) . ' ' . implode('', $validator->errors()->all()));
						continue;
					}
					dump($val->company, $val->entity_type, $val->name, $val->display_order);

					$company = company::select(
						'id',
						'name'
					)
						->where('name', $val->company)
						->first();
					if (!$company) {
						dump('Record No: ' . ($key + 1) . ' - Company not found');
						continue;
					}

					$entity_type = EntityType::select(
						'id',
						'name'
					)
						->where('name', $val->entity_type)
						->first();
					if (!$entity_type) {
						dump('Record No: ' . ($key + 1) . ' - Travel Mode not found');
						continue;
					}

					$entities = new Entity;
					$entities->company_id = $company->id;
					$entities->entity_type_id = $entity_type->id;
					$entities->name = $val->name;
					$entities->display_order = $val->display_order;
					$entities->created_by = Auth::user()->id;
					$entities->updated_by = Auth::user()->id;
					$entities->created_at = Carbon::now();
					$entities->updated_at = Carbon::now();
					$entities->save();
					dump(' === updated === ');

				} catch (\Exception $e) {
					dump($e);
					continue;
				}
			}
			dd(' == completed ==');
		}
	}
}