<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    private $authkey = '6ff3496e';

    /**
     * Get a JWT via given credentials.
     *
     * @param Request $request
     * @return Response
     */
    public function login(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['username', 'password']);

        $user_model = new User;
        $user = $user_model->get_user_login_by_username($credentials['username']);

//        if (!$token = Auth::attempt($credentials)) {
//            return response()->json(['message' => 'Unauthorized'], 401);
//        }

        if (!empty($user)) {
            $hashed_password = $this->user_hash($credentials['password'], $user->salt);
            if ($hashed_password != $user->password)
                return response()->json(['message' => 'Incorrect password.'], 401);
            else
                return $this->respondWithToken(Auth::login($user));
//                return $this->respondWithToken(Auth::login($user), ['uid' => $user->uid]);

//        else { //TODO for adding addtl values to auth object
//                $payload = $user_model->get_user_info($user->uid);
////                dd($payload);die();
////                var_dump(Auth::login($payload));die();
//                return $this->respondWithToken(Auth::login($payload));
////               return $this->respondWithToken(Auth::login($user), ['uid' => $user->uid]);
//            }
        } else {
            return response()->json(['message' => 'Account doesn\'t exist.'], 401);
        }
    }

    private function user_hash($passwordinput, $salt)
    {
        $passwordinput = "{$passwordinput}-{$salt}-{$this->authkey}";
        return sha1($passwordinput);
    }
}
