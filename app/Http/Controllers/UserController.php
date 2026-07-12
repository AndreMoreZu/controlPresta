<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::orderBy('name')->get();
        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        return view('usuarios.create');
    }

    public function store(StoreUserRequest $request)
    {
        User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        return redirect()
            ->route('usuarios.index')
            ->with('status', "Usuario \"{$request->name}\" creado correctamente.");
    }

    public function destroy(User $user)
    {
        if ($user->id === auth()->id()) {
            return back()->with('error', 'No podés eliminar tu propia cuenta.');
        }

        if (User::count() <= 1) {
            return back()->with('error', 'No se puede eliminar el único usuario del sistema.');
        }

        $nombre = $user->name;
        $user->delete();

        return redirect()
            ->route('usuarios.index')
            ->with('status', "Usuario \"{$nombre}\" eliminado.");
    }
}
