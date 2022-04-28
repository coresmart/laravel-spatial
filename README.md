# Laravel Spatial

Forked from tarfin-labs/laravel-spatial for laravel 9 support

[![Latest Version on Packagist](https://img.shields.io/packagist/v/tarfin-labs/laravel-spatial.svg?style=flat-square)](https://packagist.org/packages/tarfin-labs/laravel-spatial)
[![Total Downloads](https://img.shields.io/packagist/dt/tarfin-labs/laravel-spatial.svg?style=flat-square)](https://packagist.org/packages/tarfin-labs/laravel-spatial)
![GitHub Actions](https://github.com/tarfin-labs/laravel-spatial/actions/workflows/main.yml/badge.svg)

This is a Laravel package to work with geospatial data types and functions.

It supports only MySQL Spatial Data Types and Functions, other RDBMS is on the roadmap.

**Supported data types:**
- `Point`

**Available Scopes:**
- `withinDistanceTo($column, $coordinates, $distance)`
- `selectDistanceTo($column, $coordinates)`
- `orderByDistanceTo($column, $coordinates, 'asc')`

***
## Installation

You can install the package via composer:

```bash
composer require tarfin-labs/laravel-spatial
```

## Usage
Generate a new model with a migration file:

```bash
php artisan make:model Address --migration
```

### 1- Migrations:

To add a spatial data field, you need to extend the migration from `TarfinLabs\LaravelSpatial\Migrations\SpatialMigration`.

It is a simple abstract class that adds `point` spatial data type to Doctrine mapped types in the constructor.

```php
use TarfinLabs\LaravelSpatial\Migrations\SpatialMigration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends SpatialMigration {
    
    public function up(): void
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->point('location');
        })
    }

}
```

The migration above creates an `addresses` table with a `location` spatial column.

> Spatial columns with no SRID attribute are not SRID-restricted and accept values with any SRID. However, the optimizer cannot use SPATIAL indexes on them until the column definition is modified to include an SRID attribute, which may require that the column contents first be modified so that all values have the same SRID.

So you should give an SRID attribute to use spatial indexes in the migrations:

```php
Schema::create('addresses', function (Blueprint $table) {
    $table->point(column: 'location', srid: 4326);
    
    $table->spatialIndex('location');
})
```

***

### 2- Models:

Fill the `$fillable`, `$casts` arrays in the model:

```php
use Illuminate\Database\Eloquent\Model;
use TarfinLabs\LaravelSpatial\Casts\LocationCast;
use TarfinLabs\LaravelSpatial\Traits\HasSpatial;

class Address extends Model {

    use HasSpatial;

    protected $fillable = [
        'id',
        'name',
        'address',
        'location',
    ];
    
    protected array $casts = [
        'location' => LocationCast::class
    ];

}
```

### 3- Spatial Data Types:

#### ***Point:***
`Point` represents the coordinates of a location and contains `latitude`, `longitude`, and `srid` properties.

At this point, it is crucial to understand what SRID is. Each spatial instance has a spatial reference identifier (SRID). The SRID corresponds to a spatial reference system based on the specific ellipsoid used for either flat-earth mapping or round-earth mapping. A spatial column can contain objects with different SRIDs.

>For details about SRID you can follow the link:
https://en.wikipedia.org/wiki/Spatial_reference_system

- Default value of `latitude`, `longitude` parameters is `0.0`.
- Default value of `srid` parameter is `0`.

```php
use TarfinLabs\LaravelSpatial\Types\Point;

$location = new Point(lat: 28.123456, lng: 39.123456, srid: 4326);

$location->getLat(); // 28.123456
$location->getLng(); // 39.123456
$locatipn->getSrid(); // 4326
```

You can override the default SRID via the `laravel-spatial` config file. To do that, you should publish the config file using `vendor:publish` artisan command:

```bash
php artisan vendor:publish --provider="TarfinLabs\LaravelSpatial\LaravelSpatialServiceProvider"
```

After that, you can change the value of `default_srid` in `config/laravel-spatial.php`

```php 
return [
    'default_srid' => 4326,
];
```
***
### 4- Scopes:

#### ***withinDistanceTo()***

You can use the `withinDistanceTo()` scope to filter locations by given distance:

To filter addresses within the range of 10 km from the given coordinate:

```php
use TarfinLabs\LaravelSpatial\Types\Point;
use App\Models\Address;

Address::query()
       ->withinDistanceTo('location', new Point(lat: 25.45634, lng: 35.54331), 10000)
       ->get();
```

#### ***selectDistanceTo()***

You can get the distance between two points by using `selectDistanceTo()` scope. The distance will be in meters:

```php
use TarfinLabs\LaravelSpatial\Types\Point;
use App\Models\Address;

Address::query()
       ->selectDistanceTo('location', new Point(lat: 25.45634, lng: 35.54331))
       ->get();
```

#### ***orderByDistanceTo()***

You can order your models by distance to given coordinates:

```php
use TarfinLabs\LaravelSpatial\Types\Point;
use App\Models\Address;

// ASC
Address::query()
       ->orderByDistanceTo('location', new Point(lat: 25.45634, lng: 35.54331))
       ->get();

// DESC
Address::query()
       ->orderByDistanceTo('location', new Point(lat: 25.45634, lng: 35.54331), 'desc')
       ->get();
```

#### Get latitude and longitude of the location:

```php
use App\Models\Address;

$address = Address::find(1);
$address->location; // TarfinLabs\LaravelSpatial\Types\Point

$address->location->getLat();
$address->location->getLng();
```

#### Create a new address with location:

```php
use App\Models\Address;

Address::create([
    'name'      => 'Bag End',
    'address'   => '1 Bagshot Row, Hobbiton, Shire',
    'location'  => new Point(lat: 25.45634, lng: 35.54331),
]);
```

### Testing

```bash
composer test
```

### Road Map
- [ ] MultiPoint
- [ ] LineString
- [ ] MultiLineString
- [ ] Polygon
- [ ] MultiPolygon
- [ ] GeometryCollection

### Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

### Security

If you discover any security related issues, please email development@tarfin.com instead of using the issue tracker.

## Credits

-   [Turan Karatug](https://github.com/tarfin-labs)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
