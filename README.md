# SARS Component

_Copyright (c) 2021-current Bit 64 Solutions Pty Ltd ([bit64.co](https://bit64.co))_

This component provides a tax calculator microservice specific to the South African Revenue Services (SARS) for PHP projects.

---

## Usage

Installation via Composer
```sh
$ composer require bit64/sars
```

Instantiate the API class
```php
use Bit64\SARS\Api;

$api = new Api();

```

#### Income Tax Calculator

Get the income tax calculator
```php
$incomeTax = $api->IncomeTax();
```

Default age is 30 years old
```php

$grossMonthly = 25000;

$paye = $incomeTax->calculateMonthlyTax($grossMonthly);

echo sprintf('R%0.2f', $paye);
// Output R3749.17

```

Specify age and context date
```php

$grossWeekly = 12000;
$age = 68;
$contextDate = '2021-05-30';

$paye = $incomeTax->calculateWeeklyTax($grossWeekly, $age, $contextDate);

echo sprintf('R%0.2f', $paye);
// Output R2751.23

```
