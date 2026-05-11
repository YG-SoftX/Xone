<?php

namespace App\Services;

use App\Models\Contact;

class ContactImportExportService
{
    /**
     * Export contacts as vCard (.vcf) format
     */
    public static function exportVCard(array $contacts): string
    {
        $vcf = '';
        foreach ($contacts as $contact) {
            $vcf .= "BEGIN:VCARD\r\n";
            $vcf .= "VERSION:3.0\r\n";
            $vcf .= "FN:{$contact['name']}\r\n";
            if (!empty($contact['email'])) {
                $vcf .= "EMAIL;TYPE=INTERNET:{$contact['email']}\r\n";
            }
            if (!empty($contact['phone'])) {
                $vcf .= "TEL;TYPE=CELL:{$contact['phone']}\r\n";
            }
            if (!empty($contact['organization'])) {
                $vcf .= "ORG:{$contact['organization']}\r\n";
            }
            if (!empty($contact['job_title'])) {
                $vcf .= "TITLE:{$contact['job_title']}\r\n";
            }
            if (!empty($contact['website'])) {
                $vcf .= "URL:{$contact['website']}\r\n";
            }
            $vcf .= "END:VCARD\r\n";
        }
        return $vcf;
    }

    /**
     * Export contacts as CSV
     */
    public static function exportCsv(array $contacts): string
    {
        $headers = ['Name', 'Email', 'Phone', 'Organization', 'Job Title', 'Website', 'Notes'];
        $rows    = [implode(',', $headers)];

        foreach ($contacts as $contact) {
            $rows[] = implode(',', array_map(
                fn($v) => '"' . str_replace('"', '""', $v ?? '') . '"',
                [
                    $contact['name']         ?? '',
                    $contact['email']        ?? '',
                    $contact['phone']        ?? '',
                    $contact['organization'] ?? '',
                    $contact['job_title']    ?? '',
                    $contact['website']      ?? '',
                    $contact['notes']        ?? '',
                ]
            ));
        }

        return implode("\r\n", $rows);
    }

    /**
     * Parse vCard (.vcf) import
     */
    public static function parseVCard(string $vcfContent): array
    {
        $contacts = [];
        $cards    = preg_split('/END:VCARD\r?\n?/i', $vcfContent);

        foreach ($cards as $card) {
            if (empty(trim($card))) continue;
            $contact = [];

            if (preg_match('/FN:(.+)/i', $card, $m))         $contact['name']         = trim($m[1]);
            if (preg_match('/EMAIL[^:]*:(.+)/i', $card, $m)) $contact['email']        = trim($m[1]);
            if (preg_match('/TEL[^:]*:(.+)/i', $card, $m))   $contact['phone']        = trim($m[1]);
            if (preg_match('/ORG:(.+)/i', $card, $m))         $contact['organization'] = trim($m[1]);
            if (preg_match('/TITLE:(.+)/i', $card, $m))       $contact['job_title']    = trim($m[1]);
            if (preg_match('/URL:(.+)/i', $card, $m))          $contact['website']      = trim($m[1]);

            if (!empty($contact['name'])) {
                $contacts[] = $contact;
            }
        }

        return $contacts;
    }

    /**
     * Parse CSV import
     */
    public static function parseCsv(string $csvContent): array
    {
        $contacts = [];
        $lines    = explode("\n", trim($csvContent));
        $headers  = str_getcsv(array_shift($lines));
        $headers  = array_map('strtolower', $headers);

        foreach ($lines as $line) {
            if (empty(trim($line))) continue;
            $values  = str_getcsv($line);
            $row     = array_combine($headers, array_pad($values, count($headers), ''));
            $contacts[] = [
                'name'         => $row['name'] ?? $row['full name'] ?? '',
                'email'        => $row['email'] ?? '',
                'phone'        => $row['phone'] ?? $row['mobile'] ?? '',
                'organization' => $row['organization'] ?? $row['company'] ?? '',
                'job_title'    => $row['job title'] ?? $row['title'] ?? '',
                'website'      => $row['website'] ?? '',
                'notes'        => $row['notes'] ?? '',
            ];
        }

        return $contacts;
    }
}
