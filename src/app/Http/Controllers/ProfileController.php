<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileRequest;
use App\Models\Profile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = Auth::user();

        // 初回アクセスでプロフィールレコードがなければ作成
        $profile = Profile::firstOrCreate(
            ['user_id' => $user->id],
            ['username' => $user->name] // 既存のnameを仮で入れておく
        );

        return view('mypage.profile', compact('profile', 'user'));
    }

    public function update(ProfileRequest $request)
    {
        $user = Auth::user();
        $profile = Profile::firstOrCreate(['user_id' => $user->id]);

        // 画像アップロード
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public'); // storage/app/public/avatars
            $profile->avatar_path = $path;
        }

        // 入力反映
        $profile->username = $request->username;
        $profile->zip      = $request->zip;
        $profile->address  = $request->address;
        $profile->building = $request->building;

        // 必須が埋まっていれば完了にする
        if (
            !$profile->profile_completed_at &&
            $profile->username && $profile->zip && $profile->address
        ) {
            $profile->profile_completed_at = now();
        }

        $profile->save();

        return redirect()
            ->intended(route('mypage.index'))     #保存してあれば元のURLへ、無ければ /mypage
            ->with('status', 'プロフィールを更新しました。');
    }
}
