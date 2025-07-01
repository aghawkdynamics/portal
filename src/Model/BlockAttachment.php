<?php
namespace App\Model;

use App\Core\Model;

class BlockAttachment extends Model
{
    protected string $table = 'block_attachment';

    /**
     * Get the URL of the attachment.
     *
     * @return string
     */
    public function getUrl(): string
    {
        $path = $this->get('path');
        if (empty($path)) {
            return '';
        }

        // Assuming the path is relative to a public directory
        return 'public/' . $path;
    }

    
    public function getName(): string
    {
        return basename($this->get('path', ''));
    }

    public function getComment(): string
    {
        return $this->get('comment') ?? '';
    }

}
