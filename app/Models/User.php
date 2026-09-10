<?php

namespace App\Models;

use Modules\Director\Models\Camp;
use Spatie\Permission\Traits\HasRoles;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Director\Models\CampRefereeCheckin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable implements JWTSubject
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles, SoftDeletes;

    protected $guard_name = ['api'];

    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'first_name',
        'last_name',
        'address',
        'username',
        'slug',
        'email',
        'phone',
        'password',
        'biography',
        'jourcy_number',

        'is_phone_show',
        'is_address_show',

        'otp',
        'otp_expires_at',
        'otp_verified_at',
        'reset_password_token',
        'reset_password_token_expire_at',

        'avatar',
        'last_activity_at',

        'stripe_customer_id',
        'stripe_account_id',

        'status',

        'email_verification_token',
        'email_verification_token_expires_at',
        'receive_sms_notifications',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];
    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'otp_verified_at' => 'datetime',
            'password' => 'hashed',
            'last_activity_at' => 'datetime',
            'stripe_onboarded_at' => 'datetime',
            'receive_sms_notifications' => 'boolean',
            'is_phone_show' => 'boolean',
            'is_address_show' => 'boolean',
        ];
    }


    public function getAvatarAttribute($value): string | null
    {
        if (filter_var($value, FILTER_VALIDATE_URL)) {
            return $value;
        }
        // Check if the request is an API request
        if (request()->is('api/*') && !empty($value)) {
            // Return the full URL for API requests
            return url($value);
        }

        // Return only the path for web requests
        return $value;
    }

    public function getRoleAttribute()
    {
        return  $this->getRoleNames()->first();
    }

    public function firebaseTokens()
    {
        return $this->hasMany(FirebaseTokens::class);
    }

    public function profile()
    {
        return $this->hasOne(Profile::class);
    }

    public function referee()
    {
        return $this->belongsTo(User::class, 'referee_id');
    }

    // User Model
    public function evaluatorEvaluations()
    {
        return $this->hasMany(RefereeEvaluation::class, 'evaluator_id');
    }

    // camp checkin referee
    public function refereeCheckins()
    {
        return $this->hasMany(CampRefereeCheckin::class, 'referee_id');
    }

    // referee evaluation
    public function evaluations()
    {
        return $this->hasMany(RefereeEvaluation::class, 'referee_id');
    }

    /**
     * Get unread announcements count
     */
    public function unreadAnnouncementsCount()
    {
        return $this->unreadNotifications()
            ->where('type', 'AnnouncementNotification')
            ->count();
    }

    /**
     * Referee camps with jersey numbers
     */
    public function refereeCamps()
    {
        return $this->belongsToMany(
            Camp::class,
            'camp_referee_jearsy_numbers',
            'referee_id',
            'camp_id'
        )->withPivot('jersey_number')
            ->withTimestamps();
    }

    /**
     * Route notifications for the Twilio SMS channel.
     *
     * @return string|null  E.164 phone number or null to skip
     */
    public function routeNotificationForTwilio(): ?string
    {
        // Skip test users (IDs 3 through 90)
        if ($this->id >= 3 && $this->id <= 90) {
            return null;
        }

        // Skip users who opted out of SMS notifications
        if (isset($this->receive_sms_notifications) && !$this->receive_sms_notifications) {
            return null;
        }

        if (empty($this->phone)) {
            return null;
        }

        $phone = preg_replace('/\D/', '', $this->phone); // strip non-digits

        // Already in E.164 format (starts with +)
        if (str_starts_with($this->phone, '+')) {
            return '+' . $phone;
        }

        // Bangladeshi numbers: 01XXXXXXXXX  → +8801XXXXXXXXX
        // Adjust the country prefix below to match your user base
        if (strlen($phone) === 11 && str_starts_with($phone, '0')) {
            return '+88' . $phone; // BD prefix
        }

        // Already includes country code without +  (e.g. 8801XXXXXXXXX)
        return '+' . $phone;
    }

    /**
     * Relation with Assistant Director Permission
     */

    public function assistantDirectorPermissions(): HasMany
    {
        return $this->hasMany(
            AssistantDirectorPermission::class,
            'assistant_director_id'
        );
    }

    public function directorPermissions(): HasMany
    {
        return $this->hasMany(
            AssistantDirectorPermission::class,
            'director_id'
        );
    }

}
