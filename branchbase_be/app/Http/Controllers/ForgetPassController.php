<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use Illuminate\Support\Facades\Auth;
use App\Models\Employee\Employees as Employee;
use Illuminate\Mail\Mailer;
use PHPMailer\PHPMailer;

class ForgetPassController extends Controller
{
    private $authkey = '6ff3496e';
    /**
     * Get a JWT via given credentials.
     *
     * @param Request $request
     * @return Response
     */
    public function forget_pass(Request $request)
    {
        $this->validate($request, [
            'username' => 'required|string',
            'email' => 'required|string',
        ]);

        $credentials = $request->only(['username', 'email']);
        $forgot_pass = new Employee;
        $user = $forgot_pass->check_username($credentials['username']);

        if (!empty($user)) {
            if($user->email == $credentials['email']){

                //check for existing/unexpired OTP
                $existing_otp = $forgot_pass->check_existing_otp($user->uid);
                if($existing_otp){
                    return response()->json(['exp_time' => $existing_otp->expired_time, 'existing' => true], 200);
                }

                $otp = mt_rand(10000, 99999);
                $exp_time = time() + 5 * 60;
                 $data = [
                        'uid' => $user->uid,
                        'otp' => $otp,
                        'expired_time' => $exp_time,
                        'create_time' => time()
                    ];

                $add_otp = $forgot_pass->add_otp($data);
                $mail = $this->sendMail($credentials['email'], $otp, date('M-d-Y h:i:s',$exp_time));
                

                if(!$mail){
                    return response()->json(['message' => 'Error in sending Mail'], 401);
                }else if($add_otp){
                    return response()->json(['exp_time' => $exp_time, 'existing' => false], 200);
                }
            }else{
                return response()->json(['message' => 'Account doesn\'t exist.'], 401);
            }
        } else {
            return response()->json(['message' => 'Account doesn\'t exist.'], 401);
        }
    }

    private function sendMail($toMail, $otp, $exp){
        $to               = $toMail;
        $mail             = new PHPMailer\PHPMailer();
        $mail->isSMTP();
        $mail->SMTPDebug  = 0;
        $mail->Host       = 'smtp.mxhichina.com';
        $mail->Port       = 465;
        $mail->SMTPSecure = 'ssl';
        $mail->SMTPAuth   = true;
        $mail->Username   = "service@utalk.help";
        $mail->Password   = "Utalk2016";
        $mail->setFrom('service@utalk.help', 'utalk');
        $mail->addAddress($to);
        $mail->isHTML(false);
        $mail->Subject    = 'Utalk Email Recovery';
        $msg1             = "<h3 style='display:inline-block;'>Your Confirmation Key is:&nbsp;&nbsp;</h3><h2 style='display:inline-block;'>".$otp."</h2><br>";
        $msg1             .= '<h3>This code will expire in 5 Minutes.  On '.$exp.' This code will be invalid</h3>';
        $mail->msgHTML($msg1);
        $mail->AltBody    = 'Utalk';
        return $mail->send();
    }

    public function checkOtp(Request $request){
        $this->validate($request, [
            'username' => 'required|string',
            'otp' => 'required|string',
        ]);

        $credentials = $request->only(['username', 'otp']);

        $forgot_pass = new Employee;
        
        $user = $forgot_pass->validate_otp($credentials['username'], $credentials['otp'], false);
        return response()->json(['valid_otp' => $user, 200]);
    }

    public function change_pass(Request $request){
        $this->validate($request, [
            'username' => 'required|string',
            'otp' => 'required|string',
            'password' => 'required|string',
        ]);

        $credentials = $request->only(['username', 'otp', 'password']);

        $forgot_pass = new Employee;
        
        $salt = $this->rand_word(8) ;//random alphanumeric pw
        $final_password= $this->user_hash($credentials['password'], $salt);
        $user = $forgot_pass->change_pw($credentials['username'], $credentials['otp'], $final_password, $salt);
        return !empty($user) ? response()->json(['changed' => $user], 200) : response()->json(['changed' => $user], 401);
    }

    private function user_hash($passwordinput, $salt)
    {
        $passwordinput = "{$passwordinput}-{$salt}-{$this->authkey}";
        return sha1($passwordinput);
    }

    function rand_word($length) 
    { 
        return(($length > 26 or $length < 1) ? FALSE : 
            substr(str_shuffle("abcdefghijklmnopqrstuvwxyz123456789"),0,$length)); 
    } 
}
