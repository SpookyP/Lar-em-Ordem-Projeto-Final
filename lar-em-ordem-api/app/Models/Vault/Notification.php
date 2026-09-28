<?php

namespace App\Models\Vault;

use App\Models\Property\Property;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    protected $table = 'vault_notifications';

    protected $fillable = [
        'property_id',
        'document_id',
        'type',
        'title',
        'message',
        'alert_date',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'alert_date' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    public function property(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    public function document(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Document::class);
    }
}
