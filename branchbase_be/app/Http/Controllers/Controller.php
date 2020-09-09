<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller as BaseController;
use Illuminate\Support\Facades\Auth;

class Controller extends BaseController
{
    //
    //Add this method to the Controller class
    protected function respondWithToken($token, array $addtl_params = [])
    {
        $arr = [
            'token' => $token,
//            'token_type' => 'bearer',
//            'expires_in' => Auth::factory()->getTTL() * 60 * 1000 //1HR
        ];

        $arr = array_merge($arr, $addtl_params);

        return response()->json($arr, 200);
    }
}
