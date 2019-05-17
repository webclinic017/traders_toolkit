<?php

namespace App\DataFixtures;

use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\Persistence\ObjectManager;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Helper\FormatterHelper;
use App\Entity\Instrument;
// use Symfony\Component\Console\Style\SymfonyStyle;


class InstrumentFixtures extends Fixture
{
	const FILE = 'data/source/y_universe.csv';


    public function load(ObjectManager $manager)
    {
        // $reflection = new \ReflectionClass(Fixture::class);
        // var_dump($reflection->getMethods()); exit();
        // var_dump($reflection->getProperties()); exit();

        $output = new ConsoleOutput();
        // output verbosity is not readable from thie method.

        // var_dump($output->getVerbosity()); exit();
        $output->getFormatter()->setStyle('info-init', new OutputFormatterStyle('white', 'blue'));
        $output->getFormatter()->setStyle('info-end', new OutputFormatterStyle('green', 'blue'));
        // $formatter = new FormatterHelper();
        // $output->writeln('<fg=yellow>Symbol</>,<fg=yellow>Name</>,Weight,<fg=yellow>Industry</>,Shares Held,SPDR Fund,Beta,E1,E2,E3,E4'); exit();
        $output->writeln(sprintf('<info-init>Will import list of instruments from %s file</>', self::FILE));

        $output->writeln('The seeder uses aggregate file which holds all instruments on which this app operates.');
        $output->writeln('The file must be saved in data/source/y_universe.csv with the following headers:');
        $output->writeln('<fg=yellow>Symbol</>,<fg=yellow>Name</>,Weight,<fg=yellow>Industry</>,Shares Held,SPDR Fund,Beta,E1,E2,E3,E4');
        $output->writeln('Headers that must be present are highlighted in <fg=yellow>color</>. Count of columns ');
        $output->writeln('is important, however you can skip columns that are beyond the required ones, i.e. Shares Held, etc.');
            
        $numberOfLines = 0;
        $lines = $this->getLines(self::FILE);

    	foreach ($lines as $line) {
            $fields = explode(',', $line);
        	$instrument = new Instrument();
            $symbol = strtoupper($fields[0]);
        	$instrument->setSymbol($symbol);
        	$instrument->setName($fields[1]);
        	$manager->persist($instrument);

            // $this->addReference($symbol, $instrument);

            $output->writeln(sprintf('Imported symbol=%s', $symbol));
    	}

        $manager->flush();

        $numberOfLines = $lines->getReturn(); 
        if ($numberOfLines > 0 ) { 
            $message = sprintf('<info-end>Imported %d symbols into Instruments table.</>', $numberOfLines);
        } else {
            $message = sprintf('<info-end>No records found in the %s</>', self::FILE);
        }

        $output->writeln($message.PHP_EOL);
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
}
