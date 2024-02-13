<?
namespace Frizus\Module\Request\Helper;

class UploadedFiles
{
    /**
     * @var UploadedFile[]
     */
    public $files;

    public function __construct($files)
    {
        $this->files = [];

        foreach ($files as $file) {
            $this->files[] = new UploadedFile(...array_values($file));
        }
    }

    public function count()
    {
        return count($this->files);
    }

    public function uploaded()
    {
        foreach ($this->files as $file) {
            if (!$file->uploaded()) {
                return false;
            }
        }

        return true;
    }

    public function getSizes()
    {
        $sizes = [];
        foreach ($this->files as $file) {
            $sizes[] = $file->getSize();
        }

        return $sizes;
    }
}