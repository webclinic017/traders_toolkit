<?php

/*
 * This file is part of the Trade Helper package.
 *
 * (c) Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace MGWebGroup\General;

use MGWebGroup\General\GeneralException;

/**
 * Implements basic data entity ops on a csv file:
 * Create, Read,   ReadAll, Insert, Delete, Update
 * @author Alex Kay <alex110504@gmail.com>
 */

class CSVManager implements \ArrayAccess, \SeekableIterator, \Countable
{
	/**
	 * @var array
	 */
	private $map;

	/**
	 * @var integer
	 */
	private $position;

	/**
	 * @var Resource
	 */
	private $resource = null;

	/**
	 * @var string 
	 */
	private $FQN;
	
	
	/**
	 * Will open existing file for reading or will create a blank file if does not exist
	 * @param string $FQN
	 */
	public function __construct($FQN)
	{

		if (!is_dir($dirName = pathinfo($FQN, PATHINFO_DIRNAME))) throw new GeneralException(sprintf('`%s` is not a directory', $dirName));

		// Open the file for reading and writing. If the file does not exist, it is created. If it exists, it is neither truncated, nor the call to this function fails. The file pointer is positioned on the beginning of the file.
		if (!$resource = @fopen($FQN, 'c+')) throw new GeneralException(sprintf('Failed to open resource at %s', $FQN));

		$this->FQN = realpath($FQN);

		$this->position = 0;
		$this->resource = $resource;

		$this->map = $this->createMap();
	}

	public function getResource()
	{
		return $this->resource;
	}

	public function getMap()
	{
		return $this->map;
	}

	/**
	 * Creates Map for the file under resource. The map contains cumulative lenthgs of each string in each element
	 * @return array map or empty array if file is empty
	 */
	private function createMap()
	{
		$this->map = [];
		$cumulativeLength = 0;

		while ($line = fgets($this->resource)) {
			$cumulativeLength += strlen($line);
			$this->map[] = $cumulativeLength;
		}

		return $this->map;
	}

	/**
	* Tuncates open resource to 0 length and writes data with given headers as a csv file. 
	* Will create indexed map on the fly without calling createMap() method.
	* @param array $data 
	* @param array $headers
	* @return array map of file (<line_number> => <cumulative_length>, ...)
	*/
	public function importData($data, $headers = [])
	{
		ftruncate($this->resource, 0);
		rewind($this->resource);

		$this->map = [];
		$cumulativeLength = 0;

		if (!empty($headers)) {
			$cumulativeLength += fwrite($this->resource, implode(',', $headers).PHP_EOL);
			$this->map[] = $cumulativeLength;
		}

		foreach ($data as $fields) {
			$cumulativeLength += fwrite($this->resource, implode(',', $fields).PHP_EOL);
			$this->map[] = $cumulativeLength;
		}

		return $this->map;
	}


	/* Methods from ArrayAccess */
	/**
	 * @param integer $position
	 * @return bool
	 */
	public function offsetExists($position)
	{
		return isset($this->map[$position]);
	}

	/**
	 * @param integer $position
	 * @return array | null
	 */
	public function offsetGet($position)
	{
		if (isset($this->map[$position])) {
			// $length = ($position > 0)? $this->map[$position] - $this->map[$position-1] : $this->map[0];
			$offset = ($position > 0)? $this->map[$position-1] : $this->map[0];
			if (fseek($this->resource, $offset) < 0) throw new GeneralException(sprintf('Failed to seek to offset %s in %s', $offset, $this->FQN));
			return fgetcsv($this->resource);
		} else {
			return null;
		}
	}

	/**
	 * To add a value, use CSVMgrObj[] = $value
	 * To modifiy existing value, use CSVMgrObj[$index] = $value
	 * @param integer $position
	 * @param array $data
	 * @return void
	 */
	public function offsetSet($position, $data)
	{
		// make sure number of elements in $data matches number of headings
		// ...

	    $lastPosition = count($this->map) - 1;

		if (is_null($position)) {
			$offset = ($lastPosition <= 0)? 0 : $this->map[$lastPosition];

			if (fseek($this->resource, $offset) < 0) throw new GeneralException(sprintf('Failed to seek to offset %s in %s', $offset, $this->FQN));

			if ($bytes = fwrite($this->resource, implode(',', $data).PHP_EOL)) {
				$this->map[$lastPosition+1] = $offset + $bytes;
			}
        } else {
            // read in or save in temp file raw data from the csv file past the modified position
        	$offset = $this->map[$position];

        	if (fseek($this->resource, $offset) < 0) throw new GeneralException(sprintf('Failed to seek to offset %s in %s', $offset, $this->FQN));
        	
        	$lenthToBuffer = $this->map[$lastPosition] - $this->map[$position];
        	$buffer = fread($this->resource, $lenthToBuffer);
        	// var_dump($buffer); exit();
            // write to the file new data at requested position, modify cumulative length in map, note the written length
        	$previousOffset = ($position <= 0)? 0 : $this->map[$position-1];
        	$oldLength = $offset - $previousOffset;
        	// var_dump($oldLength); exit();
        	if (fseek($this->resource, $previousOffset) < 0) throw new GeneralException(sprintf('Failed to seek to offset %s in %s', $previousOffset, $this->FQN));
        	$newLength = fwrite($this->resource, implode(',', $data).PHP_EOL);

        	$delta = $newLength - $oldLength;
        	// var_dump($delta); exit();
            // for all the remaining indexes in the map add the written length
            $remainingMap = array_slice($this->map, $position);
            array_walk($remainingMap, function(&$v, $k, $param) { $v += $param[0]; }, [$delta]);
            var_dump($remainingMap);
        	$this->map = array_splice($this->map, $position, count($this->map), $remainingMap);
        	var_dump($this->map); exit();
        	// write to the file raw data that was saved
        	fwrite($this->resource, $buffer);
        }
	}

	/**
	 * @param integer $position
	 * @return void
	 */
	public function offsetUnset($position)
	{
		if ($position < 0) throw new GeneralException(sprintf('Value of the position (offset) for deletion must be greater than 0.'));

		if (!isset($this->map[$position])) throw new GeneralException(sprintf('Out of bounds delete position (%d)', $position));

		//...
	}


	/* Methods from Countable  */
	public function count()
	{
		return count($this->map);
	}
	

	/* Methods from SeekableIterator*/
	public function seek($position)
	{
		if (!isset($this->map[$position])) throw new GeneralException(sprintf('Invalid seek position (%d)', $position));

		$this->position = $position;		
	}

	/* Inherited methods from Iterator*/
	public function current()
	{
		$offset = ($this->position <= 0)? 0 : $this->map[$this->position - 1];

		if (fseek($this->resource, $offset) < 0) throw new GeneralException(sprintf('Failed to seek to offset %s in %s', $offset, $this->FQN));

		return fgetcsv($this->resource);
	}

	public function key()
	{
		return $this->position;
	}

	public function next()
	{
		++$this->position;
	}

	public function rewind()
	{
		$this->position = 0;
	}

	public function valid()
	{
		return isset($this->map[$this->position]);
	}

}