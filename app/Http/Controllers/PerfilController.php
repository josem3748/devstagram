<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Routing\Controllers\HasMiddleware;

class PerfilController extends Controller implements HasMiddleware
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index()
    {
        return view('perfil.index');
    }

    public function store(Request $request)
    {

        $request->request->add(['username' => Str::slug($request->username)]);

        $request->validate([
            'username'  => ['required', 'unique:users,username,'.auth()->user()->id, 'min:3', 'max:20', 'not_in:twitter,editar-perfil'],
            'email'     => ['required', 'unique:users,email,'.auth()->user()->id, 'email', 'max:60'],
        ]);

        if($request->password) {

            if(!auth()->attempt([
                    'email' => $request->email,
                    'password' => $request->old_password,
                ])) {
                return back()->with('mensaje', 'Credenciales Incorrectas');
            }

            $request->validate([
                'password'     => ['required', 'confirmed', 'min:6'],
            ]);

        }

        if($request->imagen) {
            $manager = new ImageManager(new Driver());

            $imagen = $request->file('imagen');
    
            $nombreImagen = Str::uuid() . "." . $imagen->extension();
    
            $imagenServidor = $manager->read($imagen);
            
            $imagenServidor->cover(1000, 1000);
    
            $imagenPath = public_path('perfiles') . '/' . $nombreImagen;
            $imagenServidor->save($imagenPath);
        }

        // Guardar los cambios
        $usuario = User::find(auth()->user()->id);
        $usuario->username = $request->username;
        $usuario->email = $request->email;
        $usuario->password = $request->password ?? auth()->user()->password;
        $usuario->imagen = $nombreImagen ?? auth()->user()->imagen ?? null;
        $usuario->save();

        // Redireccionar
        return redirect()->route('posts.index', $usuario->username);

    }
}
