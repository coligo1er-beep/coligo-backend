<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'email',
        'phone',
        'password',
        'email_verified_at',
        'phone_verified_at',
        'first_name',
        'last_name',
        'date_of_birth',
        'gender',
        'profile_photo',
        'user_type',
        'status',
        'address_street',
        'address_city',
        'address_postal_code',
        'address_country',
        'latitude',
        'longitude',
        'is_verified',
        'verification_score',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'email_verified_at' => 'datetime',
        'phone_verified_at' => 'datetime',
        'date_of_birth' => 'date',
        'is_verified' => 'boolean',
        'verification_score' => 'integer',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
    ];

    // Relations
    public function documents()
    {
        return $this->hasMany(UserDocument::class);
    }

    public function otpCodes()
    {
        return $this->hasMany(OtpCode::class);
    }

    public function shipments()
    {
        return $this->hasMany(Shipment::class);
    }

    public function routes()
    {
        return $this->hasMany(Route::class);
    }

    public function transporterMatches()
    {
        return $this->hasMany(\App\Models\MatchRecord::class, 'transporter_id');
    }

    public function senderMatches()
    {
        return $this->hasMany(\App\Models\MatchRecord::class, 'sender_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * Get all conversations where the user is participant 1.
     */
    public function conversations1()
    {
        return $this->hasMany(Conversation::class, 'participant_1_id');
    }

    /**
     * Get all conversations where the user is participant 2.
     */
    public function conversations2()
    {
        return $this->hasMany(Conversation::class, 'participant_2_id');
    }

    /**
     * Get all conversations for the user.
     */
    public function conversations()
    {
        return Conversation::where('participant_1_id', $this->id)
            ->orWhere('participant_2_id', $this->id);
    }

    /**
     * Get users blocked by this user.
     */
    public function blockedUsers()
    {
        return $this->hasMany(UserBlock::class, 'blocker_id');
    }

    /**
     * Get users who have blocked this user.
     */
    public function blockedBy()
    {
        return $this->hasMany(UserBlock::class, 'blocked_id');
    }

    // Scopes
    public function scopeVerified($query)
    {
        return $query->where('is_verified', true);
    }

    public function scopeTransporters($query)
    {
        return $query->whereIn('user_type', ['transporter', 'both']);
    }

    public function scopeSenders($query)
    {
        return $query->whereIn('user_type', ['sender', 'both']);
    }

    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * Calculate and update verification score
     */
    public function calculateVerificationScore()
    {
        // Refresh the model to get latest data
        $this->refresh();

        $score = 0;

        // Email verified: +25 points
        if (!is_null($this->email_verified_at)) {
            $score += 25;
        }

        // Phone verified: +25 points
        if (!is_null($this->phone_verified_at)) {
            $score += 25;
        }

        // Documents verified: +50 points
        $verifiedDocuments = $this->documents()->where('verification_status', 'verified')->count();
        if ($verifiedDocuments > 0) {
            $score += 50;
        }

        // Update score and verification status
        $this->verification_score = $score;
        $this->is_verified = ($score >= 75);
        $this->save();

        return $score;
    }
}
