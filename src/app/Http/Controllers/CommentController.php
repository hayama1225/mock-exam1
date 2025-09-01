<?php

namespace App\Http\Controllers;

use App\Http\Requests\CommentRequest;
use App\Models\Item;
use App\Models\Comment;
use Illuminate\Http\RedirectResponse;

class CommentController extends Controller
{
    public function store(CommentRequest $request, Item $item): RedirectResponse
    {
        // 認証はルートミドルウェアで担保（auth, verified）
        $item->comments()->create([
            'user_id' => auth()->id(),
            'body'    => $request->body,
        ]);

        // 詳細のコメントセクションへ戻す（#comments付き）
        return redirect()
            ->to(route('items.show', $item) . '#comments')
            ->with('status', 'コメントを送信しました。');
    }
}
