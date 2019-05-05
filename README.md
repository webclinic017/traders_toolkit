# traders_toolkit
-----------------
## Version 2

Supports several market data providers and has historical price data organized into data entities. Data entities are saved on disk as csv or mysqli, or Redis files.

#### Theory:
Two main concepts: Price History and Latest Quote.
Price History is initially saved as a stream of actual ticks (tape) from the market data provider. It subsequently can be transformed into daily, weekly, monthly, etc. formats.
At this time, I don't have a provider of ticks and can only obtain daily quotes.



Price History Manager

API Manager

Price History Transformer 
Transforms given Price History into 

### Installation

1. Install Vendor dependancies:
```bash
$> composer install
```


### Miscellaneous:

Various stock market price analysis tools. Includes set of calculator and statistical functions grouped into classes.

Essential classes:
* calc1.class.php - Statistical and mathematical functions
* customexception.class.php - Class for handling exceptions and outputting custom error pages
* csv1.class.php
* csv2.class.php - Classes for downloading comma-seprated price data for the daily open, high, low, close and volume
* chart.class.php - Class that creates graphical files with price charts and studies

Detailed description of the following price study classes can be found on my blog: www.tradehelperonline.com
Price study classes:
* bbd4.class.php - ...
* bbd5.class.php
* lin_reg-atr.class.php - ...
* lin_reg_slope2.class.php - ...
* lin_reg_slope3.class.php
* lrs_ext.class.php - ...
* lrs_ext1.class.php

