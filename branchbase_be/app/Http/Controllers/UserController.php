<?php

namespace App\Http\Controllers;
use App\Libraries\Helpers as Helper;
use Illuminate\Support\Facades\Auth;
use App\User;
use App\User_profile;
use App\Employee_profile;
use App\Models\Edu\Edu_class as Edu;
use App\Models\Employee\Employees as Employee;
use Illuminate\Http\Request;
class UserController extends Controller
{
    /**
     * Instantiate a new UserController instance.
     *
     * @return void
     */
    protected $employee_model;
    public function __construct()
    {
        $this->middleware('auth');
        $this->edu_model = new Edu;
        $this->employee_model = new Employee;
    }

    /**
     * Get the authenticated User.
     *
     * @return Response
     */
    public function profile(Request $request)
    {
        // $kwa = User_profile::find(18)->users;
        // dd($kwa);die();
        $profile = $this->edu_model->get_profile(Auth::id());
        $acc = $this->edu_model->get_accumulated_earnings_count(Auth::id());
        $cd = $this->edu_model->get_deduction_count_since_last_rank_up(Auth::id());
        $ctcs = $this->edu_model->get_running_count(Auth::id());
        $sl = $this->edu_model->get_starting_level($profile->row_id, $profile->user_id,$profile->teacher_id);
        $pl = $this->edu_model->get_previous_level($profile->user_id);

        if (!empty($profile->avatar))
            $avatar = config('constants.utalk_url') . '/attachment/' . $profile->avatar;
        else
            $avatar = '';

        $class_rate = $this->employee_model->get_class_rate();
        $class_rate = Helper::OBJECT_TO_ARRAY($class_rate);
        /**
         * get next level
         */
        $k = -1;
        foreach ($class_rate as $key => $value) {
            if ($value['level'] == $profile->teacher_level) {
                $k = $key + 1;
                break;
            }
        }
        $nl = isset($class_rate[$k]) ? $class_rate[$k]['level'] : $profile->teacher_level;

        $current_rate = $this->edu_model->get_teachers_rate($profile->teacher_level);
        $next_rate = $this->edu_model->get_teachers_rate($nl);
        $cntc = isset($current_rate->course_rank_up) ? isset($ctcs->running_class_count) ? (int)$current_rate->course_rank_up - (int)$ctcs->running_class_count : (int)$current_rate->course_rank_up : '--';
        
        $ret_val = [
            'avatar' => $avatar,
            'teacher_id' => $profile->teacher_id,
            'teacher_name' => $profile->teacher_name,
            'teacher_level' => $profile->teacher_level,
            'acc' => isset($acc->total_valid_class) && $acc->total_valid_class != null ? $acc->total_valid_class : 0,
            'cd' => $cd,
            'sl' => $sl != null ? $sl->level : $profile->teacher_level,
            'cl' => $profile->teacher_level,
            'pl' => isset($pl->level) && $pl->level != null ? $pl->level : $profile->teacher_level,
            'nl' => $nl,
            'ctcs' => isset($ctcs->running_class_count) && $ctcs->running_class_count != null ? $ctcs->running_class_count : 0,
            'cntc' => $cntc,
            'mobile' => $this->edu_model->check_number_from_employee($profile->mobile) ? $profile->mobile : "<span style='color:red;'>incorrect mobile number</span>",
            'user_id' => $profile->user_id,
            'entry_date' => $profile->entry_date
        ];

        return response()->json($ret_val, 200);
    }

    /**
     * Get all User.
     *
     * @return Response
     */
    public function allUsers()
    {
        return response()->json(['users' => User::all()], 200);
    }

    /**
     * Get one user.
     *
     * @return Response
     */
    public function singleUser($id)
    {
        try {
            $user = User::findOrFail($id);

            return response()->json(['user' => $user], 200);

        } catch (\Exception $e) {

            return response()->json(['message' => 'user not found!'], 404);
        }
    }

    /**
     * Get the authenticated user profile image and username.
     *
     * @return Response
     */
    public function header_data()
    {
        $profile = $this->edu_model->get_profile(Auth::id());

        if (!empty($profile->avatar))
            $avatar = config('constants.utalk_url') . '/attachment/' . $profile->avatar;
        else
            $avatar = '';

        $ret_val = [
            'avatar' => $avatar,
            'teacher_name' => $profile->teacher_name,
        ];

        return response()->json($ret_val, 200);
    }

}
