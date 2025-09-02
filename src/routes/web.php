<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    ProfileController,
    ItemController,
    LikeController,
    CommentController,
    PurchaseController,
    MypageController,
    TempUploadController, #一時アップ用(出品画面バリデーション後に選択画像維持)
    CheckoutController
};

/*
|--------------------------------------------------------------------------
| Public
|--------------------------------------------------------------------------
*/
// トップ（商品一覧）※プロフィール未完了ならミドルウェアでプロフへ誘導
Route::middleware('force.profile')
    ->get('/', [ItemController::class, 'index'])
    ->name('items.index');

// 商品詳細（誰でも閲覧可）
Route::get('/item/{item}', [ItemController::class, 'show'])
    ->whereNumber('item')
    ->name('items.show');

// Stripe 戻り（誰でもアクセス）
Route::get('/purchase/success', [PurchaseController::class, 'success'])
    ->name('purchase.success');
Route::get('/purchase/cancel/{item}', [PurchaseController::class, 'cancel'])
    ->whereNumber('item')
    ->name('purchase.cancel');

/*
|--------------------------------------------------------------------------
| Auth + Verified（プロフィール設定は force.profile をかけない）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified'])->group(function () {
    // プロフィール編集・更新（未完了ユーザーが入れる必要があるため force.profile を付けない）
    Route::get('/mypage/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::post('/mypage/profile', [ProfileController::class, 'update'])->name('profile.update');
});

/*
|--------------------------------------------------------------------------
| Auth + Verified + force.profile（その他保護ルート）
|--------------------------------------------------------------------------
*/
Route::middleware(['auth', 'verified', 'force.profile'])->group(function () {
    // マイページ
    Route::get('/mypage', [MypageController::class, 'index'])->name('mypage.index');

    // 出品
    Route::get('/sell',  [ItemController::class, 'create'])->name('items.create');
    Route::post('/sell', [ItemController::class, 'store'])->name('items.store');

    // いいね／コメント
    Route::post('/item/{item}/like', [LikeController::class, 'toggle'])
        ->whereNumber('item')
        ->name('items.like');
    Route::post('/item/{item}/comments', [CommentController::class, 'store'])
        ->whereNumber('item')
        ->name('items.comments.store');

    // 購入
    Route::get('/purchase/{item}', [PurchaseController::class, 'show'])
        ->whereNumber('item')
        ->name('purchase.show');
    Route::post('/purchase/{item}', [PurchaseController::class, 'submit'])
        ->whereNumber('item')
        ->name('purchase.submit');
    Route::get('/purchase/address/{item}', [PurchaseController::class, 'editAddress'])
        ->whereNumber('item')
        ->name('purchase.address.edit');
    Route::post('/purchase/address/{item}', [PurchaseController::class, 'updateAddress'])
        ->whereNumber('item')
        ->name('purchase.address.update');

    // 一時アップロード
    Route::post('/upload/tmp', [TempUploadController::class, 'store'])
        ->name('upload.tmp');

    // POST だけは保護されたままでOK
    Route::post('/checkout', [CheckoutController::class, 'create'])->name('checkout.create');
});
// ここは従来どおり公開ルート群
Route::get('/checkout/success', [CheckoutController::class, 'success'])->name('checkout.success');
Route::get('/checkout/cancel',  [CheckoutController::class, 'cancel'])->name('checkout.cancel');

// （任意）古い /home リンク対策
// Route::redirect('/home', '/')->name('home.redirect');

Route::view('/home', 'auth.verified-close')->name('home');

// （任意）404 フォールバック
// Route::fallback(fn () => abort(404));

// 認証状態の簡易チェックAPI（要ログイン）
Route::middleware('auth')->get('/email/verified-check', function () {
    return response()->json([
        'verified' => auth()->user()->hasVerifiedEmail(),
    ]);
});
