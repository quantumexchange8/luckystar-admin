<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Spatie\MediaLibrary\HasMedia;
use Illuminate\Notifications\Notifiable;
use Spatie\MediaLibrary\InteractsWithMedia;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements HasMedia
{
    // /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, InteractsWithMedia, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'password_changed_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    protected $appends = [
        'full_name',
        'kyc_status'
    ];

    public function setReferralId(): void
    {
        $characters = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $randomString = 'LKMx';

        $length = 10 - strlen($randomString);

        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }

        $this->referral_code = $randomString;
        $this->save();
    }

    public function assignedGroup($group_id): void
    {
        GroupHasUser::updateOrCreate(
            ['user_id' => $this->id],
            ['group_id' => $group_id]
        );
    }

    public function directChildren(): HasMany
    {
        return $this->hasMany(User::class, 'upline_id', 'id');
    }

    public function getChildrenIds(): array
    {
        return User::query()->where('hierarchyList', 'like', '%-' . $this->id . '-%')
            ->pluck('id')
            ->toArray();
    }

    public function getFullNameAttribute(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    public function getKycStatusAttribute(): string
    {
        if ($this->kycs()->count() === 0) {
            return 'unverified';
        }

        return $this->kycs->every(fn ($kyc) => $kyc->status === 'verified') ? 'verified' : 'unverified';
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class, 'country_id', 'id');
    }

    // public function occupation(): BelongsTo
    // {
    //     return $this->belongsTo(Occupation::class, 'occupation_id', 'id');
    // }

    // public function industry(): BelongsTo
    // {
    //     return $this->belongsTo(Industry::class, 'industry_id', 'id');
    // }

    public function background(): HasOne
    {
        return $this->hasOne(UserBackground::class, 'user_id');
    }

    public function beneficiary(): HasOne
    {
        return $this->hasOne(UserBeneficiary::class, 'user_id');
    }

    public function rank(): BelongsTo
    {
        return $this->belongsTo(GroupRankSetting::class, 'setting_rank_id', 'id');
    }

    public function upline(): BelongsTo
    {
        return $this->belongsTo(User::class, 'upline_id', 'id');
    }

    public function group(): HasOne
    {
        return $this->hasOne(GroupHasUser::class, 'user_id');
    }

    public function paymentAccounts(): HasMany
    {
        return $this->hasMany(PaymentAccount::class, 'user_id', 'id');
    }

    public function wallets(): HasMany
    {
        return $this->hasMany(Wallet::class, 'user_id', 'id');
    }

    public function active_subscriptions(): HasMany
    {
        return $this->hasMany(TradingSubscription::class, 'user_id', 'id')->where('status', 'active');
    }

    public function active_trading_accounts(): HasMany
    {
        return $this->hasMany(TradingAccount::class, 'user_id', 'id')->where('status', 'active');
    }

    public function kycs(): HasMany
    {
        return $this->hasMany(Kyc::class, 'user_id', 'id');
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'user_id', 'id');
    }
}
