# Access Logs Bandwidth

Simple script to parse logs in a `data/` folder and output the total bandwidth for the past month in GB.

## Status

This is a proof of concept at present and requires refactoring into a proper CLI command.

## Requirements

* PHP 7.1+
* Composer

## Installation

Until version 0.5 you need to install via dev-master. 

```
composer install
```

Add your log files to the `data/` folder and run `php access-logs-bandwidth.php`

## License

The MIT License (MIT). Please see [License File](LICENSE) for more information.

## Credits

- [Simon R Jones](https://github.com/simonrjones)

