# PSR-7 Adaptor for Icicle

Adapts the asynchronous HTTP messages and streams of Icicle's [HTTP component](https://github.com/icicleio/Http) to [PSR-7](http://www.php-fig.org/psr/psr-7/) compatible interface. Use with caution and only in cases where blocking is acceptable, as reading or writing from a synchronous stream can block the entire process.

Currently under development.
