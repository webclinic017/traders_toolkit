<?php
namespace SgCsv;

/**
 * Reads CSV Files
 *
 * @author Marcus Welz <marcus.welz@stanleygibbons.com>
 * @author Casey Delorme <cdelorme@stanleygibbons.com>
 * @package SgImporter\FileReader
 * @link http://en.wikipedia.org/wiki/Comma-separated_values
 */
class CsvReader extends FileReader
{

    /**
     * Mode parameter for fopen operation
     * @var string
     */
    protected $fileMode = 'rb';

    /**
     * use_include_path parameter for fopen operation
     * @var mixed
     */
    protected $fileIncludePath = false;

    /**
     * file stream context for fopen operation
     * @var resource
     */
    protected $fileContext = null;

    /**
     * @var resource The file handle to the open file
     */
    protected $fileHandle;

    /**
     * @var array Array of column names (taken from the first record in the csv)
     */
    protected $fieldNames;

    /**
     * @var string The string used to enclosure CSV data
     */
    protected $enclosure = '"';

    /**
     * @var string The string used to delimit CSV data
     */
    protected $delimiter = ',';

    /**
     * @var string The string used to escape the enclosing character if used as part of a value
     */
    protected $escape = "\\";

    /**
     * @var int Record Count
     */
    protected $count;

    /**
     * @var int The current record (0 indexed, ignoring first row for fieldnames)
     */
    protected $currentRowOffset;

    /**
     * @var array contains the current record set
     */
    protected $currentRowData;

    /**
     * @var int Position in the file for this particular record
     */
    protected $currentRowFilePos;

    /**
     * Allow a bit more control over how the file is opened
     * @param null $filename
     * @param null $fileMode
     * @param null $fileInclude
     * @param null $fileContext
     */
    function __construct($filename = null, $fileMode = null, $fileInclude = null, $fileContext = null)
    {
        if ($fileMode !== null) {
            $this->fileMode = $fileMode;
        }
        if ($fileInclude !== null) {
            $this->fileIncludePath = $fileInclude;
        }
        $this->fileContext = $fileContext === null ? stream_context_create() : $fileContext;
        parent::__construct($filename);
    }


    /**
     * @param string $delimiter
     */
    public function setDelimiter($delimiter)
    {
        $this->delimiter = $delimiter;
    }

    /**
     * @return string
     */
    public function getDelimiter()
    {
        return $this->delimiter;
    }

    /**
     * @param string $enclosure
     */
    public function setEnclosure($enclosure)
    {
        $this->enclosure = $enclosure;
    }

    /**
     * @return string
     */
    public function getEnclosure()
    {
        return $this->enclosure;
    }

    /**
     * @param string $escape
     */
    public function setEscape($escape)
    {
        $this->escape = $escape;
    }

    /**
     * @return string
     */
    public function getEscape()
    {
        return $this->escape;
    }

    /**
     * @param string $filename
     *
     * Set the filename and also open the file
     * _links to `setFileName()`_
     *
     * @throws \Exception
     */
    public function open($filename)
    {
        $this->setFileName($filename);
    }

    /**
     * @param string $filename
     *
     * Set the filename and also open the file
     *
     * @todo Perhaps open the file on-demand during read operations?
     *
     * @throws \Exception
     */
    public function setFilename($filename)
    {
        parent::setFilename($filename);

        try {
            $this->fileHandle = fopen($filename, $this->fileMode, $this->fileIncludePath, $this->fileContext);
        } catch (\Exception $e) {
            throw new \Exception('Could not open $filename for reading', $e->getCode(), $e);
        }

        $this->rewind();
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the current element
     * @link http://php.net/manual/en/iterator.current.php
     * @return mixed Can return any type.
     */
    public function current()
    {
        if (count($this->getFieldNames()) != count($this->currentRowData)) {
            return array_fill_keys($this->getFieldNames(), null);
        }
        return array_combine($this->getFieldNames(), $this->currentRowData);
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Move forward to next element
     * @link http://php.net/manual/en/iterator.next.php
     * @return void Any returned value is ignored.
     */
    public function next()
    {
        $this->currentRowFilePos = ftell($this->fileHandle);
        $this->currentRowData = fgetcsv($this->fileHandle, null, $this->delimiter, $this->enclosure, $this->escape);
        $this->currentRowOffset++;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Return the key of the current element
     * @link http://php.net/manual/en/iterator.key.php
     * @return mixed scalar on success, or null on failure.
     */
    public function key()
    {
        return $this->currentRowOffset;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Checks if current position is valid
     * @link http://php.net/manual/en/iterator.valid.php
     * @return boolean The return value will be casted to boolean and then evaluated.
     *       Returns true on success or false on failure.
     */
    public function valid()
    {
        return !feof($this->fileHandle) and (bool) $this->currentRowData;
    }

    /**
     * (PHP 5 &gt;= 5.0.0)<br/>
     * Rewind the Iterator to the first element
     * @link http://php.net/manual/en/iterator.rewind.php
     * @return void Any returned value is ignored.
     */
    public function rewind()
    {
        rewind($this->fileHandle);

        // skip headers (first-line)
        fgets($this->fileHandle);

        $this->currentRowFilePos = ftell($this->fileHandle);
        $this->currentRowData = fgetcsv($this->fileHandle, null, $this->delimiter, $this->enclosure, $this->escape);
        $this->currentRowOffset = 0;
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Seeks to a position
     * @link  http://php.net/manual/en/seekableiterator.seek.php
     *
     * @param int $position <p>
     *                      The position to seek to.
     * </p>
     *
     * @throws \OutOfBoundsException If seeking beyond the position
     * @return void
     */
    public function seek($position)
    {

        // we're already past it.
        if ($this->key() > $position) {
            $this->rewind();
        }

        // score
        if ($this->key() == $position) {
            return;
        }

        while ($this->valid() and ($this->key() < $position)) {
            $this->next();
        }

        if (!$this->valid()) {
            throw new \OutOfBoundsException("invalid seek position ($position)");
        }
    }

    /**
     * We need to count the records in the file
     *
     * @return int
     */
    public function count()
    {
        if ($this->count === null) {

            // reserve position to reset on completion
            $oldFilePosition = ftell($this->fileHandle);

            // get a count
            $count = 0;
            $this->rewind();
            while (!feof($this->fileHandle)) {
                fgetcsv($this->fileHandle, null, $this->delimiter, $this->enclosure, $this->escape);
                $count++;
            }
            $this->count = $count;

            // restore position
            fseek($this->fileHandle, $oldFilePosition);
        }

        return $this->count;
    }

    /**
     * Get the field names as a key-value map
     *
     * @return array
     */
    public function getFieldNames()
    {
        if (!$this->fieldNames) {

            $oldFilePosition = ftell($this->fileHandle);
            rewind($this->fileHandle);

            // Acquire field names
            $this->fieldNames = fgetcsv($this->fileHandle, null, $this->delimiter, $this->enclosure, $this->escape);

            // restore position
            fseek($this->fileHandle, $oldFilePosition);
        }

        return $this->fieldNames;
    }

    /**
     * Link to getFieldNames
     */
    public function headers()
    {
        return $this->getFieldNames();
    }

    /**
     * @return string
     */
    public function getFileMode()
    {
        return $this->fileMode;
    }

    /**
     * @param string $fileMode
     * @return $this
     */
    public function setFileMode($fileMode)
    {
        $this->fileMode = $fileMode;
        return $this;
    }

    /**
     * @return mixed
     */
    public function getFileIncludePath()
    {
        return $this->fileIncludePath;
    }

    /**
     * @param mixed $fileIncludePath
     * @return $this
     */
    public function setFileIncludePath($fileIncludePath)
    {
        $this->fileIncludePath = $fileIncludePath;
        return $this;
    }

    /**
     * @return resource
     */
    public function getFileContext()
    {
        return $this->fileContext;
    }

    /**
     * @param resource $fileContext
     * @return $this
     */
    public function setFileContext($fileContext)
    {
        $this->fileContext = $fileContext;
        return $this;
    }

}