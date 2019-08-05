<?php

namespace App\Http\Controllers\Auth;

use Socialite;
use App\Models\Social;
use App\Models\Profile;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use App\Forms\SocialForm as CreateForm;
use Kris\LaravelFormBuilder\FormBuilderTrait;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
{
    use FormBuilderTrait;
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    use AuthenticatesUsers;

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/home';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest')
            ->except('logout')
            ->except('login/wechat');
    }

    /**
     * Get the login username to be used by the controller.
     *
     * @return string
     */
    public function username()
    {
        return 'username';
    }

    /**
     * Attempt to log the user into the application.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return bool
     */
    protected function attemptLogin(Request $request)
    {
        $account = $request->get('username');
        if (is_numeric($account)) {
            $field = 'id';
            $account = Profile::select('user_id')->where('telephone', $account)->first();
            $account = ($account == null) ? 0 : $account->user_id;
        } elseif (filter_var($account, FILTER_VALIDATE_EMAIL)) {
            $field = 'email';
        } else {
            $field = 'name'; //禁用用户名登陆，因重名缘故
        }
        $password = $request->get('password');

        return $this->guard()->attempt([$field => $account, 'password' => $password], $request->filled('remember'));
    }

    /**
     * Validate the user login request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return void
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    protected function validateLogin(Request $request)
    {
        $request->validate([
            $this->username() => 'required|string',
            'password'        => 'required|string',
            'captcha'         => 'required|captcha',
        ]);
    }

    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToProvider()
    {
        if (Auth::id()) {
            return redirect('home');
        }

        return Socialite::driver('github')->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleProviderCallback()
    {
        if (Auth::id()) {
            return redirect('home');
        }
        $socialUser = Socialite::driver('github')->user();

        return $this->bind($socialUser, Social::TYPE_GITHUB);
    }

    public function redirectToFacebookProvider()
    {
        if (Auth::id()) {
            return redirect('home');
        }

        return Socialite::driver('facebook')->redirect();
    }

    public function handleFacebookProviderCallback()
    {
        if (Auth::id()) {
            return redirect('home');
        }
        $socialUser = Socialite::driver('facebook')->user();

        return $this->bind($socialUser, Social::TYPE_FACEBOOK);
    }

    public function redirectToWechatProvider()
    {
        // if (Auth::id()) {
        //     return redirect('home');
        // }

        return Socialite::driver('weixin')->redirect();
    }

    public function handleWechatProviderCallback()
    {
        $userId = Auth::id();
        $socialUser = Socialite::driver('weixin')->user();
        if ($userId) {
            $this->socialUpdate($userId, Social::TYPE_WECHAT, $socialUser->avatar, $socialUser->nickname ?: $socialUser->name);
            alert()->toast(__('Bind Success'), 'success', 'top-center')->autoClose(3000);
            \Log::error(__FUNCTION__, [__CLASS__, __LINE__, $socialUser]);
            dd($userId);

        //return redirect('home');
        } else {
            dd($socialUser, __LINE__);
            //return $this->bind($socialUser, Social::TYPE_WECHAT);
        }
    }

    public function bind($socialUser, $type)
    {
        $userId = Social::where('social_id', $socialUser->id)->pluck('user_id')->first();
        \Log::error(__FUNCTION__, [__CLASS__, __LINE__, $userId]);
        //bind
        if (! $userId) {
            $form = $this->form(
                CreateForm::class,
                [
                    'method' => 'POST',
                    'url'    => action('SocialController@store'),
                ],
                ['socialUser' => $socialUser, 'socialType' => $type],
            );
            \Log::error(__FUNCTION__, [__CLASS__, __LINE__, 'socials.create']);

            return view('socials.create', compact('form'));
        }
        \Log::error(__FUNCTION__, [__CLASS__, __LINE__, $userId]);
        $user = Auth::loginUsingId($userId, true);
        $this->socialUpdate($userId, $type, $socialUser->avatar, $socialUser->nickname ?: $socialUser->name);
        \Log::error(__FUNCTION__, [__CLASS__, __LINE__, $userId]);

        return redirect('home');
    }

    public function socialUpdate($userId, $type, $avatar, $nickname)
    {
        $social = Social::where('user_id', $userId)
            ->where('type', $type)
            ->firstOrFail();
        $social->avatar = $avatar;
        $social->name = $nickname;

        return $social->save();
    }
}
