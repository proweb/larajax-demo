<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Http\File as LocalFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use October\Contracts\Database\AttachmentInterface;
use October\Contracts\Database\SortableInterface;

/**
 * File attachment model.
 *
 * Stores uploads on the public disk under storage/app/public/uploads, served
 * through the public/storage link. Attached to a record through the morph
 * relation that names the field it belongs to.
 */
class File extends Model implements AttachmentInterface, SortableInterface
{
    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'is_public' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * attachment is the record the file belongs to, empty while an upload waits for its record to save.
     */
    public function attachment(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * fromPost stores an uploaded file, in the protected folder when the relation made the file private.
     */
    public function fromPost($fileInput): static
    {
        return $this->fromFile($fileInput->getRealPath(), $fileInput->getClientOriginalName());
    }

    /**
     * fromFile stores a local file under a unique disk name and fills in its name, size and type.
     */
    public function fromFile(string $path, ?string $fileName = null): static
    {
        $file = new LocalFile($path);

        $this->file_name = $fileName ?? $file->getFilename();
        $this->file_size = $file->getSize();
        $this->content_type = $file->getMimeType() ?? 'application/octet-stream';
        $this->disk_name = Str::random(40).'.'.strtolower(pathinfo($this->file_name, PATHINFO_EXTENSION));
        $this->is_public ??= true;

        $this->getStorageDisk()->putFileAs($this->getStorageFolder(), $file, $this->disk_name);

        return $this;
    }

    /**
     * getPath returns the public address of the file, or of another file in its folder.
     */
    public function getPath($fileName = null): string
    {
        return $this->getStorageDisk()->url($this->getStorageFolder().'/'.($fileName ?? $this->disk_name));
    }

    /**
     * getThumb returns the address of the file itself, since the demo has no image resizer to make a thumbnail.
     */
    public function getThumb($width, $height, $options = []): string
    {
        return $this->getPath();
    }

    /**
     * getMaxFilesize returns the largest upload PHP accepts, in kilobytes.
     */
    public static function getMaxFilesize(): float
    {
        return UploadedFile::getMaxFilesize() / 1024;
    }

    /**
     * getDiskPath returns where the file lives on the public disk, under uploads/public or uploads/protected.
     */
    public function getDiskPath(): string
    {
        return $this->getStorageFolder().'/'.$this->disk_name;
    }

    /**
     * getStorageFolder returns the upload folder for the file, which depends on whether it is public.
     */
    protected function getStorageFolder(): string
    {
        return 'uploads/'.($this->is_public ? 'public' : 'protected');
    }

    /**
     * getStorageDisk returns the disk that holds the uploads, served through the public/storage link.
     */
    protected function getStorageDisk(): FilesystemAdapter
    {
        return Storage::disk('public');
    }

    /**
     * getSortOrderColumn names the column that orders the files of a field.
     */
    public function getSortOrderColumn(): string
    {
        return 'sort_order';
    }

    /**
     * setSortableOrder stores a new order for the given files, numbered from one unless the orders are supplied.
     *
     * @param  array<int, int|string>  $itemIds
     * @param  array<int, int>|bool|null  $itemOrders
     */
    public function setSortableOrder($itemIds, $itemOrders = null): void
    {
        if (!is_array($itemIds)) {
            return;
        }

        $itemIds = array_values($itemIds);
        $orders = is_array($itemOrders) && count($itemOrders) === count($itemIds)
            ? array_values($itemOrders)
            : range(1, count($itemIds));

        foreach ($itemIds as $index => $id) {
            static::query()->whereKey($id)->update(['sort_order' => $orders[$index]]);
        }
    }

    /**
     * booted places a new file after the others and removes the stored file once its record is deleted.
     */
    protected static function booted(): void
    {
        static::creating(function (File $file) {
            $file->sort_order ??= (int) static::query()->max('sort_order') + 1;
        });

        static::deleted(function (File $file) {
            $file->getStorageDisk()->delete($file->getDiskPath());
        });
    }
}
