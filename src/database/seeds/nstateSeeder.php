<?php

namespace Uitoux\EYatra\Database\Seeds;

use Carbon\Carbon;
use Illuminate\Database\Seeder;
use PHPExcel_IOFactory;
use Uitoux\EYatra\NCountry;
use Uitoux\EYatra\NState;

class NstateSeeder extends Seeder {
	/**
	 * Run the database seeds.
	 *
	 * @return void
	 */
	public function run() {

		$objPHPExcel = PHPExcel_IOFactory::load('public/excel-imports/nstates.xlsx');
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

		$nstates = $sheet->rangeToArray('A2:' . $highestColumn . $highestRow, null, true, false);
		if (!empty($nstates)) {
			foreach ($nstates as $key => $nstateValue) {
				$val = [];
				foreach ($header as $headerKey => $column) {
					if (!$column) {
						continue;
					} else {
						$header_col = str_replace(' ', '_', strtolower($column));
						$val[$header_col] = $nstateValue[$headerKey];
					}
				}
				$val = (object) $val;
				try {
					if (empty($val->country)) {
						dump('Record No: ' . ($key + 1) . ' - Country is required');
						continue;
					}

					if (empty($val->code)) {
						dump('Record No: ' . ($key + 1) . ' - Code is required');
						continue;
					}

					if (empty($val->name)) {
						dump('Record No: ' . ($key + 1) . ' - Name is required');
						continue;
					}

					if (empty($val->gstin_state_code)) {
						dump('Record No: ' . ($key + 1) . ' - GSTIN state code is required');
						continue;
					}

					if (empty($val->axapta_cgst_code)) {
						dump('Record No: ' . ($key + 1) . ' - Axapta CGST code is required');
						continue;
					}

					if (empty($val->axapta_sgst_code)) {
						dump('Record No: ' . ($key + 1) . ' - Axapta SGST code is required');
						continue;
					}

					if (empty($val->axapta_igst_code)) {
						dump('Record No: ' . ($key + 1) . ' - Axapta IGST code is required');
						continue;
					}

					dump($val->country, $val->code, $val->name);

					$country = NCountry::select([
						'id',
					])
						->where('name', $val->country)
						->first();

					if (!$country) {
						dump('Record No: ' . ($key + 1) . ' - Country not found');
						continue;
					}

					$nstate = NState::firstOrNew([
						'country_id' => $country->id,
						'code' => $val->code,
						'name' => $val->name,
					]);

					if ($nstate->exists) {
						$nstate->updated_at = Carbon::now();
						$nstate->updated_by = 1;
					} else {
						$nstate->created_at = Carbon::now();
						$nstate->created_by = 1;
					}

					$nstate->gstin_state_code = $val->gstin_state_code;
					$nstate->axapta_cgst_code = $val->axapta_cgst_code;
					$nstate->axapta_sgst_code = $val->axapta_sgst_code;
					$nstate->axapta_igst_code = $val->axapta_igst_code;
					$nstate->save();

					dump(' === success === ');

				} catch (\Exception $e) {
					dump($e);
					continue;
				}
			}
		}
		dd(' == completed ==');
	}
}
