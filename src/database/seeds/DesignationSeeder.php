<?php

namespace Uitoux\EYatra\Database\Seeds;
use PHPExcel_IOFactory;
use Validator;
use Uitoux\EYatra\Company;
use Uitoux\EYatra\Designation;
use Uitoux\EYatra\Entity;
use Illuminate\Database\Seeder;

class DesignationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		$objPHPExcel = PHPExcel_IOFactory::load('public/excel-imports/designations.xlsx');
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

		$designation = $sheet->rangeToArray('A2:' . $highestColumn . $highestRow, null, true, false);
		if (!empty($designation)) {
			foreach ($designation as $key => $designationValue) {
				$val = [];
				foreach ($header as $headerKey => $column) {
					if (!$column) {
						continue;
					} else {
						$header_col = str_replace(' ', '_', strtolower($column));
						$val[$header_col] = $designationValue[$headerKey];
					}
				}
				$val = (object) $val;
				try {
					if (empty($val->company)) {
						dump('Record No: ' . ($key + 1) . ' - Company is required');
						continue;
					}if (empty($val->name)) {
						dump('Record No: ' . ($key + 1) . ' - Name is required');
						continue;
					}
					if (empty($val->grade)) {
						dump('Record No: ' . ($key + 1) . ' - Grade name is required');
						continue;
					}
					dump($val->company, $val->name, $val->grade);

					$validator = Validator::make((array) $val, [
						'name' => [
							'string',
							'max:255',
						],
					]);
					if ($validator->fails()) {
						dump('Record No: ' . ($key + 1) . ' ' . implode('', $validator->errors()->all()));
						continue;
					}
					dump($val->company, $val->name, $val->grade);

					$company = Company::select(
						'id',
						'name'
					)
						->where('id', $val->company)
						->first();
					if (!$company) {
						dump('Record No: ' . ($key + 1) . ' - Company not found');
						continue;
					}

					$grade = Entity::select(
						'id',
						'name'
					)
						->where('name', $val->grade)
						->first();
dd($company->id,$val->name,$grade->id);
					$existing_designation = Designation::where('company_id',$company->id)
					->where('name',$val->name)
					->where('grade_id',$grade->id)->first();
					
					if ($existing_designation) {
						dump('Record No: ' . ($key + 1) . ' - Designation Already Exist');
						continue;
					}

					$designation = new Designation;
					$designation->company_id = $company->id;
					$designation->name = $val->name;
					$designation->grade_id = $grade->id;
					$designation->created_by = 1;
					$designation->save();
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
