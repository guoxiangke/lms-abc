<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
// 引入 laravel-permission 模型
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware(['admin']);
    }

    /**
     * 显示角色列表.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // eager load with('Spatie\Permission\Models\Permission')->
        $roles = Role::all(); // 获取所有角色

        return view('roles.index')->with('roles', $roles);
    }

    /**
     * 显示创建角色表单.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $permissions = Permission::all(); // 获取所有权限

        $roles = Role::all(); // 获取所有角色
        return view('roles.create', ['permissions'=>$permissions, 'roles'=>$roles]);
    }

    /**
     * 保存新创建的角色.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //验证 name 和 permissions 字段
        $this->validate(
            $request,
            [
                'name'        => 'required|unique:roles|max:10',
                'permissions' => 'required',
            ]
        );

        $name = $request['name'];
        $role = new Role();
        $role->name = $name;

        $permissions = $request['permissions'];

        $role->save();
        // 遍历选择的权限
        foreach ($permissions as $permission) {
            $p = Permission::where('id', '=', $permission)->firstOrFail();
            // 获取新创建的角色并分配权限
            $role = Role::where('name', '=', $name)->first();
            $role->givePermissionTo($p);
        }

        return redirect()->route('roles.index');
    }

    /**
     * 显示指定角色.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        return redirect('roles');
    }

    /**
     * 显示编辑角色表单.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Role $role)
    {
        $permissions = Permission::all();

        return view('roles.edit', compact('role', 'permissions'));
    }

    /**
     * 更新角色.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Role $role)
    {
        // 验证 name 和 permission 字段
        $this->validate($request, [
            'name'        => 'required|max:15|unique:roles,name,'.$role->id,
            'permissions' => 'nullable',
        ]);
        $input = $request->except(['permissions']);
        $role->fill($input)->save();

        $permissions = $request['permissions'];

        // 移除与角色关联的所有权限
        $p_all = Permission::all(); //获取所有权限
        foreach ($p_all as $p) {
            $role->revokePermissionTo($p);
        }
        if ($permissions) {
            foreach ($permissions as $permission) {
                $p = Permission::where('id', '=', $permission)->firstOrFail(); //从数据库中获取相应权限
                $role->givePermissionTo($p);  // 分配权限到角色
            }
        }

        return redirect()->route('roles.index');
    }

    /**
     * 删除指定权限.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $role = Role::findOrFail($id);
        $role->delete();

        return redirect()->route('roles.index');
    }
}
