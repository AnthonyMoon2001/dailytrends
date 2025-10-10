<?php

namespace App\Scraper;

final class HtmlUtils
{
    public static function tidy(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }
        $t = trim(
            preg_replace(
                "/\s+/u",
                " ",
                html_entity_decode($text, ENT_QUOTES | ENT_HTML5, "UTF-8")
            )
        );
        return $t !== "" ? $t : null;
    }

    public static function absolutize(string $href, string $base): string
    {
        if (
            $href === "" ||
            str_starts_with($href, "http://") ||
            str_starts_with($href, "https://")
        ) {
            return $href;
        }
        return rtrim($base, "/") . "/" . ltrim($href, "/");
    }

    public static function firstNonEmpty(string ...$candidates): ?string
    {
        foreach ($candidates as $c) {
            $t = self::tidy($c);
            if ($t) {
                return $t;
            }
        }
        return null;
    }

    public static function parseSrcset(?string $srcset): ?string
    {
        if (!$srcset) {
            return null;
        }
        $parts = array_map("trim", explode(",", $srcset));
        if (!$parts) {
            return null;
        }
        $first = $parts[0];
        $spacePos = strpos($first, " ");
        $url = $spacePos === false ? $first : substr($first, 0, $spacePos);
        return $url ?: null;
    }

    public static function sanitizeUrl(string $url): string
    {
        $kill = [
            "utm_source",
            "utm_medium",
            "utm_campaign",
            "utm_term",
            "utm_content",
            "gclid",
            "fbclid",
            "intcmp",
            "catlivefeed",
            "s",
        ];
        $p = parse_url($url);
        if (!$p) {
            return $url;
        }
        $query = [];
        if (!empty($p["query"])) {
            parse_str($p["query"], $query);
            foreach ($kill as $k) {
                unset($query[$k]);
            }
        }
        $qs = http_build_query($query);
        $scheme = $p["scheme"] ?? "https";
        $host = $p["host"] ?? "";
        $path = $p["path"] ?? "";
        return $scheme . "://" . $host . $path . ($qs ? "?" . $qs : "");
    }

    public static function isArticleUrl(
        string $href,
        string $baseHost,
        array $denySubstrings = []
    ): bool {
        $u = strtolower($href);
        foreach ($denySubstrings as $bad) {
            if (str_contains($u, $bad)) {
                return false;
            }
        }
        if (preg_match("#/20\d{2}/\d{2}/\d{2}/#", $u)) {
            return true;
        }
        if (str_ends_with($u, ".html")) {
            return true;
        }
        if (
            str_starts_with($u, "/") &&
            !str_contains($u, "/ultimas-noticias")
        ) {
            return true;
        }
        if (str_starts_with($u, "http") && str_contains($u, $baseHost)) {
            return true;
        }
        return false;
    }
}
