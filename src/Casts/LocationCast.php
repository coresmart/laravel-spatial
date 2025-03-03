<?php

declare(strict_types=1);

namespace TarfinLabs\LaravelSpatial\Casts;

use Exception;
use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Database\Eloquent\SerializesCastableAttributes;
use Illuminate\Database\Query\Expression;
use Illuminate\Support\Facades\DB;
use TarfinLabs\LaravelSpatial\Types\Point;

class LocationCast implements CastsAttributes, SerializesCastableAttributes
{
    public function get($model, string $key, $value, array $attributes): ?Point
    {
        if (is_null($value)) {
            return null;
        }

        $coordinates = explode(',', $value);

        if (count($coordinates) > 1) {
            $location = explode(',', str_replace(['POINT(', ')', ' '], ['', '', ','], $coordinates[0]));

            return new Point(lat: (float) $location[1], lng: (float) $location[0], srid: (int) $coordinates[1]);
        }

        $location = explode(',', str_replace(['POINT(', ')', ' '], ['', '', ','], $value));

        return new Point(lat: (float) $location[1], lng: (float) $location[0]);
    }

    public function set($model, string $key, $value, array $attributes): Expression
    {
        if (!$value instanceof Point) {
            throw new Exception(message: 'The '.$key.' field must be instance of '.Point::class);
        }

        if ($value->getSrid() > 0) {
            return DB::raw(
                value: "ST_GeomFromText('POINT({$value->getLng()} {$value->getLat()})', {$value->getSrid()}, 'axis-order=long-lat')"
            );
        }

        return DB::raw(value: "ST_GeomFromText('POINT({$value->getLng()} {$value->getLat()})')");
    }

    public function serialize($model, string $key, $value, array $attributes): array
    {
        return [
            'lat'  => $value->getLat(),
            'lng'  => $value->getLng(),
            'srid' => $value->getSrid(),
        ];
    }
}
