# attestation

[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg?style=flat-square)](LICENSE.md)
[![Travis](https://img.shields.io/travis/chinhle-pa/attestation.svg?style=flat-square)]()
[![Total Downloads](https://img.shields.io/packagist/dt/chinhle-pa/attestation.svg?style=flat-square)](https://packagist.org/packages/chinhle-pa/attestation)

## Install
`composer require chinhle-pa/attestation`

## Usage
Add middleware ensuredeive to any routes you need check attestation.
``` bash
Route::get('/full-info', function(Request $request){
    return $request;
})->middleware('ensuredevice');
```
Plugin will repond a challenge key
``` bash
{
    "challenge": "bWd2dkJEdjhiS0dQT1ZtVXVqcFA="
}
```

### iOS
Call POST api/via-attestation to check

``` bash
{
    "device-platform": "iOS",
    "challenge": "bWd2dkJEdjhiS0dQT1ZtVXVqcFA=",
    "integrityToken": "o2NmbXRvYXBwbGUtY....",
    "keyIndentifier": "sy05jqrd95uyFKZcJrHobs6s.."
}
```

### Android

``` bash
{
    "device-platform": "Android",
    "challenge": "bWd2dkJEdjhiS0dQT1ZtVXVqcFA=",
    "integrityToken": "o2NmbXRvYXBwbGUtY...."
}
```

## Testing
Run the tests with:

``` bash
vendor/bin/phpunit
```

## Changelog
Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing
Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Credits

- [Chinh Le](https://github.com/chinhle-pa)
- [All Contributors](https://github.com/chinhle-pa/attestation/contributors)

## Security
If you discover any security-related issues, please email chinhle@pacificcross.com.vn instead of using the issue tracker.

## License
The MIT License (MIT). Please see [License File](/LICENSE.md) for more information.