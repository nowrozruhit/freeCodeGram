<?php

namespace App\Http\Controllers;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class ProfilesController extends Controller
{
    public function index(\App\User $user)
    {
        $follows = (auth()->user()) ? auth()->user()->following->contains($user->id) : false;
        $postCount = Cache::remember(
            'count.posts'.$user->id, now()->addSeconds(30), 
            function () use ($user) {
                return $user->posts->count();
            });

        $followerCount = Cache::remember(
            'count.followers'.$user->id, now()->addSeconds(30), 
            function () use ($user){
                return $user->profile->followers->count();
            });

        $followingCount = Cache::remember(
            'count.following'.$user->id, now()->addSeconds(30), 
            function () use ($user){
                return $user->following()->count();    
            });

        return view('profiles.index', compact('user', 'follows', 'postCount', 'followerCount', 'followingCount'));
    }

    public function edit(\App\User $user)
    {   
        $this->authorize('update', $user->profile);
        return view('profiles.edit', compact('user'));
    }

    public function update(\App\User $user)
    {
        $this->authorize('update', $user->profile);
        $data = request()->validate([
            'title' => 'required',
            'description' => 'required',
            'url' => 'url',
            'image' => ''
        ]);

        if(request('image')){
            $imagePath = request('image')->store('profile', 'public');
            $imagePath = '/storage/'.$imagePath;
            $image = Image::make(public_path($imagePath))->fit(1000, 1000);
            $image->save();
            $imageArray = ['image' => $imagePath];
        }

        auth()->user()->profile->update(array_merge(
            $data,
            $imageArray ?? []
        ));

        return redirect("/profile/".$user->id);
    }
}
