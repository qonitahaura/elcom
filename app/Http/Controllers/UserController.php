<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class UserController extends Controller
{
    // Register user baru
    public function register(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users',
            'password' => 'required|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'phone'    => $request->phone,
            'image'    => $request->image,
            'address'  => $request->address,
        ]);

        return response()->json(['message' => 'User registered', 'user' => $user], 201);
    }

    // Login user dan buat token
    public function login(Request $request)
    {
        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid email or password'], 401);
        }

        // Generate token baru
        $token = Str::random(60);
        $user->api_token = hash('sha256', $token);
        $user->save();

        return response()->json([
            'message' => 'Login success',
            'user' => $user,
            'token' => $token
        ]);
    }

    // Logout (hapus token)
    public function logout(Request $request)
    {
        $token = $request->bearerToken();

        $user = User::where('api_token', hash('sha256', $token))->first();
        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        $user->api_token = null;
        $user->save();

        return response()->json(['message' => 'Logout success']);
    }

    // Get user profile dari token
    public function profile(Request $request)
    {
        $token = $request->bearerToken();

        $user = User::where('api_token', hash('sha256', $token))->first();
        if (!$user) {
            return response()->json(['message' => 'Invalid token'], 401);
        }

        return response()->json($user);
    }

    // CRUD
    public function index()  { return response()->json(User::all()); }

    public function show($id)
    {
        $user = User::find($id);
        return $user ? response()->json($user) : response()->json(['message' => 'Not found'], 404);
    }

    public function update(Request $request, $id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'Not found'], 404);

        $user->update($request->all());
        return response()->json(['message' => 'User updated', 'user' => $user]);
    }

    public function updateProfile(Request $request)
{
    $token = $request->bearerToken();

    $user = User::where('api_token', hash('sha256', $token))->first();

    if (!$user) {
        return response()->json(['success' => false, 'message' => 'Invalid token'], 401);
    }

    // Validasi
    $validator = Validator::make($request->all(), [
        'phone' => 'required',
        'address' => 'required',
        'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    if ($validator->fails()) {
        return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
    }

    // Upload image jika ada
    if ($request->hasFile('image')) {
        $filename = time().'_'.$request->image->getClientOriginalName();
        $request->image->move(public_path('uploads/profile'), $filename);

        $user->image = "uploads/profile/" . $filename;
    }

    $user->phone = $request->phone;
    $user->address = $request->address;
    $user->save();

    return response()->json([
        'success' => true,
        'message' => 'Profile updated',
        'user' => $user
    ]);
}


    public function destroy($id)
    {
        $user = User::find($id);
        if (!$user) return response()->json(['message' => 'Not found'], 404);

        $user->delete();
        return response()->json(['message' => 'User deleted']);
    }
}
