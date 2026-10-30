<?php

namespace App\Models;

use October\Rain\Database\Attach\File as FileBase;

/**
 * File attachment model.
 *
 * Stores uploads on the public disk under storage/app/public/uploads, served
 * through the public/storage link.
 */
class File extends FileBase
{
    /**
     * Get the public address of the upload folder.
     */
    public function getPublicPath()
    {
        // asset() trims the trailing slash the partition path needs
        return asset('storage/uploads/'.($this->isPublic() ? 'public' : 'protected')).'/';
    }

    /**
     * Get the local folder that holds the uploads.
     */
    protected function getLocalRootPath()
    {
        return storage_path('app/public/uploads');
    }
}
