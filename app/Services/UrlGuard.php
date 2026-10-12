<?php

namespace App\Services;

/**
 * Protection contre les requêtes vers le réseau interne (SSRF) pour les cibles de rejeu.
 *
 * Limite connue : la résolution DNS est contrôlée au moment de l'envoi, mais l'adresse
 * réellement jointe par le client HTTP est résolue une seconde fois (liaison DNS tardive).
 * Pour une exposition publique, ajouter un proxy sortant dédié ou fixer l'IP validée (CURLOPT_RESOLVE).
 */
class UrlGuard
{
    /** Plages IPv4 supplémentaires à refuser (partage d'adresses, tests, multidiffusion). */
    private const BLOCKED_V4 = [
        ['100.64.0.0', 10],
        ['192.0.0.0', 24],
        ['198.18.0.0', 15],
        ['224.0.0.0', 4],
    ];

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
            if (! $this->isPublicIp($ip)) {
                return false;
            }
        }

        return true;
    }

    public function isPublicIp(string $ip): bool
    {
        $packed = @inet_pton($ip);

        if ($packed === false) {
            return false;
        }

        if (strlen($packed) === 16) {
            // ::/8 couvre non spécifiée, boucle locale et IPv4 mappée (::ffff:a.b.c.d) ;
            // 64:ff9b::/96 (NAT64) et 2002::/16 (6to4) embarquent une IPv4 arbitraire.
            // ff00::/8 (multidiffusion) et 2001::/32 (Teredo, embarque aussi une IPv4) sont refusés de même.
            if ($packed[0] === "\x00" || $packed[0] === "\xff" || str_starts_with($packed, "\x00\x64\xff\x9b")
                || str_starts_with($packed, "\x20\x02") || str_starts_with($packed, "\x20\x01\x00\x00")) {
                return false;
            }
        } elseif ($this->inBlockedV4($ip)) {
            return false;
        }

        return (bool) filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE);
    }

    private function inBlockedV4(string $ip): bool
    {
        $long = ip2long($ip);

        foreach (self::BLOCKED_V4 as [$network, $bits]) {
            $mask = -1 << (32 - $bits);

            if (($long & $mask) === (ip2long($network) & $mask)) {
                return true;
            }
        }

        return false;
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
