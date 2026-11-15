<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvestorOnboardingStep extends Model
{
    use HasFactory;

    protected $table = 'investor_onboarding_steps';

    protected $fillable = [
        'user_id',
        'modal_usd',
        'tier',
        'completed_steps',
        'draft_data',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'modal_usd'       => 'decimal:2',
            'completed_steps' => 'array',
            'draft_data'      => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
