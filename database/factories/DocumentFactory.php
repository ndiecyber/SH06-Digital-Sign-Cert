<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class DocumentFactory extends Factory
{
    public function definition(): array
    {
        $topics = [
            'PKS_Layanan_Cloud_Hosting',
            'NDA_Pengembangan_Aplikasi_Mobile',
            'Surat_Keputusan_Struktur_Organisasi',
            'SOP_Manajemen_Keamanan_Informasi',
            'Laporan_Keuangan_Kuartal_II',
            'Proposal_Pembaruan_Infrastruktur_IT',
            'Kontrak_Kerja_Sama_Vendor_Security',
            'Dokumen_Spesifikasi_Kebutuhan_Sistem',
            'Adendum_Perpanjangan_Sewa_Server',
            'Surat_Tugas_Penetrasi_Testing_BSrE',
            'Laporan_Audit_Internal_ISO27001',
            'SOP_Pencadangan_Data_dan_Recovery',
            'NDA_Kemitraan_Bisnis_Strategis',
            'Kontrak_Pengadaan_Lisensi_Software',
            'Sertifikasi_Kepatuhan_Regulasi_E-Sign',
            'BAP_Instalasi_Infrastruktur_Jaringan',
        ];
        $extensions = ['.pdf', '.docx', '.xlsx'];
        $title = fake()->randomElement($topics) . '_' . fake()->numberBetween(10, 99) . fake()->randomElement($extensions);

        return [
            'title' => $title,
            'type' => fake()->randomElement(['Kontrak', 'Proposal', 'SOP', 'Laporan']),
            'status' => fake()->randomElement(['draft', 'pending', 'signed', 'rejected']),
            'file_path' => 'documents/' . fake()->uuid() . '.pdf',
            'uploaded_by_id' => \App\Models\User::factory(),
            'created_at' => fake()->dateTimeBetween('-1 year', 'now'),
        ];
    }
}
