<?php

Route::group(array('prefix' => 'fingerprints'), function()
{
    Route::get('ac', function()
    {
        if(Input::has('vc')){
            $data = Kamfret::getDeviceByVc(Input::get('vc'));

            echo $data['ac'] . $data['sn'];
        }
    });

    // Get fingerprint registration URL 
    Route::get('register/{id}', function($id)
    {
        echo Kamfret::registerUrl($id);
    });

    // validate fingerprint registration, if success return URL redirect
    Route::post('register/{id}', function($id)
    {
        $result = Kamfret::register($id, Input::get('RegTemp'));
        $response = Event::fire('fingerprints.register', array($result));

    });

    // Get fingerprint verification URL
    Route::get('verify/{id}', function($id)
    {
        $user = User::findOrFail($id);
        echo Kamfret::verificationUrl($user, Input::all());
    });

    Route::post('verify/{id}', function($id)
    {
        $result = Kamfret::verify($id, Input::get('VerPas'));

        // set action for this verification, default to login
        $result['extras'] = Input::all();

        // Let's tell laravel result of our verification
        $response = Event::fire('fingerprints.verify', array($result));
    });
});