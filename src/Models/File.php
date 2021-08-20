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

    public function scopeOnlyExtensions($query, $extensions = [])
    {
        return $query->where(function ($query) use ($extensions) {
            foreach ($extensions as $extension) {
                $query = $query->orWhere('url', 'like', '%' . $extension);
            }

            return $query;
        });
    }

    public function markAsProcessed()
    {
        return $this->update(['processed_at' => now()]);
    }

    public function content()
    {
        return $this->hasMany(ImportedContent::class);
    }

    public function extension(): string
    {
        return collect(explode('.', $this->url))->last();
    }
}
