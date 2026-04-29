<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController, ProfileController, IdeaController,
    DealController, MessageController, KycController,
    AccessRequestController, NotificationController,
    ReportController, AdminController,
    UploadController, ChatController
};

// ─── Public ───────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('register',         [AuthController::class, 'register']);
    Route::post('login',            [AuthController::class, 'login']);
    Route::post('forgot-password',  [AuthController::class, 'forgotPassword']);
    Route::post('reset-password',   [AuthController::class, 'resetPassword']);
    Route::get('verify-email/{id}/{hash}', [AuthController::class, 'verifyEmail'])
         ->name('verification.verify');
});

Route::get('ideas',         [IdeaController::class, 'index']);      // public marketplace
Route::get('ideas/{idea}',  [IdeaController::class, 'show']);

// ─── Authenticated ─────────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('auth/logout', [AuthController::class, 'logout']);
    Route::get('auth/me',      [AuthController::class, 'me']);

    // Profile
    Route::get('profile',                  [ProfileController::class, 'show']);
    Route::put('profile',                  [ProfileController::class, 'update']);
    Route::post('profile/avatar',          [ProfileController::class, 'uploadAvatar']);
    Route::get('profile/{userId}',         [ProfileController::class, 'showById']);

    // Gate check (used by useUserGate hook)
    Route::get('me/gate',                  [ProfileController::class, 'gate']);

    // Ideas
    Route::post('ideas',                   [IdeaController::class, 'store']);
    Route::put('ideas/{idea}',             [IdeaController::class, 'update']);
    Route::delete('ideas/{idea}',          [IdeaController::class, 'destroy']);
    Route::post('ideas/{idea}/publish',    [IdeaController::class, 'publish']);
    Route::post('ideas/{idea}/pitch-deck', [IdeaController::class, 'uploadPitchDeck']);

    // Saved ideas
    Route::post('ideas/{idea}/save',       [IdeaController::class, 'save']);
    Route::delete('ideas/{idea}/save',     [IdeaController::class, 'unsave']);
    Route::get('saved-ideas',              [IdeaController::class, 'savedIdeas']);

    // Access requests
    Route::get('access-requests',          [AccessRequestController::class, 'index']);
    Route::post('access-requests',         [AccessRequestController::class, 'store']);
    Route::patch('access-requests/{req}/approve', [AccessRequestController::class, 'approve']);
    Route::patch('access-requests/{req}/reject',  [AccessRequestController::class, 'reject']);

    // Deals
    Route::get('deals',                    [DealController::class, 'index']);
    Route::post('deals',                   [DealController::class, 'store']);
    Route::patch('deals/{deal}/accept',    [DealController::class, 'accept']);
    Route::patch('deals/{deal}/reject',    [DealController::class, 'reject']);
    Route::post('deals/{deal}/sign-nda',   [DealController::class, 'signNda']);

    // Messages (polling-based chat)
    Route::get('messages/{userId}',        [MessageController::class, 'thread']);
    Route::post('messages',                [MessageController::class, 'store']);
    Route::patch('messages/{userId}/read', [MessageController::class, 'markRead']);
    Route::get('conversations',            [MessageController::class, 'conversations']);

    // KYC
    Route::get('kyc',                      [KycController::class, 'show']);
    Route::post('kyc/submit',              [KycController::class, 'submit']);
    Route::post('kyc/upload',              [KycController::class, 'upload']);

    // Generic authenticated file upload (used by SubmitIdea etc.)
    Route::post('upload',                  [UploadController::class, 'store']);

    // AI chat
    Route::get('chat/history',             [ChatController::class, 'history']);
    Route::post('chat/history',            [ChatController::class, 'storeMessage']);
    Route::delete('chat/history',          [ChatController::class, 'clearHistory']);
    Route::post('chat/stream',             [ChatController::class, 'stream']);

    // Phone OTP
    Route::post('phone/send-otp',          [ProfileController::class, 'sendOtp']);
    Route::post('phone/verify-otp',        [ProfileController::class, 'verifyOtp']);

    // Notifications
    Route::get('notifications',            [NotificationController::class, 'index']);
    Route::patch('notifications/{n}/read', [NotificationController::class, 'markRead']);
    Route::patch('notifications/read-all', [NotificationController::class, 'readAll']);

    // Reports
    Route::post('reports',                 [ReportController::class, 'store']);
});

// ─── Admin only ────────────────────────────────────────────
Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('stats',                    [AdminController::class, 'stats']);
    Route::get('users',                    [AdminController::class, 'users']);
    Route::match(['post','patch'], 'users/{user}/block',     [AdminController::class, 'blockUser']);
    Route::match(['post','patch'], 'users/{user}/unblock',   [AdminController::class, 'unblockUser']);
    Route::post('grant-role',              [AdminController::class, 'grantRole']);

    Route::get('ideas',                    [AdminController::class, 'ideas']);
    Route::match(['post','patch'], 'ideas/{idea}/approve',   [AdminController::class, 'approveIdea']);
    Route::match(['post','patch'], 'ideas/{idea}/reject',    [AdminController::class, 'rejectIdea']);
    Route::match(['post','patch'], 'ideas/{idea}/toggle',    [AdminController::class, 'toggleIdea']);

    Route::get('kyc',                      [AdminController::class, 'kycList']);
    Route::match(['post','patch'], 'kyc/{kyc}/approve',      [AdminController::class, 'approveKyc']);
    Route::match(['post','patch'], 'kyc/{kyc}/reject',       [AdminController::class, 'rejectKyc']);

    Route::get('reports',                  [AdminController::class, 'reports']);
    Route::match(['post','patch'], 'reports/{report}/resolve',[AdminController::class,'resolveReport']);
    Route::get('analytics',                [AdminController::class, 'analytics']);
});