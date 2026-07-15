<?php

namespace App\Http\Controllers\Storefront;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\Orders\OrderAccessService;
use App\Services\Orders\OrderPaymentPageDataService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OrderPaymentShowController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Order $order, OrderAccessService $access, OrderPaymentPageDataService $pageData): Response
    {
        $access->assertCanAccess($request, $order);

        return Inertia::render('storefront/checkout/payment', $pageData->build($order));
    }
}
