<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TempUploadController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'image' => ['required', 'image', 'mimes:jpeg,jpg,png', 'max:5120'],
        ]);

        $path = $request->file('image')->store('tmp', 'public'); // storage/app/public/tmp/...
        return response()->json([
            'path' => $path,
            'url'  => asset('storage/' . $path),
        ]);
    }
}
