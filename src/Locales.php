<?php

declare(strict_types=1);

namespace Conia;

use \Closure;
use \RuntimeException;
use \ValueError;


class Locales
{
    /** @var array<string, Locale> */
    protected array $locales = [];
    protected ?string $default = null;
    protected ?Closure $negotiator = null;

    public function add(
        string $id,
        string $title,
        ?string $fallback = null,
        ?array $domains = null,
        ?string $urlPrefix = null,
    ) {
        $this->locales[$id] =  new Locale($this, $id, $title, $fallback, $domains, $urlPrefix);
    }

    public function setDefault(string $locale): void
    {
        if ($this->exists($locale)) {
            $this->default = $locale;
            return;
        }

        throw new ValueError('Locale does not exist. Add all your locales first before setting the default.');
    }

    public function setNegotiator(Closure $func): void
    {
        $this->negotiator = $func;
    }

    protected function exists(string $id): bool
    {
        return array_key_exists($id, $this->locales);
    }

    public function get(string $id): Locale
    {
        return $this->locales[$id];
    }

    protected function fromBrowser(): string|false
    {
        if (isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            preg_match_all(
                '/([a-z]{1,8}(-[a-z]{1,8})?)\s*(;\s*q\s*=\s*(1|0\.[0-9]+))?/i',
                $_SERVER['HTTP_ACCEPT_LANGUAGE'],
                $matches
            );

            if (count($matches[1])) {
                $langs = array_combine($matches[1], $matches[4]);

                foreach ($langs as $lang => $val) {
                    if ($val === '') {
                        $langs[$lang] = 1;
                    }
                }

                arsort($langs, SORT_NUMERIC);

                foreach ($langs as $lang => $val) {
                    if ($this->exists($lang)) return $lang;

                    $lang = str_replace('-', '_', $lang);
                    if ($this->exists($lang))  return $lang;

                    $lang = strtok($lang, '_');
                    if ($this->exists($lang))  return $lang;
                }
            }
        }

        return false;
    }

    public function fromRequest(Request $request): Locale
    {
        // By domain
        $host = strtolower(explode(':', $request->host())[0]);
        foreach ($this->locales as $locale) {
            foreach ($locale->domains as $domain) {
                if ($host === $domain) {
                    return $locale;
                }
            }
        }

        // From URL path prefix. e. g. http://example.com/en_EN/path/to/page
        $prefix = explode('/', trim(parse_url($request->url())['path'], '/'))[0];
        foreach ($this->locales as $locale) {
            if ($prefix === $locale->urlPrefix) {
                return $locale;
            }
        }

        // From session
        $locale = $request->session()->get('locale', false);
        if ($locale && $this->exists($locale)) {
            return $this->locales[$locale];
        }

        // From the locales the browser says the user accepts
        $locale =  $this->fromBrowser();
        if ($locale && $this->exists($locale)) {
            return $this->locales[$locale];
        }

        // default locale from config file
        if ($this->default !== null) {
            return $this->locales[$this->default];
        }

        return null;
    }

    public function negotiate(Request $request): Locale
    {
        if (count($this->locales) === 0) {
            throw new RuntimeException('No locales available. Add at least your default language.');
        }

        if ($this->negotiator) {
            return ($this->negotiator)($request);
        }

        return  $this->fromRequest($request);
    }
}
