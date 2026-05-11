<?php

namespace App\Services;

use App\Models\CustomDomain;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DnsVerificationService
{
    /**
     * Generate verification token for domain ownership
     */
    public function generateVerificationToken(CustomDomain $domain): string
    {
        $token = Str::random(40);
        
        $domain->update([
            'verification_token' => $token,
            'is_verified' => false,
        ]);

        return $token;
    }

    /**
     * Verify domain ownership via DNS TXT record
     */
    public function verifyDomainOwnership(CustomDomain $domain): bool
    {
        try {
            // Query DNS TXT records
            $records = dns_get_record($domain->domain, DNS_TXT);
            
            if (!$records) {
                return false;
            }

            // Check for verification token
            $expectedRecord = "yg-verify={$domain->verification_token}";
            
            foreach ($records as $record) {
                if (isset($record['txt']) && $record['txt'] === $expectedRecord) {
                    $domain->update([
                        'is_verified' => true,
                        'verified_at' => now(),
                    ]);
                    
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('DNS verification failed', [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Verify MX record configuration
     */
    public function verifyMxRecord(CustomDomain $domain): bool
    {
        try {
            $records = dns_get_record($domain->domain, DNS_MX);
            
            if (!$records) {
                return false;
            }

            // Check if MX record points to YG Mail servers
            foreach ($records as $record) {
                if (isset($record['target']) && 
                    str_contains($record['target'], 'mail.ygxone.com')) {
                    $domain->update(['mx_configured' => true]);
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('MX record verification failed', [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Verify SPF record
     */
    public function verifySpfRecord(CustomDomain $domain): bool
    {
        try {
            $records = dns_get_record($domain->domain, DNS_TXT);
            
            if (!$records) {
                return false;
            }

            // Check for SPF record including YG Mail
            foreach ($records as $record) {
                if (isset($record['txt']) && 
                    str_contains($record['txt'], 'v=spf1') &&
                    str_contains($record['txt'], '_spf.ygxone.com')) {
                    $domain->update(['spf_configured' => true]);
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('SPF verification failed', [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Verify DKIM record
     */
    public function verifyDkimRecord(CustomDomain $domain): bool
    {
        try {
            $dkimSelector = 'yg._domainkey.' . $domain->domain;
            $records = dns_get_record($dkimSelector, DNS_TXT);
            
            if (!$records) {
                return false;
            }

            // Check for DKIM public key
            foreach ($records as $record) {
                if (isset($record['txt']) && 
                    str_contains($record['txt'], 'v=DKIM1')) {
                    $domain->update(['dkim_configured' => true]);
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('DKIM verification failed', [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Verify DMARC record
     */
    public function verifyDmarcRecord(CustomDomain $domain): bool
    {
        try {
            $dmarcSelector = '_dmarc.' . $domain->domain;
            $records = dns_get_record($dmarcSelector, DNS_TXT);
            
            if (!$records) {
                return false;
            }

            // Check for DMARC policy
            foreach ($records as $record) {
                if (isset($record['txt']) && 
                    str_contains($record['txt'], 'v=DMARC1')) {
                    $domain->update(['dmarc_configured' => true]);
                    return true;
                }
            }

            return false;
        } catch (\Exception $e) {
            Log::error('DMARC verification failed', [
                'domain' => $domain->domain,
                'error' => $e->getMessage(),
            ]);
            
            return false;
        }
    }

    /**
     * Run all DNS verifications
     */
    public function verifyAllRecords(CustomDomain $domain): array
    {
        return [
            'ownership' => $this->verifyDomainOwnership($domain),
            'mx' => $this->verifyMxRecord($domain),
            'spf' => $this->verifySpfRecord($domain),
            'dkim' => $this->verifyDkimRecord($domain),
            'dmarc' => $this->verifyDmarcRecord($domain),
        ];
    }

    /**
     * Get DNS propagation status
     */
    public function getPropagationStatus(CustomDomain $domain): array
    {
        return [
            'is_verified' => $domain->is_verified,
            'mx_configured' => $domain->mx_configured,
            'spf_configured' => $domain->spf_configured,
            'dkim_configured' => $domain->dkim_configured,
            'dmarc_configured' => $domain->dmarc_configured,
            'fully_configured' => $domain->isFullyConfigured(),
            'dns_records' => $domain->getDnsVerificationRecords(),
        ];
    }
}
