<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Rol;
use App\Services\FacePhotoEncodingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    // ── Roles ──────────────────────────────────────────────────────────
    public function getRoles()
    {
        $roles = Rol::all();
        return response()->json($roles);
    }

    // ── Listar ACTIVOS ─────────────────────────────────────────────────
    public function index()
    {
        $usuarios = User::query()
            ->select([
                'id', 'name', 'email', 'phone',
                'id_rol', 'email_verified_at',
                'created_at', 'updated_at',
                'fecha_nacimiento', // NUEVO
                'nro_carnet',       // NUEVO
                'activo',           // NUEVO
            ])
            ->with('rol')
            ->where('activo', true)   // Solo activos
            ->orderBy('id')
            ->get();

        return response()->json(['usuarios' => $usuarios]);
    }

    // ── Listar INACTIVOS ───────────────────────────────────────────────
    public function inactivos()
    {
        $usuarios = User::query()
            ->select([
                'id', 'name', 'email', 'phone',
                'id_rol', 'email_verified_at',
                'created_at', 'updated_at',
                'fecha_nacimiento',
                'nro_carnet',
                'activo',
            ])
            ->with('rol')
            ->where('activo', false)  // Solo inactivos
            ->orderBy('id')
            ->get();

        return response()->json(['usuarios' => $usuarios]);
    }

    // ── Crear ──────────────────────────────────────────────────────────
    public function store(Request $request)
    {
        $data = $request->validate([
            'name'              => ['required', 'string', 'max:255', 'regex:/^[\pL\s]+$/u'],
            'email'             => 'required|string|email|max:255|unique:users',
            'password'          => 'required|string|min:8|confirmed',
            'phone'             => 'required|string|size:8|regex:/^[0-9]{8}$/',
            'face_photo_base64' => 'required|string|max:7000000',
            'face_descriptor'   => 'nullable|array|size:128',
            'face_descriptor.*' => 'numeric',
            'id_rol'            => 'required|integer',
            'fecha_nacimiento'  => 'required|date|before_or_equal:' . now()->subYears(18)->format('Y-m-d'),
            'nro_carnet'        => 'required|string|size:8|regex:/^[0-9]{8}$/|unique:users,nro_carnet',
        ]);

        $facePhoto = app(FacePhotoEncodingService::class)
            ->toDataUriForStorage($data['face_photo_base64']);

        $user = User::create([
            'name'              => $data['name'],
            'email'             => $data['email'],
            'password'          => Hash::make($data['password']),
            'phone'             => $data['phone'],
            'face_photo_base64' => $facePhoto,
            'face_descriptor'   => $data['face_descriptor'] ?? null,
            'id_rol'            => $data['id_rol'],
            'email_verified_at' => now(),
            'fecha_nacimiento'  => $data['fecha_nacimiento'] ?? null, // NUEVO
            'nro_carnet'        => $data['nro_carnet'] ?? null,       // NUEVO
            'activo'            => true,                               // NUEVO: siempre activo al crear
        ]);

        return response()->json([
            'message' => 'Usuario creado exitosamente',
            'user'    => $user->makeVisible(['face_photo_base64']),
        ], 201);
    }

    // ── Ver uno ────────────────────────────────────────────────────────
    public function show(User $user)
    {
        return response()->json($user->makeVisible(['face_photo_base64']));
    }

    // ── Actualizar ─────────────────────────────────────────────────────
    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'name'              => ['sometimes', 'string', 'max:255', 'regex:/^[\pL\s]+$/u'],
            'email'             => [
                'sometimes', 'email', 'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'password'          => 'sometimes|string|min:8|confirmed',
            'phone'             => 'nullable|string|size:8|regex:/^[0-9]{8}$/',
            'face_photo_base64' => 'nullable|string|max:7000000',
            'face_descriptor'   => 'nullable|array|size:128',
            'face_descriptor.*' => 'numeric',
            'id_rol'            => 'sometimes|integer',
            'fecha_nacimiento'  => 'required|date|before_or_equal:' . now()->subYears(18)->format('Y-m-d'),
            'nro_carnet'        => [
                'required', 'string', 'size:8', 'regex:/^[0-9]{8}$/',
                Rule::unique('users', 'nro_carnet')->ignore($user->id),
            ],
        ]);

        if (!empty($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        if (
            array_key_exists('face_photo_base64', $data)
            && $data['face_photo_base64'] !== null
            && $data['face_photo_base64'] !== ''
        ) {
            $data['face_photo_base64'] = app(FacePhotoEncodingService::class)
                ->toDataUriForStorage($data['face_photo_base64']);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Usuario actualizado',
            'user'    => $user->fresh()->makeVisible(['face_photo_base64']),
        ]);
    }

    // ── Desactivar (soft-delete) ───────────────────────────────────────
    // Antes era destroy(), ahora solo pone activo = false
    // ── Desactivar ─────────────────────────────────────────────

    public function destroy(User $user){
    $authUser = User::find(auth()->id());

    if ($authUser && $user->id === $authUser->id) {
        if ($user->id_rol == 1) {
            $adminsActivos = User::where('id_rol', 1)
                                 ->where('activo', true)
                                 ->count();
            if ($adminsActivos <= 1) {
                return response()->json([
                    'message' => 'Eres el único administrador activo. No puedes desactivar tu propia cuenta.',
                ], 403);
            }
        }
    }

        $user->update(['activo' => false]);

            return response()->json([
                'message' => 'Usuario desactivado correctamente.',
                'self'    => $authUser && $user->id === $authUser->id,
    ]);
    }

    // ── Activar ────────────────────────────────────────────────────────
    public function activar(User $user)
    {
        $user->update(['activo' => true]);

        return response()->json(['message' => 'Usuario activado']);
    }

    // ── Eliminar PERMANENTEMENTE ───────────────────────────────────────
    public function eliminarDefinitivo(User $user)
    {
        $user->delete();

        return response()->json(['message' => 'Usuario eliminado permanentemente']);
    }
}
