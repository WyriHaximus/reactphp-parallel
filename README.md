# reactphp-parallel

[![Build Status](https://travis-ci.com/WyriHaximus/reactphp-parallel.png)](https://travis-ci.com/WyriHaximus/reactphp-parallel)
[![Latest Stable Version](https://poser.pugx.org/WyriHaximus/react-parallel/v/stable.png)](https://packagist.org/packages/WyriHaximus/react-parallel)
[![Total Downloads](https://poser.pugx.org/WyriHaximus/react-parallel/downloads.png)](https://packagist.org/packages/WyriHaximus/react-parallel)
[![License](https://poser.pugx.org/wyrihaximus/react-parallel/license.png)](https://packagist.org/packages/wyrihaximus/react-parallel)

ReactPHP bindings around ext-parallel

## Install ##

To install via [Composer](http://getcomposer.org/), use the command below, it will automatically detect the latest version and bind it with `~`.

```
composer require wyrihaximus/react-parallel 
```

## Pools

## Low level pools

Low level pools only deal with executing closures on the Runtimes (threads) that make out the pools. And as such there 
is only one low level pool and that is [`Infinite`](https://github.com/WyriHaximus/reactphp-parallel-infinite-pool), which will, as the name suggest, scale infinitely. While you can use 
such pools directly these are intended to be used by high level pools which have more control over what and how many 
things you run on these threads.

Low level pools have an additional feature where you can acquire a group lock that will prevent others from killing the 
pool. The idea behind low level pools is that hey are never used directly be always by encapsulating high level pool. 
Once all locks are released you can close/kill a low level pool.  

## License ##

Copyright 2019 [Cees-Jan Kiewiet](http://wyrihaximus.net/)

Permission is hereby granted, free of charge, to any person
obtaining a copy of this software and associated documentation
files (the "Software"), to deal in the Software without
restriction, including without limitation the rights to use,
copy, modify, merge, publish, distribute, sublicense, and/or sell
copies of the Software, and to permit persons to whom the
Software is furnished to do so, subject to the following
conditions:

The above copyright notice and this permission notice shall be
included in all copies or substantial portions of the Software.

THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND,
EXPRESS OR IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES
OF MERCHANTABILITY, FITNESS FOR A PARTICULAR PURPOSE AND
NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY,
WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING
FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR
OTHER DEALINGS IN THE SOFTWARE.
