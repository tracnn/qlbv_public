<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
//use App\CustomUser; // Asumsi Anda menggunakan model User bawaan Laravel
use App\Permission; // Pastikan Anda telah membuat model Permission
use App\Role; // Pastikan Anda telah membuat model Permission
use DataTables;

class UserController extends Controller
{
    protected $userModel;

    public function __construct()
    {
        $this->userModel = app(config('auth.providers.users.model'));
    }

    public function index(Request $request)
    {
        return view('users.index');
    }

    public function getUser(Request $request)
    {
        if (!$request->ajax()) {
            return redirect()->route('home');
        }

        $query = $this->userModel->newQuery();

        return DataTables::of($query)
            ->editColumn('is_active', function ($result) {
                return $result->is_active == 1
                    ? '<span class="text-success glyphicon glyphicon-ok"></span>'
                    : '<span class="text-danger glyphicon glyphicon-remove"></span>';
            })
            ->addColumn('action', function ($result) {
                $editPermissionsUrl = route('users.edit_permissions', ['id' => $result->id]);
                $updatePermissionsUrl = route('users.update_permissions', ['user' => $result->id]);

                $editRolesUrl = route('users.edit_roles', ['id' => $result->id]);
                $updateRolesUrl = route('users.update_roles', ['user' => $result->id]);

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
            ->rawColumns(['action', 'is_active'])
            ->toJson();
    }

    public function editPermissions($id)
    {
        $user = $this->userModel->findOrFail($id);
        $permissions = Permission::all();
        $userPermissions = $user->permissions->pluck('id')->toArray();

        $html = view('users.partials.permissions_form', compact('user', 'permissions', 'userPermissions'))->render();

        return response()->json(['html' => $html]);
    }

    public function updatePermissions(Request $request, $user)
    {
        $user = $this->userModel->findOrFail($user);
        $user->permissions()->sync($request->permissions);

        return response()->json(['success' => true, 'message' => 'Quyền được cập nhật thành công!']);
    }

    public function editRoles($id)
    {
        $user = $this->userModel->findOrFail($id);
        $roles = Role::all();
        $userRoles = $user->roles->pluck('id')->toArray();

        $html = view('users.partials.roles_form', compact('user', 'roles', 'userRoles'))->render();

        return response()->json(['html' => $html]);
    }

    public function updateRoles(Request $request, $user)
    {
        $user = $this->userModel->findOrFail($user);
        $user->roles()->sync($request->roles);

        return response()->json(['success' => true, 'message' => 'Vai trò cập nhật thành công!']);
    }
}
