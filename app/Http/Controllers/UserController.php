<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\CustomUser; // Asumsi Anda menggunakan model User bawaan Laravel
use App\Permission; // Pastikan Anda telah membuat model Permission
use App\Role; // Pastikan Anda telah membuat model Permission
use DataTables;

class UserController extends Controller
{
    public function index(Request $request)
    {
        return view('users.index');
    }

    public function getUser(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $model = CustomUser::query();
        return DataTables::of($model)
        ->editColumn('is_active', function($result) {
            // Kiểm tra nếu is_active = 1, hiển thị dấu tích xanh, ngược lại hiển thị dấu nhân đỏ
            return $result->is_active == 1 
                ? '<span class="text-success glyphicon glyphicon-ok"></span>' 
                : '<span class="text-danger glyphicon glyphicon-remove"></span>';
        })
        ->addColumn('action', function ($result) {
            // Lưu ý sử dụng ngoặc đơn kép để biến PHP được giải thích, và dùng dấu chấm (.) để nối chuỗi
            $editPermissionsUrl = route('users.edit_permissions', ['id' => $result->id]);
            $updatePermissionsUrl = route('users.update_permissions', ['user' => $result->id]);

            $editRolesUrl = route('users.edit_roles', ['id' => $result->id]);
            $updateRolesUrl = route('users.update_roles', ['user' => $result->id]);

            // Dùng ngoặc đơn kép cho chuỗi, và escape ký tự đặc biệt với hàm htmlspecialchars
            return '<button class="btn btn-sm btn-primary edit-permissions" data-id="' . $result->id . '"
                    data-permission-url="' . htmlspecialchars($editPermissionsUrl, ENT_QUOTES, 'UTF-8') . '" 
                    data-update-permission-url="' . htmlspecialchars($updatePermissionsUrl, ENT_QUOTES, 'UTF-8') . '" 
                    data-toggle="modal" data-target="#editPermissionsModal">
                    <span class="glyphicon glyphicon-check"></span> Permissions
                    </button>
                    <button class="btn btn-sm btn-danger edit-roles" data-id="' . $result->id . '"
                    data-role-url="' . htmlspecialchars($editRolesUrl, ENT_QUOTES, 'UTF-8') . '" 
                    data-update-role-url="' . htmlspecialchars($updateRolesUrl, ENT_QUOTES, 'UTF-8') . '" 
                    data-toggle="modal" data-target="#editRolesModal">
                    <span class="glyphicon glyphicon-check"></span> Roles
                    </button>';
        })
        ->rawColumns(['action', 'is_active']) // Thêm này để đánh dấu cột 'action' là HTML thô, không escape
        ->toJson();

    }

    public function editPermissions($id)
    {
        $user = CustomUser::findOrFail($id);
        $permissions = Permission::all();
        $userPermissions = $user->permissions->pluck('id')->toArray();

        // Load và trả về view như một string HTML
        $html = view('users.partials.permissions_form', compact('user', 'permissions', 'userPermissions'))->render();

        return response()->json(['html' => $html]);
    }

    public function updatePermissions(Request $request, $user)
    {
        $user = CustomUser::findOrFail($user);
        $user->permissions()->sync($request->permissions); // Cập nhật quyền

        return response()->json(['success' => true, 'message' => 'Quyền được cập nhật thành công!']);
    }

    public function editRoles($id)
    {
        $user = CustomUser::findOrFail($id);
        $roles = Role::all();
        $userRoles = $user->roles->pluck('id')->toArray();

        // Load và trả về view như một string HTML
        $html = view('users.partials.roles_form', compact('user', 'roles', 'userRoles'))->render();

        return response()->json(['html' => $html]);
    }

    public function updateRoles(Request $request, $user)
    {
        $user = CustomUser::findOrFail($user);
        $user->roles()->sync($request->roles); // Cập nhật vai trò

        return response()->json(['success' => true, 'message' => 'Vai trò cập nhật thành công!']);
    }
}
