<?php

use App\Http\Controllers\InvestmentController;
use Inertia\Inertia;
use Illuminate\Support\Facades\Route;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Redirect;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\PendingController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StrategyController;
use App\Http\Controllers\AccountTypeController;
use App\Http\Controllers\SelectOptionController;
use App\Http\Controllers\TopUpProfileController;

Route::get('/', function () {
    return Redirect::route('login');
});

Route::get('/dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    // select option
    Route::get('/get_countries', [SelectOptionController::class, 'getCountries'])->name('getCountries');
    Route::get('/get_group_uplines', [SelectOptionController::class, 'getUplinesByGroup'])->name('getUplinesByGroup');
    Route::get('/getAvailableLeader', [SelectOptionController::class, 'getAvailableLeader'])->name('getAvailableLeader');
    Route::get('/getSettingRanks', [SelectOptionController::class, 'getSettingRanks'])->name('getSettingRanks');
    Route::get('/getGroups', [SelectOptionController::class, 'getGroups'])->name('getGroups');
    Route::get('/getLeverages/{id}', [SelectOptionController::class, 'getLeverages'])->name('getLeverages');
    Route::get('/getGroupLeaders', [SelectOptionController::class, 'getGroupLeaders'])->name('getGroupLeaders');
    Route::get('/getGroupMembers', [SelectOptionController::class, 'getGroupMembers'])->name('getGroupMembers');
    Route::get('/getStrategies', [SelectOptionController::class, 'getStrategies'])->name('getStrategies');

    /**
     * ==============================
     *           Pending
     * ==============================
     */
    Route::prefix('pending')->group(function () {
        /**
         * ==============================
         *           Deposit
         * ==============================
         */

        /**
         * ==============================
         *          Withdrawal
         * ==============================
         */

        /**
         * ==============================
         *          Investment
         * ==============================
         */
        Route::prefix('investment')->group(function () {
            Route::get('/', [PendingController::class, 'pending_investment'])->name('pending.investment');
            Route::get('/getPendingInvestmentsData', [PendingController::class, 'getPendingInvestmentsData'])->name('pending.getPendingInvestmentsData');

            Route::post('/pendingInvestmentApproval', [PendingController::class, 'pendingInvestmentApproval'])->name('pending.pendingInvestmentApproval');
        });
    });

    /**
     * ==============================
     *           Members
     * ==============================
     */
    Route::prefix('member')->group(function () {
        // Listing Routes
        Route::get('/listing', [MemberController::class, 'listing'])->name('member.listing');
        Route::get('/getMemberListingData', [MemberController::class, 'getMemberListingData'])->name('member.getMemberListingData');
        Route::get('/getAvailableUplines', [MemberController::class, 'getAvailableUplines'])->name('member.getAvailableUplines');

        Route::get('/access_portal/{user}', [MemberController::class, 'access_portal'])->name('member.access_portal');

        Route::post('/addNewMember', [MemberController::class, 'addNewMember'])->name('member.addNewMember');
        Route::post('/updateMemberStatus', [MemberController::class, 'updateMemberStatus'])->name('member.updateMemberStatus');
        Route::post('/transferUpline', [MemberController::class, 'transferUpline'])->name('member.transferUpline');
        Route::post('/resetPassword', [MemberController::class, 'resetPassword'])->name('member.resetPassword');

        Route::delete('/deleteMember', [MemberController::class, 'deleteMember'])->name('member.deleteMember');

        // details
        Route::get('/detail/{id_number}', [MemberController::class, 'detail'])->name('member.detail');
        Route::get('/getUserData', [MemberController::class, 'getUserData'])->name('member.getUserData');
        Route::get('/media/download/{media}', [MemberController::class, 'downloadMedia'])->name('member.downloadMedia');
        Route::get('/getFinancialInfoData', [MemberController::class, 'getFinancialInfoData'])->name('member.getFinancialInfoData');
        Route::get('/getTradingAccounts', [MemberController::class, 'getTradingAccounts'])->name('member.getTradingAccounts');
        Route::get('/getAdjustmentHistoryData', [MemberController::class, 'getAdjustmentHistoryData'])->name('member.getAdjustmentHistoryData');

        Route::post('/updateProfileInfo', [MemberController::class, 'updateProfileInfo'])->name('member.updateProfileInfo');
        Route::post('/updateKycStatus', [MemberController::class, 'updateKycStatus'])->name('member.updateKycStatus');
        Route::post('/walletAdjustment', [MemberController::class, 'walletAdjustment'])->name('member.walletAdjustment');

        // account listing
        // Route::get('/account_listing', [AccountController::class, 'index'])->name('member.account_listing');
        // Route::get('/getAccountListingData', [AccountController::class, 'getAccountListingData'])->name('member.getAccountListingData');
        // Route::get('/getAccountListingPaginate', [AccountController::class, 'getAccountListingPaginate'])->name('member.getAccountListingPaginate');
        Route::get('/getTradingAccountData', [AccountController::class, 'getTradingAccountData'])->name('member.getTradingAccountData');

        Route::post('/accountAdjustment', [AccountController::class, 'accountAdjustment'])->name('member.accountAdjustment');
        Route::post('/updateLeverage', [AccountController::class, 'updateLeverage'])->name('member.updateLeverage');
        Route::post('/change_password', [AccountController::class, 'change_password'])->name('member.change_password');
        // Route::post('/refreshAllAccount', [AccountController::class, 'refreshAllAccount'])->name('member.refreshAllAccount');
        Route::delete('/accountDelete', [AccountController::class, 'accountDelete'])->name('member.accountDelete');

    });

    /**
     * ==============================
     *            Group
     * ==============================
     */
    Route::prefix('group')->group(function () {
        Route::get('/listing', [GroupController::class, 'index'])->name('group.listing');
        Route::get('/getGroupsData', [GroupController::class, 'getGroupsData'])->name('group.getGroupsData');

        Route::post('/addGroup', [GroupController::class, 'addGroup'])->name('group.addGroup');
    });

    /**
     * ==============================
     *           Strategy
     * ==============================
     */
    Route::prefix('strategy')->group(function () {
        Route::get('/listing', [StrategyController::class, 'index'])->name('strategy.listing');
        Route::get('/getStrategiesOverview', [StrategyController::class, 'getStrategiesOverview'])->name('strategy.getStrategiesOverview');
        Route::get('/getStrategyData', [StrategyController::class, 'getStrategyData'])->name('strategy.getStrategyData');
        Route::get('/getInvestmentDataByStrategy', [StrategyController::class, 'getInvestmentDataByStrategy'])->name('strategy.getInvestmentDataByStrategy');

        Route::post('/addStrategy', [StrategyController::class, 'addStrategy'])->name('strategy.addStrategy');
    });

    /**
     * ==============================
     *          Investment
     * ==============================
     */
    Route::prefix('investment')->group(function () {
        Route::get('/listing', [InvestmentController::class, 'index'])->name('investment.listing');
        Route::get('/getInvestmentsData', [InvestmentController::class, 'getInvestmentsData'])->name('investment.getInvestmentsData');
    });

    /**
     * ==============================
     *         Account Type
     * ==============================
     */
    Route::prefix('account_type')->group(function () {
        Route::get('/account_type', [AccountTypeController::class, 'index'])->name('account_type.index');
        Route::get('/getAccountTypes', [AccountTypeController::class, 'getAccountTypes'])->name('account_type.getAccountTypes');
        // Route::get('/getAccountTypeUsers', [AccountTypeController::class, 'getAccountTypeUsers'])->name('account_type.getAccountTypeUsers');

        Route::post('/addAccountType', [AccountTypeController::class, 'addAccountType'])->name('account_type.addAccountType');
        Route::post('/update', [AccountTypeController::class, 'updateAccountType'])->name('account_type.update');

        Route::patch('/updateStatus/{id}', [AccountTypeController::class, 'updateStatus'])->name('account_type.updateStatus');
    });

    /**
     * ==============================
     *         Configurations
     * ==============================
     */
    Route::prefix('configuration')->group(function () {
        // Top Up Profile
        Route::get('/top_up_profile', [TopUpProfileController::class, 'index'])->name('configuration.top_up_profile');

        Route::post('/addTopUpProfile', [TopUpProfileController::class, 'addTopUpProfile'])->name('account_type.addTopUpProfile');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
