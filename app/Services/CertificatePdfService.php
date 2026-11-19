<?php

namespace App\Services;

use App\Models\Equipment;
use Illuminate\Support\Facades\Storage;

class CertificatePdfService
{
    public static function generate(Equipment $equipment, string $certificateNumber, string $issuedAt): string
    {
        $lines = [
            'ASTA ENGINEERING',
            'EQUIPMENT CERTIFICATE',
            'Certificate No: '.$certificateNumber,
            'Equipment: '.$equipment->name,
            'Short Name: '.($equipment->short_name ?: '-'),
            'Equipment No: '.($equipment->equipment_code ?: '-'),
            'Serial No: '.($equipment->serial_number ?: '-'),
            'Manufacturer: '.($equipment->manufacturer ?: '-'),
            'Model: '.($equipment->model ?: '-'),
            'Rated Capacity: '.($equipment->rated_capacity ?: '-'),
            'SWL / Capacity: '.($equipment->swl_capacity ?: '-'),
            'Site: '.$equipment->site->site_name,
            'Issued On: '.$issuedAt,
        ];

        $stream = "BT\n/F1 18 Tf\n60 760 Td\n";
        foreach ($lines as $index => $line) {
            if ($index === 2) {
                $stream .= "/F1 11 Tf\n";
            }
            $escaped = str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $line) ?: $line);
            $stream .= ($index ? "0 -30 Td\n" : '')."({$escaped}) Tj\n";
        }
        $stream .= 'ET';

        $objects = [
            '<< /Type /Catalog /Pages 2 0 R >>',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Resources << /Font << /F1 5 0 R >> >> /Contents 4 0 R >>',
            '<< /Length '.strlen($stream)." >>\nstream\n{$stream}\nendstream",
            '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>',
        ];

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $number => $object) {
            $offsets[] = strlen($pdf);
            $pdf .= ($number + 1)." 0 obj\n{$object}\nendobj\n";
        }
        $xref = strlen($pdf);
        $pdf .= "xref\n0 6\n0000000000 65535 f \n";
        for ($i = 1; $i <= 5; $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i])."\n";
        }
        $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xref}\n%%EOF";

        $path = 'equipment-certificates/'.now()->format('Y/m').'/'.$certificateNumber.'.pdf';
        Storage::disk('public')->put($path, $pdf);

        return $path;
    }
}
