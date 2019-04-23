<?php
namespace SgCsv;

/**
 * Basic abstract File Reader (to be extended)
 *
 * @author Marcus Welz <marcus.welz@stanleygibbons.com>
 * @package SgImporter\FileReader
 */
abstract class FileReader implements \SeekableIterator, \Countable
{
    /**
     * @var string
     */
    protected $filename;

    /**
     * @param string $filename
     */
    public function setFilename($filename)
    {
        $this->filename = $filename;
    }

    /**
     * @return string
     */
    public function getFilename()
    {
        return $this->filename;
    }

    /**
     * @return mixed
     */
    abstract public function getFieldNames();

    /**
     * @param null|string $filename
     */
    public function __construct($filename = null) {
        if ($filename) {
            $this->setFilename($filename);
        }
    }
}