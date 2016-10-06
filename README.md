# chubbyphp-csrf

[![Build Status](https://api.travis-ci.org/chubbyphp/chubbyphp-csrf.png?branch=master)](https://travis-ci.org/chubbyphp/chubbyphp-csrf)
[![Total Downloads](https://poser.pugx.org/chubbyphp/chubbyphp-csrf/downloads.png)](https://packagist.org/packages/chubbyphp/chubbyphp-csrf)
[![Latest Stable Version](https://poser.pugx.org/chubbyphp/chubbyphp-csrf/v/stable.png)](https://packagist.org/packages/chubbyphp/chubbyphp-csrf)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/chubbyphp/chubbyphp-csrf/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/chubbyphp/chubbyphp-csrf/?branch=master)

## Description

A simple csrf solution based on [chubbyphp/chubbyphp-session][2].

## Requirements

 * php: ~7.0
 * chubbyphp/chubbyphp-error-handler: ~1.0
 * chubbyphp/chubbyphp-session: ~1.0

## Suggest

 * pimple/pimple: ~3.0

## Installation

Through [Composer](http://getcomposer.org) as [chubbyphp/chubbyphp-csrf][1].

## Usage

### CsrfMiddleware

```{.php}
<?php

$app = ...

// sample for slim
$app->add($container['session.middleware']);

```

### CsrfProvider (Pimple)

```{.php}
<?php

namespace Chubbyphp\Csrf\CsrfProvider;
namespace Chubbyphp\Csrf\SessionProvider;
namespace Pimple\Container;

$container = new Container();
$container->register(new CsrfProvider());
$container->register(new SessionProvider());

```

[1]: https://packagist.org/packages/chubbyphp/chubbyphp-csrf
[2]: https://github.com/chubbyphp/chubbyphp-session

## Copyright

Dominik Zogg 2016
