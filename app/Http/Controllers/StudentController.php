<?php

namespace App\Http\Controllers;

use App\Forms\Edit\StudentForm as EditForm;
use App\Forms\Register\StudentRegisterForm;
use App\Forms\StudentForm as CreateForm;
use App\Forms\StudentsImportForm as ImportForm;
use App\Imports\StudentsImport;
use App\Models\Agency;
use App\Models\Contact;
use App\Models\PayMethod;
use App\Models\Profile;
use App\Models\Student;
use App\User;
use Auth;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;
use Kris\LaravelFormBuilder\FormBuilder;
use Kris\LaravelFormBuilder\FormBuilderTrait;
use Spatie\QueryBuilder\QueryBuilder;

class StudentController extends Controller
{
    use FormBuilderTrait;

    public function __construct(Student $Student)
    {
        // $this->middleware(['admin'], ['only' => ['index']]);
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('viewAny', Student::class);
        $students = Student::with(
            'user',
            'user.profiles',
            'user.profiles.contacts',
            'user.profiles.recommend',
            'user.socials',
            )//'user.paymethod',
            ->orderBy('id', 'desc');

        $students = QueryBuilder::for($students)
            ->allowedIncludes(['user.profiles'])
            ->allowedFilters(['user.name', 'user.profiles.name'])
            ->paginate(100);

        return view('students.index', compact('students'));
    }

    /**
     * Display a listing of the resource.
     * 代理： 我的学生页.
     * @return \Illuminate\Http\Response
     */
    public function indexByRecommend()
    {
        $user = Auth::user();
        //谁可以拥有此列表
        if (! $user->hasAnyRole(Student::ALLOW_LIST_ROLES)) {
            abort(403);
        }
        //$this->authorize('indexByRole');
        // dd($user->toArray());
        //我推荐的学生
        $profiles = Profile::with('contacts', 'recommend', 'user', 'user.student')
            ->where('recommend_uid', $user->id)
            ->orderBy('id', 'desc')
            ->paginate(50);

        return view('students.index4recommend', compact('profiles'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $this->authorize('create', Student::class);
        $form = $this->form(CreateForm::class, [
            'method' => 'POST',
            'url'    => action('StudentController@store'),
        ]);

        return view('students.create', compact('form'));
    }

    public function register()
    {
        //必须是没学生角色才可以注册
        $this->authorize('create', Student::class);

        $form = $this->form(StudentRegisterForm::class, [
            'method' => 'POST',
            'url'    => action('StudentController@registerStore'),
        ]);

        return view('students.register', compact('form'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function registerStore(Request $request, FormBuilder $formBuilder)
    {
        //必须是没XX角色才可以注册
        $this->authorize('create', Student::class);
        $profileTelephone = $request->input('telephone');
        if ($profileTelephone) {
            $this->validate($request, [
                'telephone'=> 'required|string|min:14|max:14|unique:profiles',
            ]);
        }
        $form = $formBuilder->create(StudentRegisterForm::class);

        if (! $form->isValid()) {
            return redirect()->back()->withErrors($form->getErrors())->withInput();
        }
        // $user = $request->user();
        $user = User::find($request->input('user_id'));

        //region profile 真实姓名/电话 推荐人uid关联 更改机会
        $profileName = $request->input('profile_name');
        $recommendTelephone = $request->input('recommend_telephone');
        $userProfile = Profile::where('user_id', $user->id)->firstOrFail();
        $userProfileNeedSave = false;
        if ($recommendTelephone) {
            $recommendUser = Profile::where('telephone', $recommendTelephone)->first();
            if ($recommendUser) {
                $userProfile->recommend_uid = $recommendUser->user_id;
                $userProfileNeedSave = true;
            }
        }
        if ($profileTelephone) {
            $userProfile->telephone = $profileTelephone;
            $userProfileNeedSave = true;
        }
        if ($profileName) {
            $userProfile->name = $profileName;
            $userProfileNeedSave = true;
        }
        if ($userProfileNeedSave) {
            $userProfile->save();
        }
        //endregion

        // dd($request->all(),$request->user()->toArray());

        $student = Student::firstOrCreate([
            'user_id' => $user->id,
            'creater_uid' => Auth::user()->id,
            'grade'   => $request->input('grade'),
            'name'    => $request->input('english_name') ?: $user->name, //英文名！
        ]);
        //创建关联关系？
        // $student = $user->student()->save($student);
        if ($student) {
            $user->assignRole(User::ROLES['student']);
            Session::flash('alert-success', '学员登记成功，欢迎您！');

            return redirect()->route('home');
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, FormBuilder $formBuilder)
    {
        $this->authorize('create', Student::class);
        $form = $formBuilder->create(CreateForm::class);

        $this->validate($request, [
            'telephone'=> 'required|string|min:14|max:14|unique:profiles',
        ]);
        if (! $form->isValid()) {
            return redirect()->back()->withErrors($form->getErrors())->withInput();
        }
        // create login user
        $profileName = $request->input('profile_name');
        $name = User::getRegisterName($profileName);
        $email = $name.'_'.Str::random(6).'@student.com';
        $userData = [
            'name'     => $name,
            'email'    => $email,
            'password' => Hash::make($request->input('password') ?: 'dxjy1234'),
        ];
        $user = User::create($userData);

        $user->assignRole(User::ROLES['student']);

        $student = Student::firstOrNew([
            'user_id' => $user->id,
            'creater_uid' => Auth::user()->id,
            'grade'   => $request->input('grade'),
            'remark'  => $request->input('remark'),
            'book_id' => $request->input('book_id'),
        ]);
        $student = $user->student()->save($student);

        //确保只有一个手机号
        $profile = Profile::firstOrNew([
            'telephone' => $request->input('telephone'),
        ]);
        $birthday = $request->input('profile_birthday');
        if ($birthday) {
            $birthday = Carbon::createFromFormat('Y-m-d', $birthday);
        }
        $profile->fill([
            'user_id'       => $user->id,
            'name'          => $request->input('profile_name'),
            'sex'           => $request->input('profile_sex'),
            'birthday'      => $birthday,
            'recommend_uid' => $request->input('recommend_uid') ?: null,
        ])->save();

        Contact::firstOrNew([
            'profile_id' => $profile->id,
            'type'       => 1, // Contact::TYPES[1] = 'wechat/qq',
            'number'     => $request->input('contact_number') ?: $request->input('telephone'),
            'remark'     => $request->input('contact_remark'),
        ])->save();
        // $contact = $profile->contact()->save($contact);
        Session::flash('alert-success', '登记成功！');

        return redirect()->route('students.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function show(Student $student)
    {
        return view('students.show', compact('student'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function edit(Student $student)
    {
        $this->authorize('update', $student);
        $form = $this->form(
            EditForm::class,
            [
                'method' => 'PUT',
                'url'    => action('StudentController@update', ['id'=>$student->id]),
            ],
            ['entity' => $student],
        );

        return view('students.edit', compact('form', 'student'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Student $student, FormBuilder $formBuilder)
    {
        $this->authorize('update', $student);
        $this->validate($request, [
            'telephone'=> 'required|min:10|max:14|unique:profiles,telephone,'.$student->user_id.',user_id',
        ]);
        $form = $this->form(EditForm::class);
        if (! $form->isValid()) {
            return redirect()->back()->withErrors($form->getErrors())->withInput();
        }

        $user = $student->user;
        // dd($zoomId);
        $paymethod = $user->paymethod;

        $profile = $user->profiles->first();
        // $profile = $teacher->profiles->first();
        $contact = $profile->contacts->first();
        if (! $contact) {
            $contact = Contact::create([
                'profile_id' => $profile->id,
                'type'       => 1, // Contact::TYPES[1] = 'wechat/qq',
                'number' => $request->input('contact_number') ?: $request->input('telephone'),
                'remark' => $request->input('contact_remark'),
            ]);
        } else {
            $contact->fill([
                'type'       => 1, // Contact::TYPES[1] = 'wechat/qq',
                'number'     => $request->input('contact_number') ?: $request->input('telephone'),
                'remark'     => $request->input('contact_remark'),
            ])->save();
        }

        // create login user

        $profileName = $request->input('profile_name');
        $name = $request->input('name'); //英文名！
        if (! $name) {
            $name = User::getRegisterName($profileName);
        }
        $user->name = $name;
        if ($password = $request->input('password')) {
            $user->password = Hash::make($password);
        }
        $user->save();

        $student->fill([
            // 'user_id' => $user->id,
            'grade'   => $request->input('grade'),
            'remark'  => $request->input('remark'),
            'book_id' => $request->input('book_id'),
        ])->save();

        //确保只有一个手机号
        $birthday = $request->input('profile_birthday');
        if ($birthday) {
            $birthday = Carbon::createFromFormat('Y-m-d', $birthday);
        }
        $profile->fill([
            'telephone' => $request->input('telephone'),
            // 'user_id' => $user->id,
            'name'          => $request->input('profile_name'),
            'sex'           => $request->input('profile_sex'),
            'birthday'      => $birthday,
            'recommend_uid' => $request->input('recommend_uid') ?: null,
        ])->save();

        Session::flash('alert-success', __('Success'));

        return redirect()->route('students.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Student  $student
     * @return \Illuminate\Http\Response
     */
    public function destroy(Student $student)
    {
        //
    }

    public function import()
    {
        //权限
        // if (! Auth::user()->hasAnyRole(['agency', 'admin', 'manager'])) {
        //     abort(403);
        // }

        $form = $this->form(ImportForm::class, [
            'method' => 'POST',
            'url'    => action('StudentController@importStore'),
        ]);

        return view('students.import', compact('form'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function importStore(Request $request, FormBuilder $formBuilder)
    {
        //权限
        if (! Auth::user()->hasAnyRole(['agency', 'admin', 'manager'])) {
            abort(403);
        }

        (new StudentsImport)->import($request->file('field_excel'));
        Session::flash('alert-success', '导入完成！');

        return redirect()->route('students.index');
    }
}
