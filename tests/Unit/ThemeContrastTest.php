<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ThemeContrastTest extends TestCase
{
    public function test_semantic_text_pairs_remain_readable_in_both_modes(): void
    {
        $css = file_get_contents(__DIR__.'/../../public/css/solvet.css');
        preg_match('/^:root\s*\{(.*?)^\}/ms', $css, $lightBlock);
        preg_match('/^:root\.dark\s*\{(.*?)^\}/ms', $css, $darkBlock);
        $light = $this->tokens($lightBlock[1]);
        $dark = array_replace($light, $this->tokens($darkBlock[1]));
        foreach (['light' => $light, 'dark' => $dark] as $mode => $tokens) {
            foreach ([
                ['text', 'surface'], ['text', 'bg'], ['heading', 'surface'],
                ['muted', 'surface'], ['muted', 'bg'], ['muted', 'table-header'],
                ['link', 'surface'], ['link', 'nav-active'], ['primary-text', 'primary-bg'],
                ['primary-text', 'primary-hover'], ['info-text', 'info-bg'],
                ['success-text', 'success-bg'], ['warning-text', 'warning-bg'],
                ['danger-text', 'danger-bg'], ['neutral-text', 'neutral-bg'],
                ['text', 'field'], ['muted', 'field'],
            ] as [$foreground, $background]) {
                $ratio = $this->contrast($tokens[$foreground], $tokens[$background]);
                $this->assertGreaterThanOrEqual(4.5, $ratio, "$mode $foreground/$background: $ratio");
            }
            foreach (['surface', 'field', 'nav-active'] as $background) {
                $ratio = $this->contrast($tokens['focus'], $tokens[$background]);
                $this->assertGreaterThanOrEqual(3, $ratio, "$mode focus/$background: $ratio");
            }
        }
    }

    private function tokens(string $block): array
    {
        preg_match_all('/--solvet-([a-z-]+):\s*(#[a-f0-9]{6})\s*;/i', $block, $matches, PREG_SET_ORDER);

        return array_column($matches, 2, 1);
    }

    private function contrast(string $foreground, string $background): float
    {
        $values = [$this->luminance($foreground), $this->luminance($background)];

        return (max($values) + 0.05) / (min($values) + 0.05);
    }

    private function luminance(string $hex): float
    {
        $channels = array_map(function ($channel) {
            $value = hexdec($channel) / 255;

            return $value <= 0.04045 ? $value / 12.92 : (($value + 0.055) / 1.055) ** 2.4;
        }, str_split(substr($hex, 1), 2));

        return $channels[0] * 0.2126 + $channels[1] * 0.7152 + $channels[2] * 0.0722;
    }
}
