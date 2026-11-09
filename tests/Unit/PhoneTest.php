<?php

namespace Tests\Unit;

use App\Support\Phone;
use PHPUnit\Framework\TestCase;

class PhoneTest extends TestCase
{
    public function test_converts_local_and_international_formats(): void
    {
        $this->assertSame('6281234567890', Phone::international('0812-3456-7890'));
        $this->assertSame('6281234567890', Phone::international('+62 812 3456 7890'));
        $this->assertSame('6281234567890', Phone::international('6281234567890'));
        $this->assertSame('6281234567890', Phone::international('81234567890'));
    }

    public function test_rejects_empty_or_too_short_numbers(): void
    {
        $this->assertNull(Phone::international(null));
        $this->assertNull(Phone::international(''));
        $this->assertNull(Phone::international('-'));
        $this->assertNull(Phone::international('0812'));
        $this->assertNull(Phone::whatsappUrl('abc', 'halo'));
        $this->assertNull(Phone::telUrl(null));
    }

    public function test_builds_whatsapp_and_tel_links(): void
    {
        $this->assertSame('https://wa.me/6281234567890?text=Halo%20Dimas%0ATerima%20kasih', Phone::whatsappUrl('081234567890', "Halo Dimas\nTerima kasih"));
        $this->assertSame('https://wa.me/6281234567890', Phone::whatsappUrl('081234567890'));
        $this->assertSame('tel:+6281234567890', Phone::telUrl('0812-3456-7890'));
    }
}
