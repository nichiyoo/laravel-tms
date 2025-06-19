<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class PasswordController extends Controller
{
  /**
   * Update the user's password.
   */
  public function update(Request $request): RedirectResponse
  {
    $request->validate([
      'current_password' => ['required', 'current_password'],
      'password' => ['required', 'confirmed', Password::defaults()],
    ]);

    Auth::user()->fill($request->only('password'));
    Auth::user()->save();

    return back()
      ->with('success', 'Successfully updated password.');
  }
}
