<?php

namespace Uitoux\EYatra\Database\Seeds;
use PHPExcel_IOFactory;
use Validator;
use Uitoux\EYatra\Agent;
use Uitoux\EYatra\Entity;
use Uitoux\EYatra\NState;
use App\StateAgentTravelMode;
use Illuminate\Database\Seeder;

class StateAgentTravelModeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		$objPHPExcel = PHPExcel_IOFactory::load('public/excel-imports/state_agent_travel_mode.xlsx');
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

		$state_agent_travel_mode = $sheet->rangeToArray('A2:' . $highestColumn . $highestRow, null, true, false);
		if (!empty($state_agent_travel_mode)) {
			foreach ($state_agent_travel_mode as $key => $state_agent_travel_modeValue) {
				$val = [];
				foreach ($header as $headerKey => $column) {
					if (!$column) {
						continue;
					} else {
						$header_col = str_replace(' ', '_', strtolower($column));
						$val[$header_col] = $state_agent_travel_modeValue[$headerKey];
					}
				}
				$val = (object) $val;
				try {
					if (empty($val->agent)) {
						dump('Record No: ' . ($key + 1) . ' - Company is required');
						continue;
					}if (empty($val->state)) {
						dump('Record No: ' . ($key + 1) . ' - State is required');
						continue;
					}
					if (empty($val->travel_mode)) {
						dump('Record No: ' . ($key + 1) . ' - Travel Mode is required');
						continue;
					}if (empty($val->service_charge)) {
						dump('Record No: ' . ($key + 1) . ' - Service Charge is required');
						continue;
					}
					dump($val->agent, $val->state, $val->travel_mode, $val->service_charge);

					$validator = Validator::make((array) $val, [
						'travel_mode' => [
							'string',
							'max:20',
						],
						'state' => [
							'string',
							'max:20',
						]
					]);
					if ($validator->fails()) {
						dump('Record No: ' . ($key + 1) . ' ' . implode('', $validator->errors()->all()));
						continue;
					}

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

					$states = NState::select(
						'id',
						'code',
						'name'
					)
						->where('code', $val->state)
						->first();

					if (!$states) {
						dump('Record No: ' . ($key + 1) . ' - NState not found');
						continue;
					}

					dump($agents->id, $states->id, $travel_mode_details->id, $val->service_charge);

					$state_agent_travel_mode = new StateAgentTravelMode;
					$state_agent_travel_mode->agent_id = $agents->id;
					$state_agent_travel_mode->state_id = $states->id;
					$state_agent_travel_mode->travel_mode_id = $travel_mode_details->id;
					$state_agent_travel_mode->service_charge = $val->service_charge;
					$state_agent_travel_mode->save();
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
