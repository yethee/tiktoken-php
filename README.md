# tiktoken-php

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

## Limitations

* Encoding for GPT-2 is not supported.
* Special tokens (like `<|endofprompt|>`) are not supported.

## License

[MIT](./LICENSE)
