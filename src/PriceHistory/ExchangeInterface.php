<?php

interface ExchangeInterface
{
	public function isTradingDay($date);

	public function isOpen($DateTime);

	public function getTradedInstruments();

	public function isTraded($instrument);

}
