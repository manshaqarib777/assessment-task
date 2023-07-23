<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Merchant;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use App\Services\MerchantService;
use Illuminate\Http\JsonResponse;

class MerchantController extends Controller
{
    public function __construct(
        MerchantService $merchantService
    ) {}

    /**
     * Useful order statistics for the merchant API.
     *
     * @param Request $request Will include a from and to date
     * @return JsonResponse Should be in the form {count: total number of orders in range, commission_owed: amount of unpaid commissions for orders with an affiliate, revenue: sum order subtotals}
     */
    public function orderStats(Request $request)
    {
        // TODO: Complete this method
        $from = $request->input('from');
        $to = $request->input('to');

        $merchant = auth()->user()->merchant;

        $orders = Order::where('merchant_id', $merchant->id)->whereBetween('created_at', [$from, $to])->get();

        $noAffiliate = $orders->whereNull('affiliate_id')->first();

        $count = $orders->count();
        $revenue = $orders->sum('subtotal');
        $commissionsOwed = $orders->sum('commission_owed');

        return response()->json([
            'count' => $count,
            'revenue' => $revenue,
            'commissions_owed' => $commissionsOwed - ($noAffiliate->commission_owed ?? 0),
        ]);
    }
}
