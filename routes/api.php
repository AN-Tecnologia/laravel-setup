<?php

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    Route::prefix('webhooks')->group(function () {
        //
    });

    Route::apiResources([
        //
    ]);
});
