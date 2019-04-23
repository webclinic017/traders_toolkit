<?php
namespace SgCsv;

/**
 * Mapped reader to support seek operations
 *
 * @author Marcus Welz <marcus.welz@stanleygibbons.com>
 * @author Casey Delorme <cdelorme@stanleygibbons.com>
 * @package SgImporter\FileReader
 */
class CsvMappedReader extends CsvReader
{

    /**
     * @var array The map used for seeking
     */
    protected $seekMap;

    /**
     * @param array $seekMap
     */
    public function setSeekMap($seekMap)
    {
        $this->seekMap = $seekMap;
    }

    /**
     * @return array
     */
    public function getSeekMap()
    {
        return $this->seekMap;
    }

    /**
     * Seek using the "seek map"
     *
     * This will skip to a stored position, readying us to read the next line.
     *
     * @param int $position The position to seek to.
     *
     * @throws \OutOfBoundsException If seeking beyond the position
     */
    protected function seekUsingMap($position)
    {

        // start at the very first record
        $bestPosition = 0;

        foreach ($this->seekMap as $recordNum => $fileOffset) {

            // stop if we would overshoot
            if ($recordNum > $position) {
                break;
            }

            // set the best starting position to the next entry
            $bestPosition = $fileOffset;
        }

        // prepare seek and offset so we know where in the file we're at
        $this->currentRowOffset = array_search($bestPosition, $this->seekMap);
        fseek($this->fileHandle, $bestPosition);
        $this->next(); // load it
    }

    /**
     * (PHP 5 &gt;= 5.1.0)<br/>
     * Seeks to a position
     * @link  http://php.net/manual/en/seekableiterator.seek.php
     *
     * @param int $position The position to seek to.
     *
     * @throws \OutOfBoundsException If seeking beyond the position
     */
    public function seek($position)
    {
        if ($this->seekMap) {
            $this->seekUsingMap($position);
        }

        parent::seek($position);
    }

    /**
     * Build the "seek map"
     *
     * This will iterate over the records in the file and at every interval (chunkSize) record what the file position
     * is, so that when we seek a lot into the file, it'll be faster.
     *
     * @param integer $chunkSize mesured in number of lines (records) not bytes
     *
     * @return array
     */
    public function buildSeekMap($chunkSize)
    {
        $oldFilePosition = ftell($this->fileHandle);

        $this->rewind();

        $this->seekMap[0] = $this->currentRowFilePos;

        while ($this->valid()) {

            $counter = 0;

            while ($counter < $chunkSize and $this->valid()) {
                $this->next();
                $counter++;
            }

            if ($counter >= $chunkSize) {
                $this->seekMap[$this->key()] = $this->currentRowFilePos;
            }
        }

        fseek($this->fileHandle, $oldFilePosition);

        return $this->seekMap;
    }
}