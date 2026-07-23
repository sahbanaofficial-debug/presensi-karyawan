<?php

declare(strict_types=1);

namespace App\Services;

use InvalidArgumentException;

final class HaversineService
{
    /**
     * Radius rata-rata bumi dalam meter.
     */
    private const EARTH_RADIUS_METERS = 6_371_000.0;

    /**
     * Menghitung jarak dua koordinat dalam meter.
     */
    public function distanceInMeters(
        float $originLatitude,
        float $originLongitude,
        float $destinationLatitude,
        float $destinationLongitude
    ): float {
        $this->validateCoordinate(
            $originLatitude,
            $originLongitude,
            'Koordinat asal'
        );

        $this->validateCoordinate(
            $destinationLatitude,
            $destinationLongitude,
            'Koordinat tujuan'
        );

        if (
            $originLatitude === $destinationLatitude
            && $originLongitude === $destinationLongitude
        ) {
            return 0.0;
        }

        $originLatitudeRadians = deg2rad(
            $originLatitude
        );

        $destinationLatitudeRadians = deg2rad(
            $destinationLatitude
        );

        $latitudeDifference = deg2rad(
            $destinationLatitude
            - $originLatitude
        );

        $longitudeDifference = deg2rad(
            $destinationLongitude
            - $originLongitude
        );

        $latitudeComponent = sin(
            $latitudeDifference / 2
        );

        $longitudeComponent = sin(
            $longitudeDifference / 2
        );

        $haversine =
            ($latitudeComponent ** 2)
            + cos($originLatitudeRadians)
            * cos($destinationLatitudeRadians)
            * ($longitudeComponent ** 2);

        /*
         * Membatasi nilai karena operasi floating point
         * dapat menghasilkan nilai sedikit di luar 0 sampai 1.
         */
        $normalizedHaversine = min(
            1.0,
            max(
                0.0,
                $haversine
            )
        );

        $centralAngle = 2 * atan2(
            sqrt($normalizedHaversine),
            sqrt(1 - $normalizedHaversine)
        );

        return self::EARTH_RADIUS_METERS
            * $centralAngle;
    }

    /**
     * Memeriksa apakah koordinat berada dalam radius geofence.
     */
    public function isWithinRadius(
        float $originLatitude,
        float $originLongitude,
        float $destinationLatitude,
        float $destinationLongitude,
        float $radiusInMeters
    ): bool {
        $this->validateRadius(
            $radiusInMeters
        );

        $distance = $this->distanceInMeters(
            $originLatitude,
            $originLongitude,
            $destinationLatitude,
            $destinationLongitude
        );

        return $distance <= $radiusInMeters;
    }

    /**
     * Menghasilkan jarak yang dibulatkan untuk penyimpanan.
     */
    public function roundedDistanceInMeters(
        float $originLatitude,
        float $originLongitude,
        float $destinationLatitude,
        float $destinationLongitude,
        int $precision = 2
    ): float {
        if (
            $precision < 0
            || $precision > 6
        ) {
            throw new InvalidArgumentException(
                'Presisi pembulatan harus berada antara 0 dan 6.'
            );
        }

        return round(
            $this->distanceInMeters(
                $originLatitude,
                $originLongitude,
                $destinationLatitude,
                $destinationLongitude
            ),
            $precision
        );
    }

    /**
     * Memeriksa rentang latitude dan longitude.
     */
    private function validateCoordinate(
        float $latitude,
        float $longitude,
        string $coordinateLabel
    ): void {
        if (
            ! is_finite($latitude)
            || $latitude < -90
            || $latitude > 90
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s memiliki latitude yang tidak valid.',
                    $coordinateLabel
                )
            );
        }

        if (
            ! is_finite($longitude)
            || $longitude < -180
            || $longitude > 180
        ) {
            throw new InvalidArgumentException(
                sprintf(
                    '%s memiliki longitude yang tidak valid.',
                    $coordinateLabel
                )
            );
        }
    }

    /**
     * Radius geofence harus bernilai positif.
     */
    private function validateRadius(
        float $radiusInMeters
    ): void {
        if (
            ! is_finite($radiusInMeters)
            || $radiusInMeters <= 0
        ) {
            throw new InvalidArgumentException(
                'Radius geofence harus lebih besar dari nol.'
            );
        }
    }
}
