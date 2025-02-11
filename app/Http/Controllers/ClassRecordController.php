<?php

namespace App\Http\Controllers;

use App\Forms\ClassGenForm as GenForm;
use App\Forms\Edit\ClassRecordForm as EditForm;
use App\Jobs\ClassRecordsGenerateQueue;
use App\Models\Agency;
use App\Models\ClassRecord;
use App\Models\Order;
// use App\Forms\ClassRecordForm as CreateForm;
use App\Models\Student;
use App\Models\Teacher;
use App\Models\Vote;
use App\Models\VoteType;
use App\Notifications\ClassRecordNotifyByMessenger;
use App\Notifications\ClassRecordNotifyBySms;
use App\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Kris\LaravelFormBuilder\FormBuilder;
use Kris\LaravelFormBuilder\FormBuilderTrait;

class ClassRecordController extends Controller
{
    use FormBuilderTrait;

    /**
     * The user repository instance.
     */
    // protected $classRecord; todo

    /**
     * Display a listing of the resource for administrator
     * https://abc.dev/classRecords.
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $this->authorize('admin');

        $classRecords = ClassRecord::with(
            'rrule',
            'teacher',
            'teacher.profiles',
            'agency',
            'agency.profiles',
            'user',
            'user.profiles',
            'media'
            )
            ->orderBy('generated_at', 'desc')
            ->paginate(100);

        return view('classRecords.index', compact('classRecords'));
    }

    // https://abc.dev/classRecords/order/165
    public function indexbyOrder(Order $order)
    {
        $this->authorize('viewAny', Order::class);
        // $order = Order::with('schedules', 'classRecords')->find($order->id);
        $classRecords = ClassRecord::with(
            // 'rrule',
            'teacher',
            'teacher.profiles',
            'user',
            'agency',
            'agency.profiles',
            'media',
                )
            ->where('order_id', $order->id) //user_id
            ->orderBy('generated_at', 'desc')
            ->paginate(50);
        $form = $this->form(
            GenForm::class,
            [
                'method' => 'POST',
                'url'    => action('ClassRecordController@generate', ['id' => $order->id]),
            ]
        );

        return view('classRecords.index4order', compact('classRecords', 'form', 'order'));
    }

    public function generate(Request $request, Order $order, FormBuilder $formBuilder)
    {
        $this->authorize('admin');

        $form = $this->form(GenForm::class);
        if (! $form->isValid()) {
            return redirect()->back()->withErrors($form->getErrors())->withInput();
        }
        if ($order->isActive()) {
            ClassRecordsGenerateQueue::dispatch($order, request('days'))->onQueue('high');
            Session::flash('alert-success', '正在生成，请稍后刷新');
        } else {
            Session::flash('alert-danger', 'The order is not active!');
        }

        return redirect()->back();
    }

    // https://abc.dev/class-records 我的所有课程记录
    public function indexByRole()
    {
        $user = Auth::user();
        //谁可以拥有此列表
        //只有老师、学生、和代理可以拥有本列表
        // const ALLOW_LIST_ROLES =['agency', 'teacher', 'student'];
        if (! $user->hasAnyRole(ClassRecord::ALLOW_LIST_ROLES)) {
            abort(403);
        }

        $allowRolesMap = [
            'agency'  => 'agency_uid',
            'teacher' => 'teacher_uid',
            'student' => 'user_id',
        ];

        foreach (ClassRecord::ALLOW_LIST_ROLES as $role) {
            $roleName = $role;
            if (! $user->hasRole($role)) {
                continue;
            }
            $userName = $user->profiles->first()->name;
            $classRecords = ClassRecord::with(
                'rrule',
                'user',
                'user.profiles',
                'teacher',//teacher user!
                'teacher.profiles',//teacher user!
                'teacherModel',
                'media',
                )
            ->orderBy('generated_at', 'desc')
            ->where($allowRolesMap[$role], $user->id);
            //只让学生看好看的！！！
            if ($user->hasAnyRole(['student', 'agency'])) {
                //给学生看的状态[0,1,3]
                $classRecords = $classRecords->whereIn('exception', [0, 1, 3]);
            }
            $classRecords = $classRecords->paginate(50);
            break; //按上下↕️顺序找到第一个角色的即可返回
        }

        $aolCount = 0;
        //为保证您的课时有效期，您每月只有2次自助请假机会，超过请联系专属课程顾问。本次请假操作不可撤销，确定请假？

        if ($user->hasRole('student')) {
            $start = new Carbon('first day of this month');
            $aolCount = ClassRecord::whereIn('exception', [ClassRecord::NORMAL_EXCEPTION_STUDENT, ClassRecord::EXCEPTION_STUDENT]) // 请假和旷课都算进去
                ->where('user_id', $user->id)
                ->where('updated_at', '>=', $start)
                ->pluck('exception')
                ->count();
        }

        return view('classRecords.index4'.$roleName, compact('classRecords', 'aolCount'));
    }

    // https://abc.dev/classRecords/student/131
    // todo view classRecords by student
    // admin
    // agency
    //供代理按学生查看上课记录，不显示老师姓名
    public function indexByStudent(User $user)
    {
        //权限 代理和管理员可以拥有本列表
        if (! Auth::user()->hasAnyRole(['agency', 'admin', 'manager'])) {
            abort(403);
        }
        // 如果是代理，只有该学生的代理是他时，才可以查看。
        if (Auth::user()->hasRole('agency') && $user->profiles()->first()->recommend_uid != Auth::id()) {
            abort(403);
        }

        $classRecords = ClassRecord::with(
            'rrule',
            'user',
            'user.profiles',
                )
            ->where('user_id', $user->id)
            ->orderBy('generated_at', 'desc')
            ->paginate(50);

        return view('classRecords.index4agency', compact('classRecords', 'user'));
    }

    // https://abc.dev/classRecords/teacher/6
    public function indexByTeacher(Teacher $teacher)
    {
        //权限 只有管理员可以拥有本列表
        if (! Auth::user()->hasAnyRole(['admin', 'manager'])) {
            abort(403);
        }

        $classRecords = ClassRecord::with(
            'rrule',
            'user',
            'user.profiles',
            'media',
            'teacher',
            'teacher.profiles',
            )
            ->where('teacher_uid', $teacher->user_id)
            ->orderBy('generated_at', 'desc')
            ->paginate(100);
        // 1-5号显示上个月的统计
        // 5-30/31显示本月统计
        $counts = [];
        $dt = Carbon::now();
        if ($dt->day <= 5) {
            $whichMonth = [$dt->copy()->subMonth()->startOfMonth(), $dt->subMonth()->endOfMonth()];
            $counts['month'] = $dt->month;
        } else {
            $whichMonth = [Carbon::now()->startOfMonth(), Carbon::now()];
            $counts['month'] = $dt->month;
        }
        // 'AOL', //1-by-Student
        $counts['aol'] = ClassRecord::where('teacher_uid', $teacher->user_id)
            ->whereBetween('generated_at', $whichMonth)
            ->where('exception', ClassRecord::NORMAL_EXCEPTION_STUDENT)
            ->count();
        // 'Absent', //学生异常 3-by-Student
        $counts['absent'] = ClassRecord::where('teacher_uid', $teacher->user_id)
            ->whereBetween('generated_at', $whichMonth)
            ->where('exception', ClassRecord::EXCEPTION_STUDENT)
            ->count();
        // 'Holiday', //2 AOL-by-Teacher
        $counts['holiday'] = ClassRecord::where('teacher_uid', $teacher->user_id)
            ->whereBetween('generated_at', $whichMonth)
            ->where('exception', ClassRecord::NORMAL_EXCEPTION_TEACHER)
            ->count();
        // 'EXCEPTION', //Absent-by-Teacher 老师异常,不给老师算课时，需要给学生补课 4
        $counts['exception'] = ClassRecord::where('teacher_uid', $teacher->user_id)
            ->whereBetween('generated_at', $whichMonth)
            ->where('exception', ClassRecord::EXCEPTION_TEACHER)
            ->count();
        $counts['trail'] = Order::where('teacher_uid', $teacher->user_id)
            ->where('price', 0)
            ->where('period', 1)
            ->whereBetween('created_at', $whichMonth)
            ->count();
        // 正式上课订单数量/学生人数（不含试听）
        $counts['normal'] = Order::where('teacher_uid', $teacher->user_id)
            ->where('period', '<>', 1)
            ->count();
        // 'Normal', //0 算给老师工资
        // 'Absent', //学生异常 3-by-Student  算给老师工资
        // + no records!!! no pay!
        $counts['total'] = ClassRecord::where('teacher_uid', $teacher->user_id)
            ->whereBetween('generated_at', $whichMonth)
            ->whereIn('exception', [ClassRecord::NO_EXCEPTION, ClassRecord::EXCEPTION_STUDENT])
            ->count();
        // dd($countsThisMonth,$countsLastMonth);
        return view('classRecords.indexByteacher4admin', compact('classRecords', 'counts', 'teacher'));
    }

    // https://abc.dev/classRecords/agency/4
    public function indexByAgency(Agency $agency, Request $request)
    {
        //权限 只有管理员可以拥有本列表
        if (! Auth::user()->hasAnyRole(['admin', 'manager'])) {
            abort(403);
        }

        $classRecords = ClassRecord::with(
            'rrule',
            'user',
            'user.profiles',
            'media',
            'teacher',
            'teacher.profiles',
            )
            ->where('agency_uid', $agency->user_id)
            ->orderBy('generated_at', 'desc')
            ->paginate(50);
        // 1-5号显示上个月的统计
        // 5-30/31显示本月统计
        $counts = [];
        $dt = Carbon::now();
        if (! $from = $request->query('from')) {
            $from = Carbon::createFromFormat('Y-m-d', '2019-01-01');
        } else {
            $from = Carbon::createFromFormat('Y-m-d', $from);
        }
        if (! $to = $request->query('to')) {
            $to = Carbon::now();
        } else {
            $to = Carbon::createFromFormat('Y-m-d', $to);
        }

        $whichBetween = [$from, $to];

        // 'AOL', //1-by-Student
        $counts['aol'] = ClassRecord::where('agency_uid', $agency->user_id)
            ->whereBetween('generated_at', $whichBetween)
            ->where('exception', ClassRecord::NORMAL_EXCEPTION_STUDENT)
            ->count();
        // 'Absent', //学生异常 3-by-Student
        $counts['absent'] = ClassRecord::where('agency_uid', $agency->user_id)
            ->whereBetween('generated_at', $whichBetween)
            ->where('exception', ClassRecord::EXCEPTION_STUDENT)
            ->count();
        // 'Holiday', //2 AOL-by-Teacher
        $counts['holiday'] = ClassRecord::where('agency_uid', $agency->user_id)
            ->whereBetween('generated_at', $whichBetween)
            ->where('exception', ClassRecord::NORMAL_EXCEPTION_TEACHER)
            ->count();
        // 'EXCEPTION', //Absent-by-Teacher 老师异常,不给老师算课时，需要给学生补课 4
        $counts['exception'] = ClassRecord::where('agency_uid', $agency->user_id)
            ->whereBetween('generated_at', $whichBetween)
            ->where('exception', ClassRecord::EXCEPTION_TEACHER)
            ->count();
        $counts['trail'] = Order::where('agency_uid', $agency->user_id)
            ->where('price', 0)
            ->where('period', 1)
            ->whereBetween('created_at', $whichBetween)
            ->count();
        // 'Normal', //0 算给老师工资
        // 'Absent', //学生异常 3-by-Student  算给老师工资
        // + no records!!! no pay!
        $counts['total'] = ClassRecord::where('agency_uid', $agency->user_id)
            ->whereBetween('generated_at', $whichBetween)
            ->whereIn('exception', [ClassRecord::NO_EXCEPTION, ClassRecord::EXCEPTION_STUDENT])
            ->count();
        // dd($countsThisMonth,$countsLastMonth);
        return view('classRecords.indexByagency4admin', compact('classRecords', 'counts', 'agency', 'whichBetween'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\ClassRecord  $classRecord
     * @return \Illuminate\Http\Response
     */
    public function show(ClassRecord $classRecord)
    {
        // $classRecord->load('comments');
        $this->authorize('view', $classRecord);
        //添加5颗星
        $starts = 0;
        $vote_type = 0;
        foreach (VoteType::get($classRecord) as $key => $voteType) {
            if ($voteType->type == 5) {
                $starts = Vote::get($voteType, $classRecord);
                $vote_type = $voteType;
                break;
            }
        }

        return view('classRecords.show', compact('classRecord', 'starts', 'vote_type'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ClassRecord  $classRecord
     * @return \Illuminate\Http\Response
     */
    public function destroy(ClassRecord $classRecord)
    {
        $this->authorize('delete', $classRecord);
        // @see $table->unique(['rrule_id', 'teacher_uid', 'generated_at']);
        // 由于上述唯一约束，如果使用softDelete，再次生成时会冲突！
        $classRecord->forceDelete();
        Session::flash('alert-success', '删除成功！');

        return redirect()->route('classRecords.indexbyOrder', $classRecord->order_id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\ClassRecord  $classRecord
     * @return \Illuminate\Http\Response
     */
    public function edit(ClassRecord $classRecord)
    {
        $this->authorize('edit', $classRecord);

        $form = $this->form(
            EditForm::class,
            [
                'method' => 'PUT',
                'url'    => action('ClassRecordController@update', ['id' => $classRecord->id]),
            ],
            ['entity' => $classRecord],
        );

        return view('classRecords.edit', compact('form', 'classRecord'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\ClassRecord  $classRecord
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, ClassRecord $classRecord, FormBuilder $formBuilder)
    {
        $this->authorize('edit', $classRecord);
        $form = $this->form(EditForm::class);
        if (! $form->isValid()) {
            return redirect()->back()->withErrors($form->getErrors())->withInput();
        }
        //mp3 mp4
        // Setting 'public' permission for files uploaded on S3
        // https://github.com/spatie/laravel-medialibrary/issues/241
        // https://github.com/spatie/laravel-medialibrary/issues/241#issuecomment-226027435
        // https://github.com/spatie/laravel-medialibrary/issues/1018
        $md5Id = $classRecord->id.'_'.time(); //md5($classRecord->id . );
        if ($request->file('mp3')) {
            $classRecord->clearMediaCollection('mp3');
            $fileMp3Adder = $classRecord->addMediaFromRequest('mp3')
                ->usingFileName($md5Id.'.m4a')
                ->toMediaCollection('mp3');
        }
        if ($request->file('mp4')) {
            $classRecord->clearMediaCollection('mp4');
            $fileMp4Adder = $classRecord->addMediaFromRequest('mp4')
                ->usingFileName($md5Id.'.mp4')
                ->toMediaCollection('mp4');
        }

        $data = $request->all();
        $generated_at = $request->input('generated_at');
        if ($generated_at) {
            $generated_at = Carbon::createFromFormat('Y-m-d\TH:i', $generated_at); //2019-04-09T06:00
            $data['generated_at'] = $generated_at;
        }
        if (! $request->input('agency_uid')) {
            unset($data['agency_uid']);
        }

        $classRecord->fill($data)->save();
        Session::flash('alert-success', __('Success'));

        if (Auth::user()->hasAnyRole(ClassRecord::ALLOW_LIST_ROLES)) {
            return redirect(route('classRecords.indexByRole'));
        }

        return redirect(route('classRecords.show', $classRecord->id));
    }

    //todo vue
    public function flagException(Request $request, ClassRecord $classRecord, $exception)
    {
        //权限判断
        //如果有'Update any ClassRecord Status'这个权限，不必逐一判断

        switch ($exception) {
            case ClassRecord::NORMAL_EXCEPTION_TEACHER://2老师请假
            case ClassRecord::EXCEPTION_STUDENT://3学生旷课
                // 老师可以编辑，故老师可以点击学生旷课
                // 老师可以点击 2老师请假
                // 编辑editor可以
                $this->authorize('edit', $classRecord);
                break;
            case ClassRecord::NORMAL_EXCEPTION_STUDENT://1学生请假
                $this->authorize('aol', $classRecord); //aol权限
                break;
            case ClassRecord::NO_EXCEPTION://0归位正常
            case ClassRecord::EXCEPTION_TEACHER://4老师异常
                $this->authorize('reset', $classRecord); //aol权限
                break;
            default:
                // return abort('403');
                return response('Unauthorized.', 401);
                break;
        }

        $classRecord->exception = $exception;
        //默认=1/ture，如果有任何异常，标记为false，不作为已上课时总数计算
        //@see setExceptionAttribute 不用操心 weight
        // $classRecord->weight = 1;
        return ['success'=>$classRecord->save()];
    }

    public function classNotifyTeacher(Request $request, ClassRecord $classRecord)
    {
        $this->authorize('admin');

        $classRecord->notify(new ClassRecordNotifyByMessenger($classRecord));

        Session::flash('alert-success', __('Success'));

        return redirect()->back();
    }

    public function classNotifyStudent(Request $request, ClassRecord $classRecord)
    {
        $this->authorize('admin');

        $classRecord->notify(new ClassRecordNotifyBySms($classRecord));
        Session::flash('alert-success', __('Success'));

        return redirect()->back();
    }

    public function rate(Request $request, ClassRecord $classRecord, voteType $voteType, $value)
    {
        // todo authorize to studnets!!!
        $this->authorize('rate', $classRecord);

        $res = Vote::set($voteType, $classRecord, $value);

        return ['success' => $res];
        // Session::flash('alert-success', __('Success'));
        // return redirect()->back();
    }
}
