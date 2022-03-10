<?php

namespace Uitoux\EYatra\Database\Seeds;
use PHPExcel_IOFactory;
use Validator;
use Uitoux\EYatra\Agent;
use App\AgentTravelMode;
use Uitoux\EYatra\Entity;
use Illuminate\Database\Seeder;

class AgentTravelModeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		$objPHPExcel = PHPExcel_IOFactory::load('public/excel-imports/agent_travel_mode.xlsx');
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

		$agent_travel_mode = $sheet->rangeToArray('A2:' . $highestColumn . $highestRow, null, true, false);
		if (!empty($agent_travel_mode)) {
			foreach ($agent_travel_mode as $key => $agent_travel_modeValue) {
				$val = [];
				foreach ($header as $headerKey => $column) {
					if (!$column) {
						continue;
					} else {
						$header_col = str_replace(' ', '_', strtolower($column));
						$val[$header_col] = $agent_travel_modeValue[$headerKey];
					}
				}
				$val = (object) $val;
				try {
					if (empty($val->agent)) {
						dump('Record No: ' . ($key + 1) . ' - Company is required');
						continue;
					}if (empty($val->travel_mode)) {
						dump('Record No: ' . ($key + 1) . ' - Travel Mode is required');
						continue;
					}
					dump($val->agent, $val->travel_mode);

					$validator = Validator::make((array) $val, [
						'travel_mode' => [
							'string',
							'max:20',
						],
					]);
					if ($validator->fails()) {
						dump('Record No: ' . ($key + 1) . ' ' . implode('', $validator->errors()->all()));
						continue;
					}
					dump($val->agent, $val->travel_mode);

					$agents = Agent::select(
						'id',
						'code'
					)
						->where('code', $val->agent)
						->first();
					if (!$agents) {
						dump('Record No: ' . ($key + 1) . ' - Agent not found');
						continue;
					}

					$travel_mode_details = Entity::select(
						'id',
						'name'
					)
						->where('name', $val->travel_mode)
						->first();
					if (!$travel_mode_details) {
						dump('Record No: ' . ($key + 1) . ' - Travel Mode not found');
						continue;
					}

					$agent_travel_mode = new AgentTravelMode;
					$agent_travel_mode->agent_id = $agents->id;
					$agent_travel_mode->travel_mode_id = $travel_mode->id;
					$agent_travel_mode->save();
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
