<?php

namespace App\Http\Controllers;

use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Requests\ExhibitionRequest;
use Illuminate\Support\Facades\Storage;   #追加
use Illuminate\Support\Str;   #追加

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $q   = trim((string)$request->query('q'));
        $tab = $request->query('tab');

        // マイリストタブ
        if ($tab === 'mylist') {
            if (!Auth::check()) {
                $items = collect();
                return view('items.index', compact('items', 'q', 'tab'));
            }

            /** @var \App\Models\User $u */
            $u = Auth::user();

            $query = $u->likedItems()
                ->with(['categories', 'seller'])
                ->withCount(['likedByUsers as likes_count', 'comments']);

            if ($q !== '') {
                $query->where('name', 'LIKE', "%{$q}%");
            }

            // マイリストも昇順に戻す
            $items = $query->orderBy('id', 'asc')->get();
            return view('items.index', compact('items', 'q', 'tab'));
        }

        // おすすめタブ（= 通常一覧）
        $query = Item::query()
            ->with(['categories', 'seller'])
            ->withCount(['likedByUsers as likes_count', 'comments']);

        // ログイン中は常に自分の出品を除外
        if (Auth::check()) {
            $query->where('seller_id', '!=', Auth::id());
        }

        if ($q !== '') {
            $query->where('name', 'LIKE', "%{$q}%");
        }

        // ★ 常に昇順（Seederの順）
        $items = $query->orderBy('id', 'asc')->get();

        return view('items.index', compact('items', 'q', 'tab'));
    }

    public function show(Item $item)
    {
        $item->load(['categories', 'seller', 'comments.user'])
            ->loadCount(['likedByUsers as likes_count', 'comments']);

        /** @var \App\Models\User|null $u */
        $u = Auth::user();

        // whereKey() は主キー一致。IDE も型解決できます
        $liked = $u ? $u->likedItems()->whereKey($item->id)->exists() : false;

        return view('items.show', compact('item', 'liked'));
    }

    public function create()
    {
        // 並び順を要件通りに固定したい場合（推奨）
        $categories = \App\Models\Category::orderByRaw("
            FIELD(name,
              'ファッション','家電','インテリア','レディース','メンズ','コスメ',
              '本','ゲーム','スポーツ','キッチン','ハンドメイド','アクセサリー',
              'おもちゃ','ベビー・キッズ'
            )
        ")->get();

        $conditions = ['良好', '目立った傷や汚れなし', 'やや傷や汚れあり', '状態が悪い'];

        return view('items.create', compact('categories', 'conditions'));
    }

    public function store(ExhibitionRequest $request)
    {
        // どちらかが必須（ExhibitionRequest側でも担保）
        if (!$request->hasFile('image') && !$request->filled('image_tmp')) {
            return back()->withErrors(['image' => '画像を選択してください。'])->withInput();
        }

        // 画像パス決定：新規アップロード or 一時から移動
        $imagePath = null;

        if ($request->hasFile('image')) {
            // 通常アップロード
            $imagePath = $request->file('image')->store('items', 'public');
        } elseif ($request->filled('image_tmp') && Storage::disk('public')->exists($request->image_tmp)) {
            // 一時 → 本保存へ移動
            $ext  = pathinfo($request->image_tmp, PATHINFO_EXTENSION);
            $dest = 'items/' . Str::uuid() . '.' . $ext;
            Storage::disk('public')->move($request->image_tmp, $dest);
            $imagePath = $dest;
        }

        // 保存
        $item = Item::create([
            'seller_id'   => Auth::id(),
            'name'        => $request->name,
            'brand'       => $request->brand,
            'description' => $request->description,
            'price'       => $request->price,
            'condition'   => $request->condition,
            'image_path'  => $imagePath, // show側は asset('storage/'.$image_path)
        ]);

        $item->categories()->sync($request->categories);

        return redirect()->route('items.show', $item)
            ->with('status', '出品が完了しました。');
    }
}
