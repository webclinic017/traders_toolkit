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

use App\Service\Exchange_Equities;
use App\Entity\InstrumentList;
use App\PriceHistory\PriceProviderInterface;
// use App\Entity\OHLCVHistory;

/**
 * Downloads latest prices of same interval for a list of instruments
 */
class ListSync
{
	/**
	 * @var PriceProviderInterface 
	 */
	private $priceProvider;

	/**
	 * @var string
	 */
	private $interval;

	/**
	 * @param PriceProviderIntervace $priceProvider
	 * @param string $interval
	 */
	public function __construct(PriceProviderInterface $priceProvider, $interval)
	{
		$this->priceProvider = $priceProvider;
		$this->interval = $interval;
		$this->em = $priceProvider->em;
	}

	public function sync(InstrumentList $instrumentList)
	{
		foreach ($instrumentList->getInstruments() as $instrument) {
			$this->syncInstrument($instrument);
		}
	}

	public function syncInstrument($instrument)
	{
		$fromDate = null;
		$toDate = null;
		$options = ['interval' => $this->interval];
		// last history price does not exist:
		if (!$history = $this->priceProvider->retrieveHistory($instrument, $fromDate, $toDate, $options)) {
			// download and add all history
			$history = $this->priceProvider->downloadHistory($instrument, $fromDate, $toDate, $options);
		}


		// last history price is older than PrevT: we have a gap
		// download and add history from last history price to today
			// continue if successful, otherwise we cannot fill the gap. Except.

		// last history price exists
			// last history price is PrevT? good, continue. If not, except, we do not have a price history after the two steps above.

	 	// download and add latest closing price (will be null if market is still open)
		// download and add latest quote (will be null if market is aready closed)

	}
}
