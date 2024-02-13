<?
namespace Frizus\Module\Request\Helper;

class UploadedFile
{
    public $name;

    public $type;

    public $tmpName;

    public $error;

    public $size;

    public function __construct($name, $type, $tmpName, $error, $size)
    {
        $this->name = $name;
        $this->type = $type;
        $this->tmpName = $tmpName;
        $this->error = $error;
        $this->size = intval($size);
    }

    public function uploaded()
    {
        return $this->error === UPLOAD_ERR_OK;
    }

    public function getSize()
    {
        return $this->size;
    }

    public function getFileArray()
    {
        return [
            'name' => $this->name,
            'size' => $this->size,
            'tmp_name' => $this->tmpName,
            'type' => $this->type,
        ];
    }
}