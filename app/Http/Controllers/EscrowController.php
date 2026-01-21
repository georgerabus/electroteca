<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\EscrowTransaction;
use App\Services\EscrowService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use RuntimeException;
use Exception;

class EscrowController extends Controller
{
    private EscrowService $escrowService;

    public function __construct(EscrowService $escrowService)
    {
        $this->escrowService = $escrowService;
    }

    /**
     * Initialize escrow for an order
     */
    public function holdFunds(Request $request, Order $order): JsonResponse
    {
        try {
            $validated = $request->validate([
                'amount' => 'required|numeric|min:0.01',
                'inspection_period_days' => 'integer|min:1|max:90',
            ]);

            $escrow = $this->escrowService->holdFunds(
                $order,
                $validated['amount'],
                $validated['inspection_period_days'] ?? 7
            );

            return response()->json([
                'success' => true,
                'message' => 'Escrow funds held successfully',
                'escrow' => $escrow,
            ], 201);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Release escrow when item returned on time
     */
    public function releaseFunds(Order $order): JsonResponse
    {
        try {
            $escrow = $this->escrowService->getActiveEscrow($order);

            if (!$escrow) {
                return response()->json([
                    'success' => false,
                    'error' => 'No active escrow found',
                ], 404);
            }

            $released = $this->escrowService->releaseFunds($escrow, 'on_time_return');

            return response()->json([
                'success' => true,
                'message' => 'Escrow released successfully',
                'escrow' => $released,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Deduct escrow for damages
     */
    public function deductForDamage(Request $request, Order $order): JsonResponse
    {
        try {
            $validated = $request->validate([
                'damage_amount' => 'required|numeric|min:0.01',
                'damage_description' => 'string|nullable',
            ]);

            $escrow = $this->escrowService->getActiveEscrow($order);

            if (!$escrow) {
                return response()->json([
                    'success' => false,
                    'error' => 'No active escrow found',
                ], 404);
            }

            $result = $this->escrowService->deductForDamage(
                $escrow,
                $validated['damage_amount'],
                $validated['damage_description'] ?? ''
            );

            return response()->json([
                'success' => true,
                'message' => 'Damage deducted and funds released',
                'result' => $result,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Refund entire escrow
     */
    public function refundEscrow(Request $request, Order $order): JsonResponse
    {
        try {
            $validated = $request->validate([
                'reason' => 'string|nullable',
            ]);

            $escrow = $this->escrowService->getActiveEscrow($order);

            if (!$escrow) {
                return response()->json([
                    'success' => false,
                    'error' => 'No active escrow found',
                ], 404);
            }

            $refunded = $this->escrowService->refundEscrow(
                $escrow,
                $validated['reason'] ?? 'order_cancelled'
            );

            return response()->json([
                'success' => true,
                'message' => 'Escrow refunded successfully',
                'escrow' => $refunded,
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Get escrow details for an order
     */
    public function show(Order $order): JsonResponse
    {
        $escrow = $this->escrowService->getActiveEscrow($order);

        if (!$escrow) {
            return response()->json([
                'success' => false,
                'error' => 'No active escrow found',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'escrow' => $escrow->load('order', 'walletTransaction'),
        ]);
    }

    /**
     * Get all escrow transactions for an order
     */
    public function orderEscrows(Order $order): JsonResponse
    {
        $escrows = $order->escrowTransactions()->latest()->get();

        return response()->json([
            'success' => true,
            'count' => $escrows->count(),
            'escrows' => $escrows,
        ]);
    }

    /**
     * Get user's escrow history
     */
    public function userHistory(): JsonResponse
    {
        $user = auth()->user();

        $sellerHistory = $this->escrowService->getSellerEscrowHistory($user);
        $borrowerHistory = $this->escrowService->getBorrowerEscrowHistory($user);
        $stats = $this->escrowService->getUserEscrowStats($user);

        return response()->json([
            'success' => true,
            'as_seller' => $sellerHistory,
            'as_borrower' => $borrowerHistory,
            'statistics' => $stats,
        ]);
    }

    /**
     * Get escrow statistics for user
     */
    public function statistics(): JsonResponse
    {
        $user = auth()->user();
        $stats = $this->escrowService->getUserEscrowStats($user);

        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }
}
