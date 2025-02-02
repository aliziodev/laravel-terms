<?php

namespace Aliziodev\LaravelTerms\Traits;

trait HasMetaAttributes
{
    /**
     * Check if term has meta key
     */
    public function hasMeta(string $key): bool
    {
        return isset($this->meta[$key]);
    }

    /**
     * Get all meta data
     */
    public function getAllMeta(): array
    {
        return $this->meta ?? [];
    }

    /**
     * Get meta value with dot notation support
     */
    public function getMeta(string $key = null, $default = null): mixed
    {
        if (is_null($key)) {
            return $this->getAllMeta();
        }

        return data_get($this->meta, $key, $default);
    }

    /**
     * Set meta value with dot notation support
     */
    public function setMeta(string $key, $value): self
    {
        $meta = $this->meta ?? [];
        data_set($meta, $key, $value);
        $this->meta = $meta;
        return $this;
    }

    /**
     * Unset meta value with dot notation support
     */
    public function unsetMeta(string $key): self
    {
        $meta = $this->meta ?? [];

        if (str_contains($key, '.')) {
            $parts = explode('.', $key);
            $lastKey = array_pop($parts);
            $current = &$meta;
            
            foreach ($parts as $part) {
                if (!isset($current[$part]) || !is_array($current[$part])) {
                    return $this;
                }
                $current = &$current[$part];
            }
            
            unset($current[$lastKey]);
        } else {
            unset($meta[$key]);
        }

        $this->meta = $meta;
        $this->save();

        return $this;
    }
} 