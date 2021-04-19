<?php
namespace R64\ContentImport\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class File extends Model
{
    //use HasFactory;

    protected $fillable = ['url', 'disk', 'processed_at'];

    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }

    public function markAsProcessed()
    {
        return $this->update(['processed_at' => now()]);
    }

    public function content()
    {
        return $this->hasMany(ImportedContent::class);
    }
}
