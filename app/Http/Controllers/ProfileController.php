<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
  /**
   * Display the users profile
   */
  public function show(Request $request): View
  {
    return view('profile.show', [
      'user' => Auth::user(),
    ]);
  }

  /**
   * Display the user's profile form.
   */
  public function edit(Request $request): View
  {
    return view('profile.edit', [
      'user' => Auth::user(),
    ]);
  }

  /**
   * Update the user's profile information.
   */
  public function update(ProfileUpdateRequest $request): RedirectResponse
  {
    $validated = $request->validated();

    Auth::user()->fill($validated);
    Auth::user()->save();

    return Redirect::route('profile.show')
      ->with('success', 'Successfully saved profile information.');
  }

  /**
   * Delete the user's account.
   */
  public function destroy(Request $request): RedirectResponse
  {
    $request->validate([
      'password' => ['required', 'current_password'],
    ]);

    $user = Auth::user();
    Auth::logout();

    $user->delete();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return Redirect::route('dashboard')
      ->with('success', 'Your account has been deleted.');
  }
}
