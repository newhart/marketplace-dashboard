<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Merchant;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Notification;
use App\Notifications\MerchantApprovalNotification;

class MerchantApprovalController extends Controller
{
    /**
     * Get all pending merchant accounts
     */
    public function pendingMerchants(): JsonResponse
    {
        $pendingMerchants = Merchant::with('user')
            ->where('approval_status', 'pending')
            ->get();
            
        return response()->json([
            'pending_merchants' => $pendingMerchants
        ]);
    }
    
    /**
     * Approve a merchant account
     */
    public function approve(Request $request, $id): JsonResponse
    {
        $merchant = Merchant::findOrFail($id);
        $user = $merchant->user;
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }
        
        // Update merchant status
        $merchant->update([
            'approval_status' => 'approved'
        ]);
        
        // Update user approval status
        $user->update([
            'is_approved' => true
        ]);
        
        // Send notification to merchant
        $user->notify(new MerchantApprovalNotification('approved'));
        
        return response()->json([
            'message' => 'Merchant account approved successfully',
            'merchant' => $merchant
        ]);
    }
    
    /**
     * Reject a merchant account
     */
    public function reject(Request $request, $id): JsonResponse
    {
        $request->validate([
            'rejection_reason' => 'required|string|max:1000'
        ]);
        
        $merchant = Merchant::findOrFail($id);
        $user = $merchant->user;
        
        if (!$user) {
            return response()->json([
                'message' => 'User not found'
            ], 404);
        }
        
        // Update merchant status
        $merchant->update([
            'approval_status' => 'rejected',
            'rejection_reason' => $request->rejection_reason
        ]);
        
        // Send notification to merchant
        $user->notify(new MerchantApprovalNotification('rejected', $request->rejection_reason));
        
        return response()->json([
            'message' => 'Merchant account rejected',
            'merchant' => $merchant
        ]);
    }
}
