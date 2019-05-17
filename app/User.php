<?php

namespace App;

use Illuminate\Notifications\Notifiable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Spatie\Permission\Traits\HasRoles;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;

use App\Models\School;
use App\Models\Teacher;
use App\Models\Agency;
use App\Models\Student;
use App\Models\Profile;
use App\Models\PayMethod;
use App\Models\Social;
use Actuallymab\LaravelComment\CanComment;

class User extends Authenticatable implements HasMedia
{
    use Notifiable;
    use HasRoles;
    use HasMediaTrait;
    use CanComment;

    public function registerMediaCollections()
    {
        $this
            ->addMediaCollection('avatar')
            // ->useDisk('public')
            ->singleFile();

    }
    // $yourModel->addMedia($pathToImage)->toMediaCollection('avatar');
    // $yourModel->getMedia('avatar')->count(); // returns 1
    // $yourModel->getFirstMediaUrl('avatar'); // will return an url to the `$pathToImage` file

    const ROLES =[
        'admin' => 'admin',
        'developer' => 'developer',// '开发者',
        'manager' => 'manager',// '管理人员',
        'editor' => 'editor',// '网站编辑',

        'school' => 'school',// 'schoolMaster',
        'teacher' => 'teacher',// 'Teacher',
        'student' => 'student',// '学生',
        'agency' => 'agency',// '代理',
    ];

    //@see ClassRecordPolicy 谁可以评论 //谁可以查看
    const MANAGER_ROLES =['developer', 'manager'];//todo , 'editor'

    public function school()
    {
        return $this->hasOne(School::class);
    }

    public function teacher()
    {
        return $this->hasOne(Teacher::class);
    }

    public function student()
    {
        return $this->hasOne(Student::class);
    }

    public function agency()
    {
        return $this->hasOne(Agency::class);
    }


    public function profiles()
    {
        return $this->hasMany(Profile::class);
    }

    public function socials()
    {
        return $this->hasMany(Social::class);
    }

    public function paymethod()
    {
        return $this->hasOne(PayMethod::class);
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password'
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    
    /**
     * for Horizon::auth
     * @return boolean [description]
     */
    public function isSuperuser()
    {
        return $this->id == 1;
    }

    public function isAdmin()
    {
        return $this->isSuperuser()
            || $this->hasAnyRole(self::MANAGER_ROLES);
    }

    //姓名转pinyin和english
    public static function pinyin($name){
        $name = str_replace(' ', '', $name);//去除空格
        $name = implode('', pinyin($name, 16));//PINYIN_NAME
        if(!$name){
            $name = implode('_', pinyin($name, 64));//PINYIN_KEEP_ENGLISH
        }
        return $name;
    }

    public function routeNotificationForWechat($notification)
    {
        $openId = 'oTjEws-8eAAUqgR4q_ns7pbd0zN8';
        $social = $this->socials->first();
        if($social && $social->type == Social::TYPE_WECHAT){
            $openId = $social->social_id;
        }
        return $openId;
    }
}
