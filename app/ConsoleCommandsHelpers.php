<?php

namespace App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

trait ConsoleCommandsHelpers {

    public function requestHasValidUuid()
    {
        if(!$this->validateRequest(['messageOptions.request_uuid'=>'required']))
        {
            return false;
        }
        
        if(!$request = Cache::get('requests')[request('messageOptions.request_uuid')])
        {
            return false;
        }
        return $request;
    }
    
    public function validateRequest($validationOptions)
    {
        $validator = Validator::make(request()->all(), $validationOptions);
        
        return !$validator->fails();
    }
}