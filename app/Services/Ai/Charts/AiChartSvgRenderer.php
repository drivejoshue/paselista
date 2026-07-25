<?php

namespace App\Services\Ai\Charts;

class AiChartSvgRenderer
{
    private const DEFAULT_COLORS = [
        '#206bc4',
        '#2fb344',
        '#f59f00',
        '#4299e1',
        '#d63939',
        '#ae3ec9',
    ];

    public function renderMany(
        array $charts,
        string $primaryColor,
        string $secondaryColor
    ): array {
        $images = [];

        foreach (
            array_values($charts)
            as $index => $chart
        ) {
            $images[$index] = $this->render(
                chart: is_array($chart)
                    ? $chart
                    : [],
                primaryColor: $primaryColor,
                secondaryColor: $secondaryColor
            );
        }

        return $images;
    }

    public function render(
        array $chart,
        string $primaryColor,
        string $secondaryColor
    ): ?string {
        $data = array_values(
            is_array($chart['data'] ?? null)
                ? $chart['data']
                : []
        );

        $series = array_values(
            is_array($chart['series'] ?? null)
                ? $chart['series']
                : []
        );

        if (
            $data === []
            || $series === []
        ) {
            return null;
        }

        $primaryColor = $this->safeColor(
            $primaryColor,
            self::DEFAULT_COLORS[0]
        );

        $secondaryColor = $this->safeColor(
            $secondaryColor,
            '#172033'
        );

        $colors = self::DEFAULT_COLORS;
        $colors[0] = $primaryColor;
        $colors[1] = $secondaryColor;

        $type = (string) (
            $chart['type']
            ?? 'bar'
        );

        $svg = match ($type) {
            'line' => $this->lineChart(
                chart: $chart,
                data: $data,
                series: $series,
                colors: $colors,
                textColor: $secondaryColor
            ),

            default => $this->barChart(
                chart: $chart,
                data: $data,
                series: $series,
                colors: $colors,
                textColor: $secondaryColor
            ),
        };

        return 'data:image/svg+xml;base64,'
            .base64_encode($svg);
    }

    private function lineChart(
        array $chart,
        array $data,
        array $series,
        array $colors,
        string $textColor
    ): string {
        $width = 760;
        $height = 315;

        $left = 52;
        $right = 22;
        $top = 24;
        $bottom = 62;

        $plotWidth =
            $width - $left - $right;

        $plotHeight =
            $height - $top - $bottom;

        $allValues = [];

        foreach ($data as $row) {
            foreach ($series as $item) {
                $allValues[] = (float) (
                    $row[
                        $item['key']
                        ?? ''
                    ]
                    ?? 0
                );
            }
        }

        $allPercent = collect($series)
            ->every(
                fn (array $item): bool =>
                    ($item['suffix'] ?? '')
                    === '%'
            );

        $maxValue = $allPercent
            ? 100.0
            : max(
                1.0,
                ceil(
                    max(
                        $allValues
                            ?: [1]
                    ) * 1.1
                )
            );

        $count = count($data);

        $x = static function (
            int $index
        ) use (
            $left,
            $plotWidth,
            $count
        ): float {
            if ($count <= 1) {
                return $left
                    + ($plotWidth / 2);
            }

            return $left
                + (
                    $index
                    / ($count - 1)
                ) * $plotWidth;
        };

        $y = static function (
            float $value
        ) use (
            $top,
            $plotHeight,
            $maxValue
        ): float {
            return $top
                + $plotHeight
                - (
                    $value
                    / $maxValue
                ) * $plotHeight;
        };

        $parts = [
            $this->svgOpen(
                width: $width,
                height: $height,
                textColor: $textColor
            ),
        ];

        for (
            $tick = 0;
            $tick <= 4;
            $tick++
        ) {
            $value =
                ($maxValue / 4) * $tick;

            $gridY = $y($value);

            $parts[] = sprintf(
                '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="#dbe3ef" stroke-width="1"/>',
                $left,
                $this->number($gridY),
                $width - $right,
                $this->number($gridY)
            );

            $parts[] = sprintf(
                '<text x="%s" y="%s" text-anchor="end" font-size="10" fill="#64748b">%s%s</text>',
                $left - 8,
                $this->number(
                    $gridY + 3
                ),
                $this->formatValue($value),
                $allPercent
                    ? '%'
                    : ''
            );
        }

        foreach (
            $series
            as $seriesIndex => $item
        ) {
            $key = (string) (
                $item['key']
                ?? ''
            );

            $color = $colors[
                $seriesIndex
                % count($colors)
            ];

            $points = [];

            foreach (
                $data
                as $index => $row
            ) {
                $value = (float) (
                    $row[$key]
                    ?? 0
                );

                $points[] = sprintf(
                    '%s,%s',
                    $this->number(
                        $x($index)
                    ),
                    $this->number(
                        $y($value)
                    )
                );
            }

            $parts[] = sprintf(
                '<polyline points="%s" fill="none" stroke="%s" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/>',
                implode(' ', $points),
                $color
            );

            foreach (
                $data
                as $index => $row
            ) {
                $value = (float) (
                    $row[$key]
                    ?? 0
                );

                $label = $this->xml(
                    (string) (
                        $row[
                            $chart['x_key']
                            ?? 'label'
                        ]
                        ?? ''
                    )
                );

                $parts[] = sprintf(
                    '<circle cx="%s" cy="%s" r="3.5" fill="%s"><title>%s: %s%s</title></circle>',
                    $this->number(
                        $x($index)
                    ),
                    $this->number(
                        $y($value)
                    ),
                    $color,
                    $label,
                    $this->formatValue($value),
                    $this->xml(
                        (string) (
                            $item['suffix']
                            ?? ''
                        )
                    )
                );
            }
        }

        $labelStep = max(
            1,
            (int) ceil(
                $count / 8
            )
        );

        foreach (
            $data
            as $index => $row
        ) {
            if (
                $index % $labelStep !== 0
                && $index
                    !== $count - 1
            ) {
                continue;
            }

            $label = $this->shortLabel(
                (string) (
                    $row[
                        $chart['x_key']
                        ?? 'label'
                    ]
                    ?? ''
                ),
                15
            );

            $parts[] = sprintf(
                '<text x="%s" y="%s" text-anchor="middle" font-size="9" fill="#64748b">%s</text>',
                $this->number(
                    $x($index)
                ),
                $height - 35,
                $this->xml($label)
            );
        }

        $legendX = $left;

        foreach (
            $series
            as $seriesIndex => $item
        ) {
            $color = $colors[
                $seriesIndex
                % count($colors)
            ];

            $label = $this->shortLabel(
                (string) (
                    $item['label']
                    ?? $item['key']
                    ?? 'Serie'
                ),
                25
            );

            $parts[] = sprintf(
                '<rect x="%s" y="%s" width="9" height="9" rx="2" fill="%s"/>',
                $legendX,
                $height - 18,
                $color
            );

            $parts[] = sprintf(
                '<text x="%s" y="%s" font-size="10" fill="#64748b">%s</text>',
                $legendX + 14,
                $height - 10,
                $this->xml($label)
            );

            $legendX += 150;
        }

        $parts[] = '</g></svg>';

        return implode('', $parts);
    }

    private function barChart(
        array $chart,
        array $data,
        array $series,
        array $colors,
        string $textColor
    ): string {
        if (
            (bool) (
                $chart['horizontal']
                ?? false
            )
        ) {
            return $this->horizontalBarChart(
                chart: $chart,
                data: $data,
                series: $series,
                colors: $colors,
                textColor: $textColor
            );
        }

        return $this->verticalBarChart(
            chart: $chart,
            data: $data,
            series: $series,
            colors: $colors,
            textColor: $textColor
        );
    }

    private function verticalBarChart(
        array $chart,
        array $data,
        array $series,
        array $colors,
        string $textColor
    ): string {
        $width = 760;
        $height = 320;

        $left = 52;
        $right = 22;
        $top = 24;
        $bottom = 68;

        $plotWidth =
            $width - $left - $right;

        $plotHeight =
            $height - $top - $bottom;

        $allValues = [];

        foreach ($data as $row) {
            foreach ($series as $item) {
                $allValues[] = (float) (
                    $row[
                        $item['key']
                        ?? ''
                    ]
                    ?? 0
                );
            }
        }

        $allPercent = collect($series)
            ->every(
                fn (array $item): bool =>
                    ($item['suffix'] ?? '')
                    === '%'
            );

        $maxValue = $allPercent
            ? 100.0
            : max(
                1.0,
                ceil(
                    max(
                        $allValues
                            ?: [1]
                    ) * 1.12
                )
            );

        $categoryCount = count($data);

        $categoryWidth = $plotWidth
            / max(
                1,
                $categoryCount
            );

        $groupWidth = min(
            $categoryWidth * 0.72,
            64
        );

        $barWidth = $groupWidth
            / max(
                1,
                count($series)
            );

        $parts = [
            $this->svgOpen(
                width: $width,
                height: $height,
                textColor: $textColor
            ),
        ];

        for (
            $tick = 0;
            $tick <= 4;
            $tick++
        ) {
            $value =
                ($maxValue / 4) * $tick;

            $gridY = $top
                + $plotHeight
                - (
                    $value
                    / $maxValue
                ) * $plotHeight;

            $parts[] = sprintf(
                '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="#dbe3ef" stroke-width="1"/>',
                $left,
                $this->number($gridY),
                $width - $right,
                $this->number($gridY)
            );

            $parts[] = sprintf(
                '<text x="%s" y="%s" text-anchor="end" font-size="10" fill="#64748b">%s%s</text>',
                $left - 8,
                $this->number(
                    $gridY + 3
                ),
                $this->formatValue($value),
                $allPercent
                    ? '%'
                    : ''
            );
        }

        foreach (
            $data
            as $dataIndex => $row
        ) {
            $groupX = $left
                + (
                    $dataIndex
                    * $categoryWidth
                )
                + (
                    (
                        $categoryWidth
                        - $groupWidth
                    ) / 2
                );

            foreach (
                $series
                as $seriesIndex => $item
            ) {
                $value = (float) (
                    $row[
                        $item['key']
                        ?? ''
                    ]
                    ?? 0
                );

                $barHeight = (
                    $value
                    / $maxValue
                ) * $plotHeight;

                $barX = $groupX
                    + (
                        $seriesIndex
                        * $barWidth
                    );

                $barY = $top
                    + $plotHeight
                    - $barHeight;

                $color = $colors[
                    $seriesIndex
                    % count($colors)
                ];

                $parts[] = sprintf(
                    '<rect x="%s" y="%s" width="%s" height="%s" rx="3" fill="%s"><title>%s: %s%s</title></rect>',
                    $this->number($barX),
                    $this->number($barY),
                    $this->number(
                        max(
                            3,
                            $barWidth - 3
                        )
                    ),
                    $this->number(
                        max(
                            0,
                            $barHeight
                        )
                    ),
                    $color,
                    $this->xml(
                        (string) (
                            $item['label']
                            ?? $item['key']
                            ?? 'Serie'
                        )
                    ),
                    $this->formatValue($value),
                    $this->xml(
                        (string) (
                            $item['suffix']
                            ?? ''
                        )
                    )
                );
            }

            $label = $this->shortLabel(
                (string) (
                    $row[
                        $chart['x_key']
                        ?? 'label'
                    ]
                    ?? ''
                ),
                12
            );

            $parts[] = sprintf(
                '<text x="%s" y="%s" text-anchor="middle" font-size="9" fill="#64748b">%s</text>',
                $this->number(
                    $left
                    + (
                        $dataIndex
                        * $categoryWidth
                    )
                    + (
                        $categoryWidth
                        / 2
                    )
                ),
                $height - 39,
                $this->xml($label)
            );
        }

        $legendX = $left;

        foreach (
            $series
            as $seriesIndex => $item
        ) {
            $color = $colors[
                $seriesIndex
                % count($colors)
            ];

            $label = $this->shortLabel(
                (string) (
                    $item['label']
                    ?? $item['key']
                    ?? 'Serie'
                ),
                25
            );

            $parts[] = sprintf(
                '<rect x="%s" y="%s" width="9" height="9" rx="2" fill="%s"/>',
                $legendX,
                $height - 18,
                $color
            );

            $parts[] = sprintf(
                '<text x="%s" y="%s" font-size="10" fill="#64748b">%s</text>',
                $legendX + 14,
                $height - 10,
                $this->xml($label)
            );

            $legendX += 150;
        }

        $parts[] = '</g></svg>';

        return implode('', $parts);
    }

    private function horizontalBarChart(
        array $chart,
        array $data,
        array $series,
        array $colors,
        string $textColor
    ): string {
        $width = 760;

        $left = 145;
        $right = 38;
        $top = 25;
        $bottom = 42;

        $seriesCount = max(
            1,
            count($series)
        );

        $rowHeight = max(
            34,
            17 * $seriesCount
            + 13
        );

        $height = max(
            260,
            $top
            + $bottom
            + (
                count($data)
                * $rowHeight
            )
        );

        $plotWidth =
            $width - $left - $right;

        $allValues = [];

        foreach ($data as $row) {
            foreach ($series as $item) {
                $allValues[] = (float) (
                    $row[
                        $item['key']
                        ?? ''
                    ]
                    ?? 0
                );
            }
        }

        $allPercent = collect($series)
            ->every(
                fn (array $item): bool =>
                    ($item['suffix'] ?? '')
                    === '%'
            );

        $maxValue = $allPercent
            ? 100.0
            : max(
                1.0,
                ceil(
                    max(
                        $allValues
                            ?: [1]
                    ) * 1.1
                )
            );

        $parts = [
            $this->svgOpen(
                width: $width,
                height: $height,
                textColor: $textColor
            ),
        ];

        for (
            $tick = 0;
            $tick <= 4;
            $tick++
        ) {
            $value =
                ($maxValue / 4) * $tick;

            $gridX = $left
                + (
                    $value
                    / $maxValue
                ) * $plotWidth;

            $parts[] = sprintf(
                '<line x1="%s" y1="%s" x2="%s" y2="%s" stroke="#dbe3ef" stroke-width="1"/>',
                $this->number($gridX),
                $top - 7,
                $this->number($gridX),
                $height - $bottom + 3
            );

            $parts[] = sprintf(
                '<text x="%s" y="%s" text-anchor="middle" font-size="9" fill="#64748b">%s%s</text>',
                $this->number($gridX),
                $height - 18,
                $this->formatValue($value),
                $allPercent
                    ? '%'
                    : ''
            );
        }

        foreach (
            $data
            as $rowIndex => $row
        ) {
            $baseY = $top
                + (
                    $rowIndex
                    * $rowHeight
                );

            $label = $this->shortLabel(
                (string) (
                    $row[
                        $chart['x_key']
                        ?? 'label'
                    ]
                    ?? ''
                ),
                24
            );

            $parts[] = sprintf(
                '<text x="%s" y="%s" text-anchor="end" font-size="10" fill="#64748b">%s</text>',
                $left - 10,
                $baseY + 12,
                $this->xml($label)
            );

            foreach (
                $series
                as $seriesIndex => $item
            ) {
                $value = (float) (
                    $row[
                        $item['key']
                        ?? ''
                    ]
                    ?? 0
                );

                $barWidth = (
                    $value
                    / $maxValue
                ) * $plotWidth;

                $barY = $baseY
                    + 3
                    + (
                        $seriesIndex
                        * 16
                    );

                $color = $colors[
                    $seriesIndex
                    % count($colors)
                ];

                $parts[] = sprintf(
                    '<rect x="%s" y="%s" width="%s" height="11" rx="3" fill="%s"><title>%s: %s%s</title></rect>',
                    $left,
                    $this->number($barY),
                    $this->number(
                        max(
                            0,
                            $barWidth
                        )
                    ),
                    $color,
                    $this->xml(
                        (string) (
                            $item['label']
                            ?? $item['key']
                            ?? 'Serie'
                        )
                    ),
                    $this->formatValue($value),
                    $this->xml(
                        (string) (
                            $item['suffix']
                            ?? ''
                        )
                    )
                );

                $parts[] = sprintf(
                    '<text x="%s" y="%s" font-size="9" fill="%s">%s%s</text>',
                    $this->number(
                        min(
                            $width - $right + 3,
                            $left
                            + $barWidth
                            + 5
                        )
                    ),
                    $this->number(
                        $barY + 9
                    ),
                    $textColor,
                    $this->formatValue($value),
                    $this->xml(
                        (string) (
                            $item['suffix']
                            ?? ''
                        )
                    )
                );
            }
        }

        $legendX = $left;

        foreach (
            $series
            as $seriesIndex => $item
        ) {
            $color = $colors[
                $seriesIndex
                % count($colors)
            ];

            $label = $this->shortLabel(
                (string) (
                    $item['label']
                    ?? $item['key']
                    ?? 'Serie'
                ),
                25
            );

            $parts[] = sprintf(
                '<rect x="%s" y="6" width="9" height="9" rx="2" fill="%s"/>',
                $legendX,
                $color
            );

            $parts[] = sprintf(
                '<text x="%s" y="14" font-size="10" fill="#64748b">%s</text>',
                $legendX + 14,
                $this->xml($label)
            );

            $legendX += 150;
        }

        $parts[] = '</g></svg>';

        return implode('', $parts);
    }

    private function svgOpen(
        int $width,
        int $height,
        string $textColor
    ): string {
        return sprintf(
            '<svg xmlns="http://www.w3.org/2000/svg" width="%d" height="%d" viewBox="0 0 %d %d"><rect width="100%%" height="100%%" fill="#ffffff"/><g font-family="DejaVu Sans, Arial, sans-serif" fill="%s">',
            $width,
            $height,
            $width,
            $height,
            $textColor
        );
    }

    private function formatValue(
        float $value
    ): string {
        if (
            abs(
                $value - round($value)
            ) < 0.00001
        ) {
            return number_format(
                $value,
                0,
                '.',
                ','
            );
        }

        return number_format(
            $value,
            1,
            '.',
            ','
        );
    }

    private function number(
        float|int $value
    ): string {
        return rtrim(
            rtrim(
                number_format(
                    (float) $value,
                    2,
                    '.',
                    ''
                ),
                '0'
            ),
            '.'
        );
    }

    private function shortLabel(
        string $value,
        int $max
    ): string {
        $value = trim($value);

        if (
            mb_strlen($value)
            <= $max
        ) {
            return $value;
        }

        return rtrim(
            mb_substr(
                $value,
                0,
                max(
                    1,
                    $max - 1
                )
            )
        ).'…';
    }

    private function xml(
        string $value
    ): string {
        return htmlspecialchars(
            $value,
            ENT_QUOTES
            | ENT_XML1,
            'UTF-8'
        );
    }

    private function safeColor(
        string $value,
        string $fallback
    ): string {
        return preg_match(
            '/^#[0-9a-f]{6}$/i',
            trim($value)
        )
            ? trim($value)
            : $fallback;
    }
}
