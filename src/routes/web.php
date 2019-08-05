<?php

Route::group(['middleware' => ['web', 'auth']], function () {

	//EMPLOYEES

	//GRADES
	Route::get('eyatra/grade/get-list', 'Uitoux\EYatra\GradeController@listEYatraGrade')->name('listEYatraGrade');
	Route::get('eyatra/grade/get-form-data/{grade_id?}', 'Uitoux\EYatra\GradeController@eyatraGradeFormData')->name('eyatraGradeFormData');
	Route::post('eyatra/grade/save', 'Uitoux\EYatra\GradeController@saveEYatraGrade')->name('saveEYatraGrade');
	Route::get('eyatra/grade/view/{grade_id}', 'Uitoux\EYatra\GradeController@viewEYatraGrade')->name('viewEYatraGrade');
	Route::get('eyatra/grade/delete/{grade_id}', 'Uitoux\EYatra\GradeController@deleteEYatraGrade')->name('deleteEYatraGrade');

	//AGENTS
	Route::get('eyatra/agent/get-list', 'Uitoux\EYatra\AgentController@listEYatraAgent')->name('listEYatraAgent');
	Route::get('eyatra/agent/get-form-data/{agent_id?}', 'Uitoux\EYatra\AgentController@eyatraAgentFormData')->name('eyatraGradeFormData');
	Route::post('eyatra/agent/save', 'Uitoux\EYatra\AgentController@saveEYatraAgent')->name('saveEYatraAgent');
	Route::get('eyatra/agent/view/{agent_id}', 'Uitoux\EYatra\AgentController@viewEYatraAgent')->name('viewEYatraAgent');
	Route::get('eyatra/agent/delete/{agent_id}', 'Uitoux\EYatra\AgentController@deleteEYatraAgent')->name('deleteEYatraAgent');

	//TRIPS
	Route::get('eyatra/trip/get-list', 'Uitoux\EYatra\TripController@listTrip')->name('listTrip');
	Route::get('eyatra/trip/get-form-data/{trip_id?}', 'Uitoux\EYatra\TripController@tripFormData')->name('tripFormData');
	Route::post('eyatra/trip/save', 'Uitoux\EYatra\TripController@saveTrip')->name('saveTrip');
	Route::get('eyatra/trip/view/{trip_id}', 'Uitoux\EYatra\TripController@viewTrip')->name('viewTrip');
	Route::get('eyatra/trip/delete/{trip_id}', 'Uitoux\EYatra\TripController@deleteTrip')->name('deleteTrip');
	Route::get('eyatra/trip/verification-request/{trip_id}', 'Uitoux\EYatra\TripController@tripVerificationRequest')->name('tripVerificationRequest');

	//TRIPS VERIFICATION
	Route::get('eyatra/trip/verification/get-list', 'Uitoux\EYatra\TripVerificationController@listTripVerification')->name('listTripVerification');
	Route::get('eyatra/trip/verification/get-form-data/{trip_id?}', 'Uitoux\EYatra\TripVerificationController@tripVerificationFormData')->name('tripVerificationFormData');
	Route::post('eyatra/trip/verification/save', 'Uitoux\EYatra\TripVerificationController@saveTripVerification')->name('saveTripVerification');

	//HELPERS
	Route::post('eyatra/city/search', 'Uitoux\EYatra\CityController@searchCity')->name('searchCity');
});