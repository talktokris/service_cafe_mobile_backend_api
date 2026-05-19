<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FoodCategory extends Model
{
    protected $fillable = [
        'headOfficeId',
        'branchId',
        'createUserId',
        'name',
        'activeStatus',
        'deleteStatus',
    ];

    protected $casts = [
        'activeStatus' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function headOffice(): BelongsTo
    {
        return $this->belongsTo(OfficeProfile::class, 'headOfficeId');
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(OfficeProfile::class, 'branchId');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'createUserId');
    }

    public function scopeActive($query)
    {
        return $query->where('activeStatus', 1);
    }

    public function scopeNotDeleted($query)
    {
        return $query->where('deleteStatus', 0);
    }
}
