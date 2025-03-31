# tiktoken-php

![Packagist Version](https://img.shields.io/packagist/v/yethee/tiktoken)
![Build status](https://img.shields.io/github/actions/workflow/status/yethee/tiktoken-php/ci.yml?branch=master)
![Code Coverage](https://app.codacy.com/project/badge/Coverage/49ec3803b480478caeca8903b7ff0a69?branch=master)
![License](https://img.shields.io/github/license/yethee/tiktoken-php)

This is a port of the [tiktoken](https://github.com/openai/tiktoken).

## Installation

```bash
$ composer require yethee/tiktoken
```

## Usage

```php

use Yethee\Tiktoken\EncoderProvider;

$provider = new EncoderProvider();

$encoder = $provider->getForModel('gpt-3.5-turbo-0301');
$tokens = $encoder->encode('Hello world!');
print_r($tokens);
// OUT: [9906, 1917, 0]

$encoder = $provider->get('p50k_base');
$tokens = $encoder->encode('Hello world!');
print_r($tokens);
// OUT: [15496, 995, 0]
```

## Cache

The encoder uses an external vocabularies, so caching is used by default
to avoid performance issues.

By default, the [directory for temporary files](https://www.php.net/manual/en/function.sys-get-temp-dir.php) is used.
You can override the directory for cache via environment variable `TIKTOKEN_CACHE_DIR`
or use `EncoderProvider::setVocabCache()`:

```php
use Yethee\Tiktoken\EncoderProvider;

$encProvider = new EncoderProvider();
$encProvider->setVocabCache('/path/to/cache');

// Using the provider
```

## Lib mode

**Experimental**

You can use [tiktoken-rs](https://github.com/zurawiki/tiktoken-rs) library via FFI binding.
This can improve performance when need to encode medium or large texts. However,
the overhead of data marshalling can lead to poor performance for small texts.

```php
use Yethee\Tiktoken\Encoder\LibEncoder;
use Yethee\Tiktoken\EncoderProvider;

// LibEncoder::init('/path/to/lib');

$encProvider = new EncoderProvider(true); // Force using the lib encoder
```

You need to provide path to the lib before using the provider. There are several ways to do this:

* Use `Yethee\Tiktoken\Encoder\LibEncoder::init()` method.
* Use `Yethee\Tiktoken\Encoder\LibEncoder::preload()` method, inside opcache preload script.
* Use environment variable `TIKTOKEN_LIB_PATH` or `LD_LIBRARY_PATH`

### Build lib

#### Requirements

* [Rust](https://www.rust-lang.org/) >= 1.85

```shell
git clone git@github.com:yethee/tiktoken-php.git
cd tiktoken-php
cargo build --release
```

Copy binary from `target/release`:

* `libtiktoken_php.so` for linux
* `libtiktoken_php.dylib` for MacOS
* `tiktoken_php.dll` for Windows

**NOTE:** You can see `.docker/Dockefile` for an example.

### Benchmark

You can see benchmark result in [#27](https://github.com/yethee/tiktoken-php/pull/27) or run it locally:

```shell
composer bench
```

### TODO

* Add implementation for `Yethee\Tiktoken\Encoder\LibEncoder::encodeInChunks()` method

## Limitations

* Encoding for GPT-2 is not supported.
* Special tokens (like `<|endofprompt|>`) are not supported.

## License

[MIT](./LICENSE)
