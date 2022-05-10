<?php

use Illuminate\Support\Facades\Route;
use ChinhlePa\Attestation\Http\Controllers\AttestationController;

Route::group(['prefix' => 'api'], function(){
    Route::post('/via-attestation', [AttestationController::class, 'verifyAttestation']);
});


// Route::group(['prefix' => 'api', 'middleware' => 'api'], function() {

//     Route::get('/users', function () {
//         $users = \App\User::all();
//         return response()->json($users);
//     });

//     Route::post('/users', function () {
//         $users = \App\User::all();
//         return response()->json($users);
//     });

// });