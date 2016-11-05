# FlexcodeSDK for Laravel 4

*Unmantained*. 
I'm currently have no time to maintain this repo. If you are interested in maintaining this repo, please let me know. 

## Installation
Begin by installing this package via Composer. Edit your project's `composer.json` file to require `buchin/kamfret`

    "require": {
            "laravel/framework": "4.2.*",
            "zizaco/confide": "~4.0@dev",
            .
            .
            .
            "buchin/kamfret": "0.1"
        }

Next, update Composer through terminal:

    composer update

Next, open `app/config/app.php`, and add a new item to the providers array.

    'Buchin\Kamfret\KamfretServiceProvider'

Don't forget to add Kamfret in your Alias list in app/config/app.php

    'Kamfret'    => 'Buchin\Kamfret\Facades\Kamfret'

Then publish buchin/kamfret config

    php artisan config:publish buchin/kamfret

Edit `app/config/packages/buchin/kamfret/config.php` register your fingerprint device license like:
    
    'devices' => array(
        // add device here
        array(
            'name' => 'device_name_1',
            'sn' => 'Device Serial Number',
            'vc' => 'Device Verification Code',
            'ac' => 'Device Authentication Code',
            'vkey' => 'Device Verification Key',
        ),

        array(
            'name' => 'device_name_2',
            'sn' => 'Device Serial Number',
            'vc' => 'Device Verification Code',
            'ac' => 'Device Authentication Code',
            'vkey' => 'Device Verification Key',
        ),
    ), 

You may add multiple device using array. 
If you need device license, please watch this video on how to get those device license: https://www.youtube.com/watch?v=8a8R4htmkVo

Now run a migration to the users table. We assume you already have users table. We will add `fingerprints` column to users table. 

    php artisan migrate --package="buchin/kamfret"

## Usage

### Registration 

*Generate Registration Link*
Put this code in your View to generate registration link: `{{ Kamfret::getRegistrationLink($user->id) }}`
Example:
    
    <a href="{{ Kamfret::getRegistrationLink($user->id) }}">Register</a>

Use user id as parameter

*Listen to Event and do some stuff*
Registration are handled by FlexcodeSDK, and in the background, it will fire `fingerprints.register` event. We could subscribe to this event, check registration result and `echo` an URL to be openen by SDK to the user.
Example: (`app/filters.php`)

    Event::listen('fingerprints.register', function($data)
    {
        // Do some stuff before informing URL to user

        // inform SDK to open this URL
        echo url('users?message=' . $data['message']);
    });

`$data` will contain three information:

`$data['verified']` boolean whether fingerprints are successfully registered
`$data['user']` contains user information from eloquent 
`$data['message']` contains additional message from verification, if registration unsuccessful, it will contains error message. 

### Verification

*Generate Verification Link*
Put this code in your View to generate registration link: `{{ Kamfret::getVerificationLink($user->id, $extras) }}`
Example (Simple):

    <a href="{{ Kamfret::getVerificationLink($user->id) }}">Verify</a>

Without second argument, by default will send `$extras = array('action' => 'login')`

Example (Advanced):

    <a href="{{ Kamfret::getVerificationLink($user->id, array('action' => 'transactions.confirm', 'transaction_id' => $transaction->id )) }}">Verify Transaction</a>

*Listen to the Event and do some stuff*
Example (`app/filters.php`)

    Event::listen('fingerprints.verify', function($data){
        $action = $data['extras']['action'];
        switch ($action) {
            case 'login':
                // Log user to database here, i.e: Adding new session etc.
                // Example: 
                // Session::add($data['user']->id);

                // Then tell SDK to open this page
                echo action('UsersController@index', array('message' => $data['message']));
                break;
            
            case 'transactions.confirm':
                // mark transaction as verified, example usage:

                // $transaction = Transaction::find($data['extras']['transaction_id']);
                // $transaction->verified = true;
                // $transaction->save();

                // Then tell SDK to open this page
                echo route('transactions', 
                    array(
                        'message' => $data['message'], 
                        'id' => $data['extras']['transaction_id'])
                    );
                break;
        }
    });






