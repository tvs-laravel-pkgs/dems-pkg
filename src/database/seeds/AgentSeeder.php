<?php

namespace Uitoux\EYatra\Database\Seeds;
use PHPExcel_IOFactory;
use Validator;
use Uitoux\EYatra\Agent;
use Uitoux\EYatra\Company;
use Uitoux\EYatra\Config;
use Illuminate\Database\Seeder;

class AgentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $objPHPExcel = PHPExcel_IOFactory::load('public/excel-imports/agents.xlsx');
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

		$agents = $sheet->rangeToArray('A2:' . $highestColumn . $highestRow, null, true, false);
		if (!empty($agents)) {
			foreach ($agents as $key => $agentValue) {
				$val = [];
				foreach ($header as $headerKey => $column) {
					if (!$column) {
						continue;
					} else {
						$header_col = str_replace(' ', '_', strtolower($column));
						$val[$header_col] = $agentValue[$headerKey];
					}
				}
				$val = (object) $val;
				try {
					if (empty($val->company)) {
						dump('Record No: ' . ($key + 1) . ' - Company is required');
						continue;
					}if (empty($val->code)) {
						dump('Record No: ' . ($key + 1) . ' - Code is required');
						continue;
					}
					if (empty($val->payment_mode)) {
						dump('Record No: ' . ($key + 1) . ' - Payment Mode is required');
						continue;
					}
					if (empty($val->gstin)) {
						dump('Record No: ' . ($key + 1) . ' - GST is required');
						continue;
					}
					dump($val->company, $val->code, $val->payment_mode, $val->gstin);

					$validator = Validator::make((array) $val, [
						'code' => [
							'string',
							'max:20',
						],
					]);
					if ($validator->fails()) {
						dump('Record No: ' . ($key + 1) . ' ' . implode('', $validator->errors()->all()));
						continue;
					}
					dump($val->company, $val->code, $val->payment_mode, $val->gstin);

					$company = Company::select(
						'id',
						'name'
					)
						->where('name', $val->company)
						->first();
					if (!$company) {
						dump('Record No: ' . ($key + 1) . ' - Company not found');
						continue;
					}

					$payment_mode_details = Config::select(
						'id',
						'name'
					)
						->where('name', $val->payment_mode)
						->first();
					if (!$payment_mode_details) {
						dump('Record No: ' . ($key + 1) . ' - Payment Mode not found');
						continue;
					}

					$agents = new Agent;
					$agents->company_id = $company->id;
					$agents->code = $val->code;
					$agents->payment_mode_id = $payment_mode_details->id;
					$agents->gstin = $val->gstin;
					$agents->created_by = 1;
					$agents->save();
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
