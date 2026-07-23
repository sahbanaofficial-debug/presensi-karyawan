<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Services\HaversineService;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class HaversineServiceTest extends TestCase
{
    private HaversineService $haversineService;

    protected function setUp(): void
    {
        parent::setUp();

        $this->haversineService =
            new HaversineService;
    }

    public function test_same_coordinates_produce_zero_distance(): void
    {
        $distance =
            $this->haversineService
                ->distanceInMeters(
                    3.595196,
                    98.672226,
                    3.595196,
                    98.672226
                );

        $this->assertSame(
            0.0,
            $distance
        );
    }

    public function test_small_latitude_difference_produces_expected_distance(): void
    {
        $distance =
            $this->haversineService
                ->distanceInMeters(
                    3.595196,
                    98.672226,
                    3.595296,
                    98.672226
                );

        /*
         * Perbedaan latitude 0,0001 derajat
         * menghasilkan jarak sekitar 11,12 meter.
         */
        $this->assertEqualsWithDelta(
            11.12,
            $distance,
            0.02
        );
    }

    public function test_one_degree_latitude_at_equator_produces_expected_distance(): void
    {
        $distance =
            $this->haversineService
                ->distanceInMeters(
                    0.0,
                    0.0,
                    1.0,
                    0.0
                );

        /*
         * Dengan radius bumi 6.371.000 meter,
         * satu derajat latitude sekitar 111.194,93 meter.
         */
        $this->assertEqualsWithDelta(
            111_194.93,
            $distance,
            0.05
        );
    }

    public function test_distance_calculation_is_symmetric(): void
    {
        $forwardDistance =
            $this->haversineService
                ->distanceInMeters(
                    3.595196,
                    98.672226,
                    3.600000,
                    98.680000
                );

        $reverseDistance =
            $this->haversineService
                ->distanceInMeters(
                    3.600000,
                    98.680000,
                    3.595196,
                    98.672226
                );

        $this->assertEqualsWithDelta(
            $forwardDistance,
            $reverseDistance,
            0.000001
        );
    }

    public function test_coordinate_inside_geofence_returns_true(): void
    {
        $isWithinRadius =
            $this->haversineService
                ->isWithinRadius(
                    3.595196,
                    98.672226,
                    3.595296,
                    98.672226,
                    30.0
                );

        $this->assertTrue(
            $isWithinRadius
        );
    }

    public function test_coordinate_outside_geofence_returns_false(): void
    {
        $isWithinRadius =
            $this->haversineService
                ->isWithinRadius(
                    3.595196,
                    98.672226,
                    3.595296,
                    98.672226,
                    5.0
                );

        $this->assertFalse(
            $isWithinRadius
        );
    }

    public function test_coordinate_on_geofence_boundary_is_accepted(): void
    {
        $originLatitude = 3.595196;
        $originLongitude = 98.672226;
        $destinationLatitude = 3.595296;
        $destinationLongitude = 98.672226;

        $distance =
            $this->haversineService
                ->distanceInMeters(
                    $originLatitude,
                    $originLongitude,
                    $destinationLatitude,
                    $destinationLongitude
                );

        $isWithinRadius =
            $this->haversineService
                ->isWithinRadius(
                    $originLatitude,
                    $originLongitude,
                    $destinationLatitude,
                    $destinationLongitude,
                    $distance
                );

        $this->assertTrue(
            $isWithinRadius
        );
    }

    public function test_distance_can_be_rounded_to_two_decimal_places(): void
    {
        $distance =
            $this->haversineService
                ->roundedDistanceInMeters(
                    3.595196,
                    98.672226,
                    3.595296,
                    98.672226,
                    2
                );

        $this->assertSame(
            11.12,
            $distance
        );
    }

    public function test_distance_can_be_rounded_to_zero_decimal_places(): void
    {
        $distance =
            $this->haversineService
                ->roundedDistanceInMeters(
                    3.595196,
                    98.672226,
                    3.595296,
                    98.672226,
                    0
                );

        $this->assertSame(
            11.0,
            $distance
        );
    }

    public function test_origin_latitude_below_minimum_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Koordinat asal memiliki latitude yang tidak valid.'
        );

        $this->haversineService
            ->distanceInMeters(
                -90.000001,
                98.672226,
                3.595196,
                98.672226
            );
    }

    public function test_origin_latitude_above_maximum_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Koordinat asal memiliki latitude yang tidak valid.'
        );

        $this->haversineService
            ->distanceInMeters(
                90.000001,
                98.672226,
                3.595196,
                98.672226
            );
    }

    public function test_origin_longitude_below_minimum_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Koordinat asal memiliki longitude yang tidak valid.'
        );

        $this->haversineService
            ->distanceInMeters(
                3.595196,
                -180.000001,
                3.595196,
                98.672226
            );
    }

    public function test_origin_longitude_above_maximum_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Koordinat asal memiliki longitude yang tidak valid.'
        );

        $this->haversineService
            ->distanceInMeters(
                3.595196,
                180.000001,
                3.595196,
                98.672226
            );
    }

    public function test_destination_latitude_is_validated(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Koordinat tujuan memiliki latitude yang tidak valid.'
        );

        $this->haversineService
            ->distanceInMeters(
                3.595196,
                98.672226,
                91.0,
                98.672226
            );
    }

    public function test_destination_longitude_is_validated(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Koordinat tujuan memiliki longitude yang tidak valid.'
        );

        $this->haversineService
            ->distanceInMeters(
                3.595196,
                98.672226,
                3.595196,
                181.0
            );
    }

    public function test_non_finite_coordinate_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Koordinat asal memiliki latitude yang tidak valid.'
        );

        $this->haversineService
            ->distanceInMeters(
                INF,
                98.672226,
                3.595196,
                98.672226
            );
    }

    public function test_zero_radius_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Radius geofence harus lebih besar dari nol.'
        );

        $this->haversineService
            ->isWithinRadius(
                3.595196,
                98.672226,
                3.595296,
                98.672226,
                0.0
            );
    }

    public function test_negative_radius_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Radius geofence harus lebih besar dari nol.'
        );

        $this->haversineService
            ->isWithinRadius(
                3.595196,
                98.672226,
                3.595296,
                98.672226,
                -10.0
            );
    }

    public function test_non_finite_radius_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Radius geofence harus lebih besar dari nol.'
        );

        $this->haversineService
            ->isWithinRadius(
                3.595196,
                98.672226,
                3.595296,
                98.672226,
                INF
            );
    }

    public function test_negative_rounding_precision_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Presisi pembulatan harus berada antara 0 dan 6.'
        );

        $this->haversineService
            ->roundedDistanceInMeters(
                3.595196,
                98.672226,
                3.595296,
                98.672226,
                -1
            );
    }

    public function test_rounding_precision_above_six_is_rejected(): void
    {
        $this->expectException(
            InvalidArgumentException::class
        );

        $this->expectExceptionMessage(
            'Presisi pembulatan harus berada antara 0 dan 6.'
        );

        $this->haversineService
            ->roundedDistanceInMeters(
                3.595196,
                98.672226,
                3.595296,
                98.672226,
                7
            );
    }
}
