<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;

final class AuthenticatedLayoutEncodingTest extends TestCase
{
    public function test_authenticated_layout_contains_valid_footer_symbols(): void
    {
        $source = file_get_contents(
            resource_path(
                'views/layouts/app.blade.php'
            )
        );

        $this->assertIsString($source);

        $this->assertStringContainsString(
            "\u{00A9} {{ now()->year }} PT Gadai Ogan Baru",
            $source
        );

        $this->assertStringContainsString(
            "\u{00B7} Sistem Presensi QR TOTP dan Geofencing",
            $source
        );

        $this->assertStringContainsString(
            "Brand palette \u{2014} PT Gadai Ogan Baru",
            $source
        );

        $this->assertStringNotContainsString(
            "\u{00C3}\u{201A}\u{00C2}\u{00A9}",
            $source
        );

        $this->assertStringNotContainsString(
            "\u{00C3}\u{201A}\u{00C2}\u{00B7}",
            $source
        );

        $this->assertStringNotContainsString(
            "\u{00C3}\u{00A2}\u{00E2}\u{201A}\u{00AC}\u{00E2}\u{20AC}\u{009D}",
            $source
        );
    }
}
