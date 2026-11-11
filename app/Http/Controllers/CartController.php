<?php

namespace App\Http\Controllers;

use App\Models\Coupon;
use App\Models\MenuItem;
use App\Services\CartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    public function __construct(protected CartService $cart) {}

    public function show(Request $request): JsonResponse
    {
        return response()->json($this->cart->summary($request->query('type', 'delivery')));
    }

    public function add(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer'],
            'quantity' => ['nullable', 'integer', 'between:1,'.CartService::MAX_QTY],
        ]);

        $item = MenuItem::available()->find($data['item_id']);

        if (! $item) {
            return response()->json(['message' => 'Sorry, this dish is not available right now.'], 422);
        }

        $this->cart->add($item->id, $data['quantity'] ?? 1);
        $summary = $this->cart->summary();

        if ($summary['notices'] !== []) {
            return response()->json(['message' => $summary['notices'][0]] + $summary, 422);
        }

        return response()->json($summary + ['message' => $item->name.' added to your order']);
    }

    public function update(Request $request): JsonResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer'],
            'quantity' => ['required', 'integer', 'between:0,'.CartService::MAX_QTY],
            'type' => ['nullable', 'in:delivery,pickup,dine_in'],
        ]);

        $this->cart->set($data['item_id'], $data['quantity']);

        return response()->json($this->cart->summary($data['type'] ?? 'delivery'));
    }

    public function coupon(Request $request): JsonResponse
    {
        $data = $request->validate([
            'code' => ['nullable', 'string', 'max:40'],
            'type' => ['nullable', 'in:delivery,pickup,dine_in'],
        ]);

        if (filled($data['code'] ?? null) && ! Coupon::findByCode($data['code'])) {
            return response()->json(['message' => 'This coupon code is not valid.'], 422);
        }

        $this->cart->setCoupon($data['code'] ?? null);
        $summary = $this->cart->summary($data['type'] ?? 'delivery');

        if ($summary['coupon_error']) {
            $this->cart->setCoupon(null);

            return response()->json(['message' => $summary['coupon_error']], 422);
        }

        return response()->json($summary);
    }

    /** Delivery area and "use my points" choices made on the checkout page. */
    public function options(Request $request): JsonResponse
    {
        $data = $request->validate([
            'area_id' => ['nullable', 'integer'],
            'use_points' => ['nullable', 'boolean'],
            'type' => ['nullable', 'in:delivery,pickup,dine_in'],
        ]);

        if ($request->has('area_id')) {
            $this->cart->setArea($data['area_id'] ?? null);
        }

        if ($request->has('use_points')) {
            $this->cart->usePoints((bool) $data['use_points'] && $request->user() !== null);
        }

        return response()->json($this->cart->summary($data['type'] ?? 'delivery'));
    }

    public function clear(): JsonResponse
    {
        $this->cart->clear();

        return response()->json($this->cart->summary());
    }
}
