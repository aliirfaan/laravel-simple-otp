# Laravel Simple OTP

This package allows you to generate OTP (One time password). You can then verify the OTP code and grant access based on its validity.

## Flexibility

This package is not tied to Laravel Auth and you can use it to send OTP to any model in your project. For example you can send OTP to a Merchant or Customer model. It does not matter if you are coding a REST API or view based backend, I have tried to code the methods to be used independently.

## Features

* Generate random OTP based on length
* Associate OTP code with a model object using the object id and object type
* Hash OTP code for better security
* Validate OTP code based on presence, equality and expiry
* Throws custom exceptions

## Requirements

* [Composer](https://getcomposer.org/)
* [Laravel](http://laravel.com/)


## Installation

You can install this package on an existing Laravel project with using composer:

```bash
 $ composer require aliirfaan/laravel-simple-otp
```

Register the ServiceProvider by editing **config/app.php** file and adding to providers array:

```php
  aliirfaan\LaravelSimpleOtp\SimpleOtpServiceProvider::class,
```

Note: use the following for Laravel <5.1 versions:

```php
 'aliirfaan\LaravelSimpleOtp\SimpleOtpServiceProvider',
```

Publish files with:

```bash
 $ php artisan vendor:publish --provider="aliirfaan\LaravelSimpleOtp\SimpleOtpServiceProvider"
```

or by using only `php artisan vendor:publish` and select the `aliirfaan\LaravelSimpleOtp\SimpleOtpServiceProvider` from the outputted list.

Apply the migrations for the `ModelGotOtps` table:

```bash
 $ php artisan migrate
```

## Configuration

This package publishes an `otp.php` file inside your applications's `config` folder which contains the settings for this package. Most of the variables are bound to environment variables, but you are free to directly edit this file, or add the configuration keys to the `.env` file.

otp_does_expire | Bool (true or false)  
Whether the OTP code will expire    
If true, the number of seconds specified in otp_timeout_seconds is used to get expiry date

```php
'otp_does_expire' => true
```

otp_timeout_seconds | Numeric  
Number of seconds after which the OTP expires  
Taken into consideration only if otp_does_expire is set to true

```php
'otp_timeout_seconds' => 180
```

otp_digit_length | Numeric  
The number of digits that the OTP will have

```php
'otp_digit_length' => 6
```

otp_should_encode | Bool (true or false)  
Whether to hash the OTP before saving in the database  
Uses framework hashing to hash OTP. See security > hashing in Laravel docs

```php
'otp_should_encode' => false
```

otp_should_simulate | Bool (true or false)
Whether to simulate otp code generation

```php
'otp_should_simulate' => false
```

otp_simulate_fillable_digit | Numeric
What digits to use to generate OTP if simulation is enabled. The OTP generated will be generated with the fillable digit. Example: 1111

```php
'otp_simulate_fillable_digit' => 1
```

## Usage

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use aliirfaan\LaravelSimpleOtp\Models\ModelGotOtp; // otp model
use aliirfaan\LaravelSimpleOtp\Services\OtpHelperService; // otp helper service
use aliirfaan\LaravelSimpleOtp\Exceptions\ExpiredException;
use aliirfaan\LaravelSimpleOtp\Exceptions\NotFoundException;
use aliirfaan\LaravelSimpleOtp\Exceptions\NotMatchException;

class TestController extends Controller
{
    protected $otpModel;

    /**
     * Load model in constructor using dependency injection
     */
    public function __construct(ModelGotOtp $otpModel)
    {
        $this->otpModel = $otpModel;
    }

    /**
     * Include our service using dependency injection
     */
    public function test_send_otp(Request $request, OtpHelperService $otpHelperService)
    {
        // after an action like login, get yout model from the database
        $modelId = 1;
        $yourExampleModelObj = App\ExampleModel::find($modelId);

        // generate OTP, it will return an array with otp_code and otp_hash key
        $otpCode = $otpHelperService->generateOtpCode();

        // model type can be anything but it must be unique if you want to send OTP to multiple model classes
        // it can also be the class name of the object. You get it using new \ReflectionClass($yourExampleModelObj))->getShortName()
        $modelType = 'exampleModel'; 
        $phoneNumber = $yourExampleModelObj->phone;

        $otpData = [
            'model_id' => $modelId,
            'model_type' => $modelType,
            'otp_code' => $otpCode['otp_hash']
        ];

        /**
         * create otp 
         * use createOtp($otpData, false) to add a row for each otp sent
         * use createOtp($otpData) to update if row exists
         */
        $createOtp = $this->otpModel->createOtp($otpData);

        // send otp using your own code
        $message = 'Your OTP is: '. $otpCode['otp_code'];
        
    }

    /**
     * Include our service using dependency injection
     */
    public function test_verify_otp(Request $request, OtpHelperService $otpHelperService)
    {
        
        // normally you will get this via $request
        $modelId = 1;
        $modelType = 'exampleModel';
        $otpCode = '123456';

        // get otp
        $otpObj = $this->otpModel->getOtp($modelId, $modelType);

        // verify otp
        try {
            $otpCodeIsValid = $otpHelperService->otpCodeIsValid($otpObj, $otpCode);
            // update otp validated flag
            $updateOtp = $this->otpModel->updateOtp($otpObj->id);
        } catch (\aliirfaan\LaravelSimpleOtp\Exceptions\NotFoundException $e) {
            //
        } catch (\aliirfaan\LaravelSimpleOtp\Exceptions\NotMatchException $e) {
            //
        } catch (\aliirfaan\LaravelSimpleOtp\Exceptions\ExpiredException $e) {
            //
        } catch (\Exception $e) {
            //
        }
    }
}
```

## License

The MIT License (MIT)

Copyright (c) 2020

Permission is hereby granted, free of charge, to any person obtaining a copy
of this software and associated documentation files (the "Software"), to deal
in the Software without restriction, including without limitation the rights
to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the Software is
furnished to do so, subject to the following conditions:

The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
SOFTWARE.
