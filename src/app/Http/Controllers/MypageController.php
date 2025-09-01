<?php

namespace App\Http\Controllers;

use App\Models\Purchase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MypageController extends Controller
{
    public function index(Request $request)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();      // ← まず取り出す
        $user->load('profile');    // ← そのあとで load

        $page = $request->query('tab', 'buy'); #'buy' or 'sell'

        if ($page === 'sell') {
            $items = $user->items()
                ->with('categories')
                ->withCount(['likedByUsers as likes_count', 'comments'])
                ->latest()->get();

            return view('mypage.index', [
                'user'       => $user,
                'tab'       => 'sell',
                'items'      => $items,
                'purchases'  => collect(),
            ]);
        }

        $purchases = Purchase::where('buyer_id', $user->id)
            ->with(['item.categories', 'item.seller'])
            ->latest()->get();

        return view('mypage.index', [
            'user'       => $user,
            'tab'       => 'buy',
            'items'      => collect(),
            'purchases'  => $purchases,
        ]);
    }
}
