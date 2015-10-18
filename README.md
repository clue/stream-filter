# clue/stream-filter [![Build Status](https://travis-ci.org/clue/php-stream-filter.svg?branch=master)](https://travis-ci.org/clue/php-stream-filter)

A simple and modern approach to stream filtering in PHP

## Usage

This lightweight library consists only of a few simple functions.
All functions reside under the `Clue\StreamFilter` namespace.

The below examples assume you use an import statement similar to this:

```php
use Clue\StreamFilter as Filter;

Filter\append(…);
```

Alternatively, you can also refer to them with their fully-qualified name:

```php
\Clue\StreamFilter\append(…);
```

### append()

The `append($stream, $callback, $read_write = STREAM_FILTER_ALL)` function can be used to
append a filter callback to the given stream.

Each stream can have a list of filters attached.
This function appends a filter to the end of this list.

This function returns a filter resource which can be passed to [`remove()`](#remove).
If the given filter can not be added, it throws an `Exception`.

The `$stream` can be any valid stream resource, such as:

```php
$stream = fopen('demo.txt', 'w+');
```

The `$callback` should be a valid callable function which accepts an individual chunk of data
and should return the updated chunk:

```php
$filter = Filter\append($stream, function ($chunk) {
    // will be called each time you read or write a $chunk to/from the stream
    return $chunk;
});
```

As such, you can also use native PHP functions or any other `callable`:

```php
Filter\append($stream, 'strtoupper');

// will write "HELLO" to the underlying stream
fwrite($stream, 'hello');
```

The optional `$read_write` parameter can be used to only invoke the `$callback` when either writing to the stream or only when reading from the stream:

```php
Filter\append($stream, function ($chunk) {
    // will be called each time you write to the stream
    return $chunk;
}, STREAM_FILTER_WRITE);

Filter\append($stream, function ($chunk) {
    // will be called each time you read from the stream
    return $chunk;
}, STREAM_FILTER_READ);
```

### prepend()

The `prepend($stream, $callback, $read_write = STREAM_FILTER_ALL)` function can be used to
prepend a filter callback to the given stream.

Each stream can have a list of filters attached.
This function prepends a filter to the start of this list.

This function returns a filter resource which can be passed to [`remove()`](#remove).
If the given filter can not be added, it throws an `Exception`.

```php
$filter = Filter\prepend($stream, function ($chunk) {
    // will be called each time you read or write a $chunk to/from the stream
    return $chunk;
});
```

### remove()

The `remove($filter)` function can be used to
remove a filter previously added via [`append()`](#append) or [`prepend()`](#prepend).

```php
$filter = Filter\append($stream, function () {
    // …
});
Filter\remove($filter);
```

## Install

The recommended way to install this library is [through composer](https://getcomposer.org).
[New to composer?](https://getcomposer.org/doc/00-intro.md)

```JSON
{
    "require": {
        "clue/stream-filter": "dev-master"
    }
}
```

## License

MIT
