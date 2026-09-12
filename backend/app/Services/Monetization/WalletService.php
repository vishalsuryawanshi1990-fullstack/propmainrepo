<?php

namespace App\Services\Monetization;

use App\Models\User;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Support\Facades\DB;

/**
 * The wallet balance is never mutated directly — every change is a ledger
 * row in wallet_transactions, per 07-backend-tasks-laravel.md Sprint 3.
 */
class WalletService
{
    public function createForNewUser(User $user): Wallet
    {
        return DB::transaction(function () use ($user) {
            $wallet = Wallet::create(['user_id' => $user->id, 'contact_unlock_credits' => 0]);

            $credits = (int) config('monetization.free_signup_credits');

            if ($credits > 0) {
                $this->credit($wallet, $credits, 'signup_bonus');
            }

            return $wallet->fresh();
        });
    }

    public function credit(Wallet $wallet, int $credits, string $source, ?int $referenceId = null): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $credits, $source, $referenceId) {
            $wallet->lockForUpdate();
            $wallet->increment('contact_unlock_credits', $credits);

            return $wallet->transactions()->create([
                'type' => 'credit',
                'source' => $source,
                'amount' => $credits,
                'reference_id' => $referenceId,
            ]);
        });
    }

    public function debit(Wallet $wallet, int $credits, string $source, ?int $referenceId = null): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $credits, $source, $referenceId) {
            $locked = Wallet::query()->whereKey($wallet->id)->lockForUpdate()->firstOrFail();

            if ($locked->contact_unlock_credits < $credits) {
                throw new InsufficientCreditsException;
            }

            $locked->decrement('contact_unlock_credits', $credits);

            return $locked->transactions()->create([
                'type' => 'debit',
                'source' => $source,
                'amount' => $credits,
                'reference_id' => $referenceId,
            ]);
        });
    }

    /**
     * Cashback rewards land in wallets.cashback_balance rather than
     * contact_unlock_credits — same ledger table, a different column of
     * the wallet is what actually moved (doc03 doesn't split the ledger
     * by balance type, so the source/reference_id pair is what
     * disambiguates it when reading the ledger back).
     */
    public function creditCashback(Wallet $wallet, float $amount, string $source, ?int $referenceId = null): WalletTransaction
    {
        return DB::transaction(function () use ($wallet, $amount, $source, $referenceId) {
            $wallet->lockForUpdate();
            $wallet->increment('cashback_balance', $amount);

            return $wallet->transactions()->create([
                'type' => 'credit',
                'source' => $source,
                'amount' => $amount,
                'reference_id' => $referenceId,
            ]);
        });
    }
}
