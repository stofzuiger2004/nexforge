<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'roles' => $user?->getRoleNames()
                    ->values()
                    ->all()
                    ?? [],
                'can' => [
                    'adminAccess' => $user?->can(
                        'admin.access',
                    )
                        ?? false,

                    'viewOrders' => $user?->can(
                        'orders.viewAny',
                    )
                        ?? false,

                    'viewCustomerData' => $user?->can(
                        'orders.viewCustomerData',
                    )
                        ?? false,

                    'viewPayments' => $user?->can(
                        'payments.view',
                    )
                        ?? false,

                    'viewInventory' => $user?->can(
                        'inventory.view',
                    )
                        ?? false,

                    'adjustInventory' => $user?->can(
                        'inventory.adjust',
                    ) ?? false,

                    'manageInventory' => $user?->can(
                        'inventory.manage',
                    ) ?? false,

                    'viewPrices' => $user?->can(
                        'prices.view',
                    ) ?? false,

                    'managePrices' => $user?->can(
                        'prices.manage',
                    ) ?? false,

                    'manageCatalog' => $user?->can(
                        'catalog.manage'
                    ) ?? false
                ],
            ],
            'flash' => [
                'success' => fn (): ?string => $request->session()->get('success')
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }
}
