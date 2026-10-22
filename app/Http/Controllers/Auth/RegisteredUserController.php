<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class RegisteredUserController extends Controller
{

    public function create()
    {
        return view('auth.register');
    }

    public function validateHandle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'littlelink_name' => 'required|string|max:50|unique:users|regex:/^[\p{L}0-9-_]+$/u',
        ]);
    
        if ($validator->fails()) {
            return response()->json(['valid' => false]);
        }
    
        return response()->json(['valid' => true]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'littlelink_name' => 'required|string|max:50|unique:users|regex:/^[\p{L}0-9-_]+$/u',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8',
        ]);

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'littlelink_name' => $request->littlelink_name,
            'password' => Hash::make($request->password),
        ]);
        \App\Support\RegistrationApproval::create($user);

        Auth::login($user);
        $request->session()->regenerate();

        if (config('registration.email_verification')) {
            try {
                event(new Registered($user));
            } catch (\Exception $e) {
                report($e);
            }
        }

        return redirect($user->block === 'yes' ? route('blocked') : url('dashboard'));
    }
}
