<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\RedirectResponse;

class LikeController extends Controller
{
    public function toggle(Item $item): RedirectResponse
    {
        /** @var \App\Models\User $user */
        $user = auth()->user();

        if ($user->likedItems()->where('item_id', $item->id)->exists()) {
            $user->likedItems()->detach($item->id);
        } else {
            $user->likedItems()->attach($item->id);
        }

        return back();
    }
}
