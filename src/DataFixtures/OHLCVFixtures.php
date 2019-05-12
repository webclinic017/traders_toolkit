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


class OHLCVFixtures extends Fixture
{
	const DIRECTORY = 'data/source/ohlcv';
    const SUFFIX_DAILY = '_d';
    const SUFFIX_WEEKLY = '_w';

    public function load(ObjectManager $manager)
    {
        $output = new ConsoleOutput();
        $output->getFormatter()->setStyle('info-init', new OutputFormatterStyle('white', 'blue'));
        $output->getFormatter()->setStyle('info-end', new OutputFormatterStyle('green', 'blue'));
        // $formatter = new FormatterHelper();

        $output->writeln('This seeder will read csv files stored in the ohlcv directory and if their symbol is already present in');
        $output->writeln('database instruments table (has been imported by the InstrumentFixtures seeder) will import the price history.');

        $output->writeln(sprintf('<info-init>Will import OHLCV daily and weekly price history from directory %s </>', self::DIRECTORY));
            
        // load daily
        $output->writeln('Looking for daily OHLCV price files...');
        $suffix = self::SUFFIX_DAILY.'.csv';

        $importedFiles = $this->importFiles($suffix, $output, $manager);    

        $output->writeln(sprintf('<info-end>Imported %d daily files</>', $importedFiles));

        // load weekly
        $suffix = self::SUFFIX_WEEKLY.'.csv';

        $importedFiles = $this->importFiles($suffix, $output, $manager);    

        $output->writeln(sprintf('<info-end>Imported %d weekly files</>', $importedFiles));

    }

    /**
     * Imports files from the defined data directory
     * @param string $suffix for the file name
     * @return integer number of files imported
     */
    
    private function importFiles($suffix, $output, $manager) 
    {
        // Build filemap by mask
        $finder = new Finder();
        $finder->in(self::DIRECTORY)->files()->name('*'.$suffix);
        $fileCount = $finder->count();
        $output->writeln(sprintf('Found %d files', $fileCount));

        $importedFiles = 0;
        // foreach file select the symbol
        foreach ($finder as $file) {
            $symbol = strtoupper($file->getBasename($suffix));
            $importedRecords = 0;

            if ($instrument = $this->getReference($symbol)) {
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

                    $manager->persist($OHLCVHistory);

                    $importedRecords++;
                }
                $manager->flush();
                $message = sprintf('%3d %s: imported %d of %d price records', $importedFiles, $file->getBasename(), $importedRecords, $lines->getReturn());
            } else {
                $message = sprintf('%s: instrument record was not imported, skipping file', $file->getBasename());
            }
            $output->writeln($message);
            $importedFiles++;
        }

        return $importedFiles;
    }

    /**
    * Generator
    * Skippes first line as header
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
}
