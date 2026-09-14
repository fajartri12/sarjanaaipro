<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\CitationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DemoController;
use App\Http\Controllers\DraftController;
use App\Http\Controllers\ExportController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectChatController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\QuotaController;
use App\Http\Controllers\ReferenceController;
use App\Http\Controllers\ResearchController;
use App\Http\Controllers\ReviewerController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\SemproController;
use App\Http\Controllers\SimilarityController;
use App\Http\Controllers\SubscriptionController;
use App\Http\Controllers\TitleController;
use App\Models\Plan;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'plans' => Plan::where('is_active', true)
            ->orderBy('sort')
            ->get(['name', 'slug', 'price', 'interval', 'features', 'limits', 'description', 'is_popular']),
        'studentCount' => \App\Models\User::count(),
    ]);
})->name('home');

// webhook pembayaran: tanpa auth, diverifikasi lewat signature provider
Route::post('/webhook/payment/{provider}', [SubscriptionController::class, 'webhook'])
    ->name('webhook.payment');

// demo di landing page, tanpa login
Route::post('/demo/titles', [DemoController::class, 'titles'])
    ->middleware('throttle:10,1')
    ->name('demo.titles');

Route::middleware(['auth', 'verified'])->group(function () {
    // Notifikasi
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllRead'])->name('notifications.markAllRead');
    Route::post('/notifications/{notification}/mark-read', [NotificationController::class, 'markRead'])->name('notifications.markRead');

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::post('/dashboard/onboarding/dismiss', [DashboardController::class, 'dismissOnboarding'])
        ->name('dashboard.onboarding.dismiss');

    Route::get('/panduan', fn () => view('guide.index', [
        // Ambil dari DB, bukan hardcode: admin bisa mengubah harga lewat panel.
        'plans' => Plan::where('is_active', true)->orderBy('sort')->get(),
    ]))->name('guide.index');

    Route::resource('projects', ProjectController::class);

    Route::prefix('projects/{project}/chat')->name('projects.chat.')->group(function () {
        Route::get('/', [ProjectChatController::class, 'index'])->name('index');
        Route::post('/', [ProjectChatController::class, 'store'])->name('new');
        Route::post('/{conversation}/send', [ProjectChatController::class, 'send'])
            ->middleware('ai.limit:ai_chat')->name('send');
        Route::delete('/{conversation}', [ProjectChatController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('projects/{project}/draft')->name('draft.')->group(function () {
        Route::get('/', [DraftController::class, 'index'])->name('index');
        Route::get('/{section}/references', [CitationController::class, 'references'])->name('citations.references');
        Route::post('/{section}/citations', [CitationController::class, 'store'])->name('citations.store');
        Route::post('/{section}/review-paragraph', [ReviewerController::class, 'reviewParagraph'])
            ->middleware('ai.limit:ai_reviewer')->name('review-paragraph');
        Route::post('/{section}/sources', [DraftController::class, 'sources'])->name('sources');
        Route::get('/{section}/versions', [DraftController::class, 'versions'])->name('versions');
        Route::post('/{section}/versions/{version}/restore', [DraftController::class, 'restore'])->name('versions.restore');
        Route::get('/{section}', [DraftController::class, 'show'])->name('show');
        Route::patch('/{section}', [DraftController::class, 'save'])->name('save');
        Route::post('/{section}/autosave', [DraftController::class, 'autosave'])->name('autosave');
        Route::post('/{section}/generate', [DraftController::class, 'generate'])
            ->middleware('ai.limit:ai_chat')->name('generate');
        Route::post('/{section}/action', [DraftController::class, 'action'])
            ->middleware('ai.limit:ai_chat')->name('action');
    });

    Route::prefix('titles')->name('titles.')->group(function () {
        Route::get('/', [TitleController::class, 'index'])->name('index');
        Route::post('/generate', [TitleController::class, 'generate'])
            ->middleware('ai.limit:generate_titles')->name('generate');
        Route::get('/{title}', [TitleController::class, 'show'])->name('show');
        Route::post('/{title}/analyze', [TitleController::class, 'analyze'])
            ->middleware('ai.limit:generate_titles')->name('analyze');
        Route::post('/{title}/select', [TitleController::class, 'select'])->name('select');
    });

    Route::prefix('research')->name('research.')->group(function () {
        Route::get('/', [ResearchController::class, 'index'])->name('index');
        Route::post('/upload', [ResearchController::class, 'upload'])
            ->middleware('ai.limit:pdf_analysis')->name('upload');
        Route::post('/ask', [ResearchController::class, 'ask'])
            ->middleware('ai.limit:ai_chat')->name('ask');
        Route::post('/gap', [ResearchController::class, 'gap'])
            ->middleware('ai.limit:ai_chat')->name('gap');
        Route::post('/methodology', [ResearchController::class, 'methodology'])
            ->middleware('ai.limit:ai_chat')->name('methodology');
        Route::get('/{document}', [ResearchController::class, 'show'])->name('show');
        Route::get('/{document}/download', [ResearchController::class, 'download'])->name('download');
        Route::post('/{document}/analyze', [ResearchController::class, 'analyze'])
            ->middleware('ai.limit:pdf_analysis')->name('analyze');
        Route::delete('/{document}', [ResearchController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('references')->name('references.')->group(function () {
        Route::get('/', [ReferenceController::class, 'index'])->name('index');
        Route::get('/search', [ReferenceController::class, 'search'])->name('search');
        Route::post('/', [ReferenceController::class, 'store'])->name('store');
        Route::post('/import-doi', [ReferenceController::class, 'importDoi'])->name('doi');
        Route::post('/bibliography', [ReferenceController::class, 'bibliography'])->name('bibliography');
        Route::put('/{reference}', [ReferenceController::class, 'update'])->name('update');
        Route::delete('/{reference}', [ReferenceController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('reviewer')->name('reviewer.')->group(function () {
        Route::get('/', [ReviewerController::class, 'index'])->name('index');
        Route::get('/{project}', [ReviewerController::class, 'show'])->name('show');
        Route::post('/{project}', [ReviewerController::class, 'review'])
            ->middleware('ai.limit:ai_reviewer')->name('review');
        Route::post('/{project}/section', [ReviewerController::class, 'reviewSection'])
            ->middleware('ai.limit:ai_reviewer')->name('section');
        Route::post('/{project}/consistency', [ReviewerController::class, 'consistency'])
            ->middleware('ai.limit:ai_reviewer')->name('consistency');
    });

    Route::prefix('similarity')->name('similarity.')->group(function () {
        Route::get('/', [SimilarityController::class, 'index'])->name('index');
        Route::get('/{project}', [SimilarityController::class, 'show'])->name('show');
        Route::post('/{project}/section', [SimilarityController::class, 'checkSection'])
            ->middleware('ai.limit:ai_reviewer')->name('section');
        Route::post('/{project}/full', [SimilarityController::class, 'checkFullProject'])
            ->middleware('ai.limit:ai_reviewer')->name('full');
    });

    Route::prefix('sempro')->name('sempro.')->group(function () {
        Route::get('/', [SemproController::class, 'index'])->name('index');
        Route::post('/', [SemproController::class, 'store'])->name('store');
        Route::get('/readiness/{project}', [SemproController::class, 'readiness'])->name('readiness');
        Route::get('/{session}', [SemproController::class, 'show'])->name('show');
        Route::post('/{session}/answer', [SemproController::class, 'answer'])->name('answer');
        Route::post('/{session}/follow-up', [SemproController::class, 'followUp'])->name('follow-up');
        Route::post('/{session}/finish', [SemproController::class, 'finish'])->name('finish');
        Route::get('/{session}/result', [SemproController::class, 'result'])->name('result');
        Route::delete('/{session}', [SemproController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('subscription')->name('subscription.')->group(function () {
        Route::get('/prices', [SubscriptionController::class, 'prices'])->name('prices');
        Route::get('/my', [SubscriptionController::class, 'mySubscription'])->name('my');
        Route::post('/checkout/{plan}', [SubscriptionController::class, 'checkout'])->name('checkout');
        Route::patch('/payments/{payment}/channel', [SubscriptionController::class, 'updateChannel'])->name('payments.channel');
        Route::delete('/cancel', [SubscriptionController::class, 'cancel'])->name('cancel');
        Route::get('/invoices', [InvoiceController::class, 'index'])->name('invoices');
    });

    Route::get('/invoices/{payment}/download', [InvoiceController::class, 'download'])->name('invoices.download');

    Route::get('/quota', [QuotaController::class, 'index'])->name('quota');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/search', SearchController::class)->name('search');

    Route::get('/projects/{project}/export/pdf', [ExportController::class, 'pdf'])->name('export.pdf');
    Route::get('/projects/{project}/export/docx', [ExportController::class, 'docx'])->name('export.docx');
    Route::get('/projects/{project}/similarity/export', [ExportController::class, 'similarityPdf'])->name('export.similarity');

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/users', [AdminController::class, 'users'])->name('users');
        Route::patch('/users/{user}', [AdminController::class, 'updateUser'])->name('users.update');
        Route::patch('/users/{user}/subscription', [AdminController::class, 'updateSubscription'])->name('users.subscription');
        Route::get('/usage', [AdminController::class, 'usage'])->name('usage');
        Route::get('/ai', [AdminController::class, 'ai'])->name('ai');
        Route::get('/payments', [AdminController::class, 'payments'])->name('payments');
        Route::post('/payments/{payment}/confirm', [AdminController::class, 'confirmPayment'])->name('payments.confirm');
        Route::post('/payments/{payment}/expire', [AdminController::class, 'expirePayment'])->name('payments.expire');
        Route::get('/plans', [AdminController::class, 'plans'])->name('plans');
        Route::patch('/plans/{plan}', [AdminController::class, 'updatePlan'])->name('plans.update');
        Route::get('/settings', [AdminController::class, 'settings'])->name('settings');
        Route::patch('/settings', [AdminController::class, 'updateSettings'])->name('settings.update');
        Route::get('/projects', [AdminController::class, 'projects'])->name('projects');
        Route::patch('/projects/{project}', [AdminController::class, 'updateProject'])->name('projects.update');
        Route::get('/activity', [AdminController::class, 'activity'])->name('activity');
    });
});

require __DIR__.'/auth.php';
