<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 9/6/18
 * Time: 5:56 PM
 */

use Illuminate\Support\Facades\Route;

Route::group(['namespace' => 'Fynduck\Post\Controllers'], function () {
    Route::get('post', 'PostController@pagePost')->name('page-post');
    Route::get('get-items', 'PostController@getItems')->name('get-items');
    /**
     * Generate test posts
     */
    Route::get('generate-items', 'PostController@generateData')->name('generate-items');
});
