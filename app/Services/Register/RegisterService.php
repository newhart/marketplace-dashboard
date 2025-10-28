<?php

namespace App\Services\Register;

use App\Http\Requests\RegisterRequest;

class RegisterService
{

    public function register(RegisterRequest $registerRequest){
        $data = $registerRequest->validated(); 
        if(isset($data['role']) and  $data['role'] === 'user'){
            
        }else{

        }
    }
}
