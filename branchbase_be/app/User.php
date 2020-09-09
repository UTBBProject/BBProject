<?php

namespace App;

//use App\Libraries\Helpers as Helper;
use Illuminate\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Database\Eloquent\Model;
use Laravel\Lumen\Auth\Authorizable;
use Illuminate\Support\Facades\DB;

use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Model implements AuthenticatableContract, AuthorizableContract, JWTSubject
{
    use Authenticatable, Authorizable;

    protected $table = 'ims_users';
    protected $primaryKey = 'uid';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password',
    ];

    public function __construct(){

    }

    public function get_user_login_by_username($username) {
        $current_timestamp = time();
        $data = $this->where('groupid', 7)
            ->where('username', $username)
            ->whereRaw('not (`leavestatus`=1 and `leavestime`<"'.$current_timestamp.'")')
            ->where('base', 1)
            ->first();
        return $data;
    }
    /*
     * todo add other data to auth object
     */
//    public function get_user_login_by_username($username)
//    {
//        return $this::from('ims_users as u')->where('groupid', 7)
//            ->select('u.uid', 'up.avatar', 'u.uid as teacher_id', 'e.user_id', 'up.realname as teacher_name', 'up.mobile', 'e.entry_date', 'e.teacher_level', 'u.leavestatus', 'u.password', 'u.salt')
//            ->leftJoin('ims_users_profile as up', 'u.uid', '=', 'up.uid')
//            ->leftJoin(Helper::DB_TABLE('DB_DATABASE_RECRUITMENT', 'employee') . ' as e', 'up.mobile', '=', 'e.phone')
//            ->where('username', $username)
//            ->where('leavestatus', '!=', 1)
//            ->first();
//    }
//
//    public function get_user_info($uid)
//    {
//        return $this::from('ims_users as u')
//            ->select('u.uid', 'up.avatar', 'u.uid as teacher_id', 'e.user_id', 'up.realname as teacher_name', 'up.mobile', 'e.entry_date', 'e.teacher_level', 'u.leavestatus')
//            ->leftJoin('ims_users_profile as up', 'u.uid', '=', 'up.uid')
//            ->leftJoin(Helper::DB_TABLE('DB_DATABASE_RECRUITMENT', 'employee') . ' as e', 'up.mobile', '=', 'e.phone')
//            ->where('u.uid', '=', $uid)
//            ->first();
//    }

    /**
     * Get the identifier that will be stored in the subject claim of the JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    /**
     * Return a key value array, containing any custom claims to be added to the JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [];
    }
}
