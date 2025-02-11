<?php

namespace App\Http\Controllers;

use App\Forms\SocialForm as CreateForm;
use App\Models\Profile;
use App\Models\Social;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Kris\LaravelFormBuilder\FormBuilder;
use Kris\LaravelFormBuilder\FormBuilderTrait;
use Socialite;

class SocialController extends Controller
{
    use FormBuilderTrait;

    /**
     * The user repository instance.
     */
    // protected $classRecord; todo

    public function __construct()
    {
        $this->middleware(['admin'], ['only' => ['destroy']]);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, FormBuilder $formBuilder)
    {
        $this->validate($request, [
            'username'=> 'required',
            'password'=> 'required',
        ]);
        $account = $request->get('username');
        if (is_numeric($account)) {
            $field = 'id';
            $account = Profile::select('user_id')->where('telephone', $account)->first();
            $account = ($account == null) ? 0 : $account->user_id;
        } elseif (filter_var($account, FILTER_VALIDATE_EMAIL)) {
            $field = 'email';
        } else {
            $field = 'name';
        }
        $data = [$field => $account, 'password' => request('password')];
        if (Auth::attempt($data, true)) {
            Session::flash('alert-success', __('Bind Success'));
            Social::firstOrCreate(
                [
                    'social_id' => $request->input('social_id'),
                    'user_id'   => Auth::id(),
                    'type'      => $request->input('type'),
                ]
            );

            return redirect('home');
        } else {
            Session::flash('alert-danger', __('Wrong Credentials'));

            return redirect('login');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Social  $social
     * @return \Illuminate\Http\Response
     */
    public function destroy(Social $social)
    {
        // $this->authorize('delete', $social);

        $social->delete();
        Session::flash('alert-success', __('Unbind Success'));

        return redirect('students');
    }

    /**
     * Redirect the user to the GitHub authentication page.
     *
     * @return \Illuminate\Http\Response
     */
    public function redirectToGithubProvider()
    {
        return Socialite::driver('github')->redirect();
    }

    /**
     * Obtain the user information from GitHub.
     *
     * @return \Illuminate\Http\Response
     */
    public function handleGithubProviderCallback()
    {
        $socialUser = Socialite::driver('github')->user();

        return $this->bind($socialUser, Social::TYPE_GITHUB);
    }

    public function redirectToFacebookProvider()
    {
        return Socialite::driver('facebook')->redirect();
    }

    public function handleFacebookProviderCallback()
    {
        $socialUser = Socialite::driver('facebook')->user();

        return $this->bind($socialUser, Social::TYPE_FACEBOOK);
    }

    public function redirectToWechatProvider()
    {
        return Socialite::driver('weixin')->redirect();
    }

    public function handleWechatProviderCallback()
    {
        $socialUser = Socialite::driver('weixin')->user();

        return $this->bind($socialUser, Social::TYPE_WECHAT);
    }

    public function bind($socialUser, $type)
    {
        $userId = Social::where('social_id', $socialUser->id)->pluck('user_id')->first();
        //bind
        if (! $userId) {
            $loginedId = Auth::id();
            if (! $loginedId) {
                $form = $this->form(
                    CreateForm::class,
                    [
                        'method' => 'POST',
                        'url'    => action('SocialController@store'),
                    ],
                    ['socialUser' => $socialUser, 'socialType' => $type],
                );

                return view('socials.create', compact('form'));
            } else {
                Session::flash('alert-success', __('Bind Success'));
                Social::firstOrCreate(
                    [
                        'social_id' => $socialUser->id,
                        'user_id'   => $loginedId,
                        'type'      => $type,
                    ]
                );
                $this->socialUpdate($loginedId, $type, $socialUser->avatar, $socialUser->nickname ?: $socialUser->name);

                return redirect('home');
            }
        }
        $user = Auth::loginUsingId($userId, true);
        $this->socialUpdate($userId, $type, $socialUser->avatar, $socialUser->nickname ?: $socialUser->name);

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
