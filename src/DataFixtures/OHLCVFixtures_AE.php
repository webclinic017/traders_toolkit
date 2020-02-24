<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\ConsoleOutput;
// use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
// use Symfony\Component\Console\Helper\FormatterHelper;
use App\Entity\OHLCVHistory;
use Symfony\Component\Finder\Finder;
use Doctrine\Bundle\FixturesBundle\FixtureGroupInterface;


class OHLCVFixtures_AE extends Fixture implements FixtureGroupInterface
{
	const DIRECTORY = 'data/source/ohlcv';
    const SUFFIX_DAILY = '_d';
    const SUFFIX_WEEKLY = '_w';

    private $manager;

    public static function getGroups(): array
    {
        return ['OHLCV'];
    }

    public function load(ObjectManager $manager)
    {
        $this->manager = $manager;
        $output = new ConsoleOutput();
        $output->getFormatter()->setStyle('info-init', new OutputFormatterStyle('white', 'blue'));
        $output->getFormatter()->setStyle('info-end', new OutputFormatterStyle('green', 'blue'));
        // $formatter = new FormatterHelper();
        $output->writeln(sprintf('<info-init>Will import OHLCV daily and weekly price history from directory %s </>', self::DIRECTORY));

        $output->writeln('This seeder will read csv files stored in the ohlcv directory and if their symbol is already present in');
        $output->writeln('database instruments table (has been imported by the InstrumentFixtures seeder) will import the price history.');
            
        // load daily
        $output->writeln('Looking for daily OHLCV price files...');
        $suffix = self::SUFFIX_DAILY.'.csv';

        $importedFiles = $this->importFiles($suffix, $output);

        $output->writeln(sprintf('<info-end>Imported %d daily files</>', $importedFiles));

        // load weekly
        $output->writeln('Looking for weekly OHLCV price files...');
        $suffix = self::SUFFIX_WEEKLY.'.csv';

        $importedFiles = $this->importFiles($suffix, $output);

        $output->writeln(sprintf('<info-end>Imported %d weekly files</>', $importedFiles));

    }

    /**
     * Imports csv files from the defined data directory.
     * Columns in files must follow strict order: Date, Open, High, Low, Close, Volume. Rest of the columns will be ignored.
     * File names must be similar to: AAPL_d.csv or ABX_w.csv
     * @param string $suffix for the file name, i.e. _d.csv
     * @param $output
     * @return integer number of files imported
     * @throws \Exception
     */
    
    private function importFiles($suffix, $output)
    {
        $repository = $this->manager->getRepository(\App\Entity\Instrument::class);
        // Build filemap by mask
        $finder = new Finder();
        // debug: will display how many files of each letter found 
//         foreach ($this->getLetter() as $letter) {
//             $finder = new Finder();
//             $pattern = sprintf('/^[%s][A-Z]*%s/', $letter, $suffix);
//             // var_dump($pattern); exit();
//             $count = $finder->in(self::DIRECTORY)->files()->name($pattern)->sortByName()->count();
//             $output->writeln(sprintf('%s: %d', $letter, $count));
//         }
//         exit();
        $finder->in(self::DIRECTORY)->files()->name('/^[A-E][A-Z]*'.$suffix.'/')->sortByName();
        $fileCount = $finder->count();
        $output->writeln(sprintf('Found %d files for letters A-E ending in %s', $fileCount, $suffix));

        $importedFiles = 0;
        // foreach file select the symbol
        foreach ($finder as $file) {
            if ($importedFiles >= 0) {
                $symbol = strtoupper($file->getBasename($suffix));
                $importedRecords = 0;

                // look for instrument without the seeders reference
                $instrument = $repository->findOneBy(['symbol' => $symbol]);
                // var_dump($instrument); exit();
                // if ($instrument = $this->getReference($symbol)) {
                if ($instrument) {
                    // if exists load
                    $fileName = $file->getPath().'/'.$file->getBasename();
                    $lines = $this->getLines($fileName);
                    foreach ($lines as $line) {
                        $fields = explode(',', $line);
                        // var_dump($fileName, $fields);
                        $OHLCVHistory = new OHLCVHistory();
                        // $OHLCVHistory->setTimestamp(strtotime($fields[0]));
                        $OHLCVHistory->setTimestamp(new \DateTime($fields[0]));
                        $OHLCVHistory->setOpen($fields[1]);
                        $OHLCVHistory->setHigh($fields[2]);
                        $OHLCVHistory->setLow($fields[3]);
                        $OHLCVHistory->setClose($fields[4]);
                        $OHLCVHistory->setVolume((int)$fields[5]);
                        $OHLCVHistory->setInstrument($instrument);
                        $OHLCVHistory->setTimeinterval(new \DateInterval('P1D'));

                        $this->manager->persist($OHLCVHistory);

                        $importedRecords++;
                    }
                    $this->manager->flush();
                    $importedFiles++;
                    $message = sprintf('%3d %s: imported %d of %d price records', $importedFiles, $file->getBasename(), $importedRecords, $lines->getReturn());
                } else {
                    $message = sprintf('%s: instrument record was not imported, skipping file', $file->getBasename());
                }
                $output->writeln($message);
            }
        }

        return $importedFiles;
    }

    /**
    * Generator
    * Skips first line as header
    * https://www.php.net/manual/en/language.generators.overview.php
    */
    private function getLines($file) {
    	$f = fopen($file, 'r');
        $counter = 0;
	    try {
	        while ($line = fgets($f)) {
	            // will skip first line as header
                if ($counter > 0) yield $line;
                $counter++;
	        }
	    } finally {
	        fclose($f);
	    }

        return $counter-1;
	}


    /**
     * Alphabet generator
     */
    private function getLetter()
    {
        $alphabet = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        for ($pos = 0; $pos <= 25; $pos++) {
            yield substr($alphabet, $pos, 1);
        }
    }
    
}
