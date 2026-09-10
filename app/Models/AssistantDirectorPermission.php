<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Director\Models\Camp;

class AssistantDirectorPermission extends Model
{
    use HasFactory;

    protected $fillable = [
        'director_id',
        'assistant_director_id',
        'camp_id',
        'build_schedule',
        'assign_referees',
        'publish_camp',
        'manage_ranking_reports',
        'manage_roster_referees',
        'manage_roster_evaluators',
        'manage_roster_crews',
        'manage_announcements',
    ];

    protected $casts = [
        'director_id' => 'integer',
        'assistant_director_id' => 'integer',
        'camp_id' => 'integer',

        'build_schedule' => 'boolean',
        'assign_referees' => 'boolean',
        'publish_camp' => 'boolean',
        'manage_ranking_reports' => 'boolean',
        'manage_roster_referees' => 'boolean',
        'manage_roster_evaluators' => 'boolean',
        'manage_roster_crews' => 'boolean',
        'manage_announcements' => 'boolean',
    ];

    protected $hidden = [
        'created_at',
        'updated_at',
    ];

    public function director(): BelongsTo
    {
        return $this->belongsTo(User::class, 'director_id');
    }

    public function assistantDirector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assistant_director_id');
    }

    public function camp(): BelongsTo
    {
        return $this->belongsTo(Camp::class);
    }

}
