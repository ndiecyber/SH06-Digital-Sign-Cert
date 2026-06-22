<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CertificateFactory extends Factory
{
    public function definition(): array
    {
        $certTypes = [
            'SSL Wildcard Certificate',
            'Code Signing Certificate',
            'E-Sign Enterprise Certificate',
            'Root CA Self-Signed Certificate',
            'Client Authentication Certificate',
            'API Gateway TLS Certificate',
            'Sertifikat Elektronik BSrE',
            'Sertifikat Tanda Tangan Digital Kominfo',
        ];
        $holders = [
            'PT Lexa Teknologi Indonesia',
            'PT Bank Central Asia Tbk',
            'PT Telekomunikasi Indonesia Tbk',
            'Kementerian Komunikasi dan Informatika',
            'Badan Siber dan Sandi Negara',
            'PT Pertamina (Persero)',
            'PT GoTo Gojek Tokopedia Tbk',
        ];

        $issuedAt = fake()->dateTimeBetween('-1 year', 'now');
        return [
            'name' => fake()->randomElement($holders) . ' - ' . fake()->randomElement($certTypes),
            'holder' => fake()->randomElement($holders),
            'status' => fake()->randomElement(['valid', 'expiring_soon', 'expired']),
            'valid_until' => fake()->dateTimeBetween('now', '+1 year'),
            'issued_at' => $issuedAt,
            'created_at' => $issuedAt,
        ];
    }
}
