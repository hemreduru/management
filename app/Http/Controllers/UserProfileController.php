<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdatePasswordRequest;
use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class UserProfileController extends Controller
{
    /**
     * Show the form for editing the user's profile.
     */
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Update the user's profile information.
     */
    public function update(UpdateProfileRequest $request)
    {
        $user = Auth::user();
        $data = $request->validated();

        // Handle profile image upload
        if ($request->hasFile('profile_image')) {
            try {
                $file = $request->file('profile_image');

                // Basic validation
                if (!$file->isValid()) {
                    throw new \Exception('Invalid file upload');
                }

                Log::info('Profile image processing. User->id: ' . $user->id . '. By: ' . auth()->id());

                // Create destination directory if it doesn't exist
                $uploadPath = public_path('uploads/profiles');
                if (!file_exists($uploadPath)) {
                    mkdir($uploadPath, 0755, true);
                }

                // Generate a unique filename
                $filename = uniqid('profile_') . '_' . time() . '.' . $file->getClientOriginalExtension();

                // Move uploaded file directly
                if ($file->move($uploadPath, $filename)) {
                    // Delete old image if it exists
                    if ($user->profile_image && file_exists(public_path($user->profile_image))) {
                        unlink(public_path($user->profile_image));
                    }

                    // Save relative path to database
                    $data['profile_image'] = 'uploads/profiles/' . $filename;

                    Log::info('Profile image uploaded. User->id: ' . $user->id . '. By: ' . auth()->id());
                } else {
                    throw new \Exception('Failed to move uploaded file');
                }
            } catch (\Exception $e) {
                Log::error('Profile image failed while uploading. Error: ' . $e->getMessage() . '. By: ' . auth()->id());
                return redirect()->route('admin.profile.edit')
                    ->withInput()
                    ->withErrors(['profile_image' => 'Error uploading image: ' . $e->getMessage()]);
            }
        }

        $user->update($data);

        Log::info('Profile updated. User->id: ' . $user->id . '. Updated by: ' . auth()->id());

        return redirect()->route('admin.profile.edit')
            ->with('success', __('users.profile_updated'));
    }

    /**
     * Update the user's password.
     */
    public function updatePassword(UpdatePasswordRequest $request)
    {
        $user = Auth::user();
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        Log::info('Password updated. User->id: ' . $user->id . '. Updated by: ' . auth()->id());

        return redirect()->route('admin.profile.edit')
            ->with('success', __('users.password_updated'));
    }
}
