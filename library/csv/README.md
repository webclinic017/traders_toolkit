# Stanley Gibbons CSV Reader
This is a simple implementation of a CSV file reader which adds support for seeking into a file quickly. This enables us to process large CSV files in reasonable amounts of time.

## Installation
Install using composer
`composer require sgmarketplace/csv`

## Usage
Simply instantiate the CsvMappedReader class and seek away
```
require 'vendor/autoload.php';

$csv = new \SgCsv\CsvMappedReader('demo.csv');
```

The FileReader implements the `\SeekableIterator` and `\Countable` PHP Interfaces so you can easily iterate via foreach.
Each entry is returned as an associative array of file column headings mapped to line values.
```
// Print all values in file from the column with heading 'someheading'
foreach ($csv as $line) {
    echo $line['someheading']."\n";
}
```