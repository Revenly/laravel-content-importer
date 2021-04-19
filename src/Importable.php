<?php
namespace R64\ContentImport;

use Illuminate\Database\Eloquent\Model;
use R64\ContentImport\Models\File;

abstract class Importable extends Model
{
    protected $guarded = [];

    public function scopeForFile($query, $id)
    {
        return $query->where('file_id', $id);
    }

    public function scopeOnDisk($query, $disk)
    {
        return $query->whereHas('file', fn($q) => $q->where('disk', $disk));
    }

    public function scopeUnprocessed($query)
    {
        return $query->whereNull('processed_at');
    }

    public function scopeProcessed($query)
    {
        return $query->whereNotNull('processed_at');
    }

    public function scopeProcessedBetween($query, $start, $end)
    {
        $query->whereBetween('processed_at', [$start, $end]);
    }

    public function file()
    {
        return $this->belongsTo(File::class);
    }
}
