<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Laravel\Socialite\Facades\Socialite;
use App\Models\User;
use App\Models\Role;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            $user = User::where('email', $googleUser->getEmail())->first();

            if (!$user) {
                $userRole = Role::where('name', 'User')->first();
                $user = User::create([
                    'name' => $googleUser->getName(),
                    'email' => $googleUser->getEmail(),
                    'google_id' => $googleUser->getId(),
                    'password' => bcrypt(Str::random(16)),
                    'role_id' => $userRole->id ?? null,
                    'email_verified_at' => now(), // Assume verified
                ]);
            } else {
                $updates = [];
                if (!$user->google_id) {
                    $updates['google_id'] = $googleUser->getId();
                }
                if (!$user->role_id) {
                    $userRole = Role::where('name', 'User')->first();
                    $updates['role_id'] = $userRole->id ?? null;
                }
                if (!empty($updates)) {
                    $user->update($updates);
                }
            }

            Auth::login($user);

            if ($user->role && $user->role->name === 'Admin') {
                return redirect()->intended('/admin/dashboard'); 
            }
            return redirect()->intended('/');

        } catch (\Exception $e) {
            return redirect('/login')->withErrors(['email' => 'Gagal autentikasi via Google.']);
        }
    }
}
