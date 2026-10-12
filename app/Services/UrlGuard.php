<?php

namespace App\Services;

/**
 * Protection contre les requêtes vers le réseau interne (SSRF) pour les cibles de rejeu.
 *
 * Limite connue : la résolution DNS est contrôlée au moment de l'envoi, mais l'adresse
 * réellement jointe par le client HTTP est résolue une seconde fois (liaison DNS tardive).
 * Pour une exposition publique, ajouter un proxy sortant dédié.
 */
class UrlGuard
{
    public function isSafe(string $url): bool
    {
        $parts = parse_url($url);

        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if (! in_array(strtolower($parts['scheme']), ['http', 'https'], true)) {
            return false;
        }

        if (isset($parts['user']) || isset($parts['pass'])) {
            return false;
        }

        if (config('relais.allow_private_targets')) {
            return true;
        }

        $ips = $this->resolve(trim($parts['host'], '[]'));

        if ($ips === []) {
            return false;
        }

        foreach ($ips as $ip) {
            if (! filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @return list<string>
     */
    protected function resolve(string $host): array
    {
        if (filter_var($host, FILTER_VALIDATE_IP)) {
            return [$host];
        }

        $ips = @gethostbynamel($host) ?: [];

        foreach (@dns_get_record($host, DNS_AAAA) ?: [] as $record) {
            $ips[] = $record['ipv6'];
        }

        return $ips;
    }
}
