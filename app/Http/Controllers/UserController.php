<?php


namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Local;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserController extends Controller
{
    // Mostrar la lista de usuarios
    public function index()
    {
        $users = User::all();
        $users = User::with(['roles', 'permissions'])->get();
        return view('users.index', compact('users'));
    }

    // Mostrar formulario para crear un usuario nuevo
    public function create()
    {
        $roles = Role::all(); // Obtener todos los roles
        $locales = Local::all(); // Obtener todos los locales
        $permissions = Permission::all(); // Obtener todos los permisos
        return view('users.create', compact('roles', 'locales', 'permissions'));
    }

    // Guardar un nuevo usuario
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6|confirmed',
            'role' => 'required', // Validar que el rol sea seleccionado
            'permissions' => 'array', // Validar que los permisos sean un array
            'local_id' => 'nullable|exists:locales,id', // El local puede ser nulo para usuarios como el dueño
        ]);
    
        // Crear el nuevo usuario
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'local_id' => $request->local_id, // Guardar el local
        ]);
    
        // Asignar el rol seleccionado
        $user->assignRole($request->role);
    
        // Asignar los permisos seleccionados
        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions); // Sincronizar los permisos
        }
    
        return redirect()->route('users.index')->with('success', 'Usuario creado exitosamente');
    }

    // Mostrar el formulario para editar un usuario
    public function edit(User $user)
    {
        $roles = Role::all(); // Obtener todos los roles
        $locales = Local::all(); // Obtener todos los locales
        $permissions = Permission::all(); // Obtener todos los permisos
        return view('users.edit', compact('user', 'roles', 'locales', 'permissions'));
    }

    // Actualizar un usuario existente
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'role' => 'required',
            'permissions' => 'array', // Asegurarnos de que los permisos sean un array
            'local_id' => 'nullable|exists:locales,id',
        ]);
    
        // Actualizar la información del usuario
        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'local_id' => $request->local_id, // Actualizar el local
            'password' => $request->password ? Hash::make($request->password) : $user->password,
        ]);
    
        // Actualizar el rol
        $user->syncRoles($request->role);
    
        // Actualizar los permisos seleccionados
        if ($request->has('permissions')) {
            $user->syncPermissions($request->permissions); // Sincronizar los permisos seleccionados
        } else {
            // Si no se seleccionan permisos, eliminamos todos los permisos asignados
            $user->syncPermissions([]);
        }
    
        return redirect()->route('users.index')->with('success', 'Usuario actualizado exitosamente');
    }

    // Eliminar un usuario
    public function destroy(User $user)
    {
        $user->delete();
        return redirect()->route('users.index')->with('success', 'Usuario eliminado exitosamente');
    }

    //CODIGO PARA OBTENER LOS PERMISOS DEL ROL 
    public function getPermissionsByRole(Request $request)
{
    $role = Role::find($request->role_id);

    if ($role) {
        $permissions = $role->permissions; // Obtiene los permisos asignados a ese rol
        return response()->json($permissions);
    }

    return response()->json([], 404); // Retorna vacío si no se encuentra el rol
}

}

