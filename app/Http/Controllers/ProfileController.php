<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }

    /**
     * Update the user's profile photo.
     */
    public function updatePhoto(Request $request): RedirectResponse
    {
        $request->validate([
            'foto' => ['required', 'image', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $user = $request->user();
        
        $file = $request->file('foto');
        $filename = 'perfil_US' . $user->id_usuario . '_' . $user->dni . '.' . $file->getClientOriginalExtension();
        
        // Delete old photo if it exists
        if ($user->foto_perfil) {
            $oldPath = public_path('imagenes/' . $user->foto_perfil);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        $file->move(public_path('imagenes'), $filename);

        $user->foto_perfil = $filename;
        $user->save();

        return Redirect::route('profile.edit')->with('status', 'photo-updated');
    }

    /**
     * Delete the user's profile photo.
     */
    public function deletePhoto(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        if ($user->foto_perfil) {
            $path = public_path('imagenes/' . $user->foto_perfil);
            if (file_exists($path)) {
                unlink($path);
            }
            $user->foto_perfil = null;
            $user->save();
        }

        return Redirect::route('profile.edit')->with('status', 'photo-deleted');
    }
    /**
     * Update the user's CV (only for profesores).
     */
    public function updateCv(Request $request): RedirectResponse
    {
        $request->validate([
            'cv' => ['required', 'file', 'mimes:pdf', 'max:2048'],
        ]);

        $user = $request->user();
        
        if ($user->id_rol != 4 || !$user->profesor) {
            return Redirect::route('profile.edit')->with('status', 'not-authorized');
        }

        $profesor = $user->profesor;
        $file = $request->file('cv');
        $filename = 'cv_US' . $user->id_usuario . '_' . $user->dni . '.' . $file->getClientOriginalExtension();
        
        // Delete old CV if it exists
        if ($profesor->archivo_cv) {
            $oldPath = public_path('cvs/' . $profesor->archivo_cv);
            if (file_exists($oldPath)) {
                unlink($oldPath);
            }
        }

        if (!file_exists(public_path('cvs'))) {
            mkdir(public_path('cvs'), 0755, true);
        }

        $file->move(public_path('cvs'), $filename);

        $profesor->archivo_cv = $filename;
        $profesor->save();

        return Redirect::route('profile.edit')->with('status', 'cv-updated');
    }

    /**
     * Delete the user's CV.
     */
    public function deleteCv(Request $request): RedirectResponse
    {
        $user = $request->user();
        
        if ($user->id_rol != 4 || !$user->profesor) {
            return Redirect::route('profile.edit')->with('status', 'not-authorized');
        }

        $profesor = $user->profesor;

        if ($profesor->archivo_cv) {
            $path = public_path('cvs/' . $profesor->archivo_cv);
            if (file_exists($path)) {
                unlink($path);
            }
            $profesor->archivo_cv = null;
            $profesor->save();
        }

        return Redirect::route('profile.edit')->with('status', 'cv-deleted');
    }
}
