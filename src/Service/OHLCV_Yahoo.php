<?php
/**
 * This file is part of the Trade Helper package.
 *
 * (c) Alex Kay <alex110504@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Service;

use App\PriceHistory\PriceProviderInterface;
use Scheb\YahooFinanceApi\ApiClient;
use Scheb\YahooFinanceApi\ApiClientFactory;
// use Scheb\YahooFinanceApi\Exception\ApiException;
use App\Entity\OHLCVHistory;
use App\Service\Exchange_Equities;
use App\Exception\PriceHistoryException;
use Symfony\Bridge\Doctrine\RegistryInterface;
use Scheb\YahooFinanceApi\Results\Quote;
use App\Entity\OHLCVQuote;
// use Doctrine\Common\Persistence\ManagerRegistry;
// use Doctrine\ORM\EntityManagerInterface;
// use App\Entity\Instrument;


class OHLCV_Yahoo implements PriceProviderInterface
{
	const PROVIDER_NAME = 'YAHOO';

	/**
	* Currently supported intervals from the Price Provider 
	* These follow interval_spec for the \DateInterval class
	*/
	public $intervals = [
		// 'PT1M',
		// 'PT2M',
		// 'PT3M',
		// 'PT3M',
		// 'PT6M',
		// 'PT10M',
		// 'PT15M',
		// 'PT30M',
		// 'PT60M',
		// 'PT120M',
		'P1D',
		'P1W',
		'P1M',
		// 'P1Y',
	]; 

	private $priceProvider;

	private $saveQuotePath;

	private $exchangeEquities;

	// private $em;

	private $doctrine;


	public function __construct(Exchange_Equities $exchangeEquities, RegistryInterface $registry)
	{
		$this->priceProvider = ApiClientFactory::createApiClient();
		$this->exchangeEquities = $exchangeEquities;
		// $this->em = $em;
		$this->doctrine = $registry;
	}

	/**
	 * @param array $options ['interval' => 'P1M|P1W|P1D' ]
	 */
	public function downloadHistory($instrument, $fromDate, $toDate, $options)
	{
		if ('test' == $_SERVER['APP_ENV'] && isset($_SERVER['TODAY'])) {
			$today = $_SERVER['TODAY'];
		} else {
			$today = date('Y-m-d');
		}
		// var_dump($today); exit();
		// see if $toDate is today
		if ($toDate->format('Y-m-d') == $today) {
			$toDate = $this->exchangeEquities->calcPreviousTradingDay($toDate);
		} elseif ($toDate->format('U') > strtotime($today)) {
			$toDate = $this->exchangeEquities->calcPreviousTradingDay(new \DateTime($today));
		}

		// test for exceptions:
		if ($toDate->format('Y-m-d') == $fromDate->format('Y-m-d')) {
			throw new PriceHistoryException(sprintf('$fromDate %s is equal to $toDate %s', $fromDate->format('Y-m-d'), $toDate->format('Y-m-d')));
		}
		if ($toDate->format('U') < $fromDate->format('U')) {
			throw new PriceHistoryException(sprintf('$toDate %s is earlier than $fromDate %s', $toDate->format('Y-m-d'), $fromDate->format('Y-m-d')));	
		}
		$hours = $toDate->diff($fromDate)->format('h'); // Hours, numeric
		// check if $toDate and $fromDate are on the same weekend, then except if ()
		if ($fromDate->format('w') == 6 && $toDate->format('w') == 0 && $hours <= 48 ) {
			throw new PriceHistoryException(sprintf('$fromDate %s and $toDate %s are on the same weekend.', $fromDate->format('Y-m-d'), $toDate->format('Y-m-d')));
		}
		// check if $toDate or $fromDate is a long weekend (includes a contiguous holiday, then except
		if ($hours <= 72 && ((!$this->exchangeEquities->isTradingDay($fromDate) && $toDate->format('w') == 0) || ($fromDate->format('w') == 6 && !$this->exchangeEquities->isTradingDay($toDate)) )) {
			throw new PriceHistoryException(sprintf('$fromDate %s and $toDate %s are on the same long weekend.', $fromDate->format('Y-m-d'), $toDate->format('Y-m-d')));
		}


		if (isset($options['interval']) && in_array($options['interval'], $this->intervals)) {
			switch ($options['interval']) {
				case 'P1M':
					$apiInterval = ApiClient::INTERVAL_1_MONTH;
					$interval = new \DateInterval('P1M');
					break;
				case 'P1W':
					$apiInterval = ApiClient::INTERVAL_1_WEEK;
					$interval = new \DateInterval('P1W');
					break;
				case 'P1D':
				default:
					$apiInterval = ApiClient::INTERVAL_1_DAY;
					$interval = new \DateInterval('P1D');
			}
		} else {
			$apiInterval = ApiClient::INTERVAL_1_DAY;
		}

		$history = $this->priceProvider->getHistoricalData($instrument->getSymbol(), $apiInterval, $fromDate, $toDate);
		// var_dump($history);
		array_walk($history, function(&$v, $k, $data) {
			$OHLCVHistory = new OHLCVHistory();
			$OHLCVHistory->setOpen($v->getOpen());
			$OHLCVHistory->setHigh($v->getHigh());
			$OHLCVHistory->setLow($v->getLow());
			$OHLCVHistory->setClose($v->getClose());
			$OHLCVHistory->setVolume($v->getVolume());
			$OHLCVHistory->setTimestamp($v->getDate());
			$OHLCVHistory->setInstrument($data[0]);
			$OHLCVHistory->setTimeinterval($data[1]);
			$OHLCVHistory->setProvider(self::PROVIDER_NAME);
			$v = $OHLCVHistory;
		}, [$instrument, $interval]);

		// make sure elements are ordered from oldest date to the latest
		$this->sortHistory($history);

		return $history;
	}

	public function addHistory($instrument, $history) {
		if (!empty($history)) {
			// delete existing OHLCV History for the given instrument from history start date to current date
			$OHLCVRepository = $this->doctrine->getRepository(OHLCVHistory::class);
			// var_dump(get_class($OHLCVRepository)); exit();
			$fromDate = $history[0]->getTimestamp();
			$interval = $history[0]->getTimeinterval();
			$OHLCVRepository->deleteHistory($instrument, $fromDate, null, $interval, self::PROVIDER_NAME);

			$em = $this->doctrine->getManager();

			// save the given history
			foreach ($history as $record) {
            	$em->persist($record);
        	}

        	$em->flush();
		}
	}
 
	/**
	 * @param array $options ['interval' => 'P1M|P1W|P1D' ]
	 */
 	public function exportHistory($history, $path, $options) {
 		throw new PriceHistoryException('exportHistory is not yet implemented.');
 	}
 
	/**
	 * @param array $options ['interval' => 'P1M|P1W|P1D' ]
	 */
 	public function retrieveHistory($instrument, $fromDate, $toDate, $options)
 	{
 		if (isset($options['interval']) && !in_array($options['interval'], $this->intervals)) {
 			throw new PriceHistoryException('Requested interval `%s` is not in array of serviced intervals.', $options['interval']);
 		} elseif (isset($options['interval'])) {
 			$interval = new \DateInterval($options['interval']);
 		} else {
 			$interval = new \DateInterval('P1D');
 		}

 		$OHLCVRepository = $this->doctrine->getRepository(OHLCVHistory::class);

 		return $OHLCVRepository->retrieveHistory($instrument, $fromDate, $toDate, $interval, self::PROVIDER_NAME);
 	}
 
 	public function downloadQuote($instrument) {
 		if ('test' == $_SERVER['APP_ENV'] && isset($_SERVER['TODAY'])) {
			$today = $_SERVER['TODAY'];
		} else {
			$today = date('Y-m-d H:i:s');
		}

		$dateTime = new \DateTime($today);
		// var_dump($dateTime); exit();
		if (!$this->exchangeEquities->isOpen($dateTime)) return null; 

		$providerQuote = $this->priceProvider->getQuote($instrument->getSymbol());
		$interval = new \DateInterval('P1D');

		if (!($providerQuote instanceof Quote)) throw new PriceHistoryException('Returned provider quote is not instance of Scheb\YahooFinanceApi\Results\Quote');

		$quote = new OHLCVQuote();

        $quote->setInstrument($instrument);
        $quote->setProvider(self::PROVIDER_NAME);
        $quote->setTimestamp($providerQuote->getRegularMarketTime());
        $quote->setTimeinterval($interval);
        $quote->setOpen($providerQuote->getRegularMarketOpen());
        $quote->setHigh($providerQuote->getRegularMarketDayHigh());
        $quote->setLow($providerQuote->getRegularMarketDayLow());
        $quote->setClose($providerQuote->getRegularMarketPrice());
        $quote->setVolume($providerQuote->getRegularMarketVolume());

        return $quote;
 	}
 
 	public function saveQuote($quote)
 	{
 		$instrument = $quote->getInstrument();
		$em = $this->doctrine->getManager();
		// Remove an existing quote. It is supposed to be replaced by new
		$OHLCVQuoteRepository = $em->getRepository(OHLCVQuote::class);

		if ($oldQuote = $OHLCVQuoteRepository->findOneBy(['instrument' => $instrument])) $em->remove($oldQuote);

		$em->persist($quote);

		$em->flush();
 	}
 
 	public function addQuoteToHistory($quote, $history) {}
 
	public function retrieveQuote($instrument)
	{
		// $this->doctrine->getRepository();
		return $instrument->getOHLCVQuotes();
	}

	public function downloadClosingPrice($instrument) {}

	private function sortHistory(&$history)
	{
		uasort($history, function($a, $b) {
		    if ($a->getTimestamp()->format('U') == $b->getTimestamp()->format('U')) {
    			return 0;
			}
			return ($a->getTimestamp()->format('U') < $b->getTimestamp()->format('U')) ? -1 : 1;
		});
	}
}
