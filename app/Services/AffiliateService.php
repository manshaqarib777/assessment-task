<?php

namespace App\Services;

use App\Exceptions\AffiliateCreateException;
use App\Mail\AffiliateCreated;
use App\Models\Affiliate;
use App\Models\Merchant;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class AffiliateService
{
    public function __construct(
        protected ApiService $apiService
    ) {}

    /**
     * Create a new affiliate for the merchant with the given commission rate.
     *
     * @param  Merchant $merchant
     * @param  string $email
     * @param  string $name
     * @param  float $commissionRate
     * @return Affiliate
     */
    public function register(Merchant $merchant, string $email, string $name, float $commissionRate): Affiliate
    {
        // TODO: Complete this method
        if ($merchant->user->email === $email) {
            throw new AffiliateCreateException('Email is already in use as a merchant.');
        }

        // Check if the email is already in use as an affiliate
        $affiliate =   Affiliate::whereHas('user', function ($query) use ($email) {
            $query->where('email', $email);
        })->exists();

        if ($affiliate) {
            throw new AffiliateCreateException('Email is already in use as an affiliate.');
        }
        // Create a discount code using an external API
        $discountCode = $this->apiService->createDiscountCode();

        // Create a user for the affiliate
        $user = User::create([
            'name' => $name,
            'email' => $email,
            'type' => User::TYPE_AFFILIATE
        ]);
        // Create the affiliate
        $affiliate = Affiliate::create([
            'user_id' => $user->id,
            'merchant_id' => $merchant->id,
            'commission_rate' => $commissionRate,
            'discount_code' => $discountCode['code'],

        ]);

        // Send the AffiliateCreated email
        Mail::to($email)->send(new AffiliateCreated($affiliate));

        return $affiliate;
    }
}
