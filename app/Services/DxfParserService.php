<?php

namespace App\Services;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class DxfParserService
{
    /**
     * Maximum allowed file size in bytes (10 MB).
     */
    protected const MAX_FILE_SIZE = 10 * 1024 * 1024;

    /**
     * Validate and extract layer summary from a DXF file.
     *
     * @param UploadedFile|string $file
     * @return array
     * @throws ValidationException
     */
    public function getLayerSummary(UploadedFile|string $file): array
    {
        $content = $this->readAndValidateDxfContent($file);
        $entities = $this->parseEntities($content);

        $polylineCounts = [];
        $textCounts = [];
        $layers = [];

        foreach ($entities['polylines'] as $poly) {
            $layer = $poly['layer'] ?? '0';
            $layers[$layer] = true;
            $polylineCounts[$layer] = ($polylineCounts[$layer] ?? 0) + 1;
        }

        foreach ($entities['texts'] as $txt) {
            $layer = $txt['layer'] ?? '0';
            $layers[$layer] = true;
            $textCounts[$layer] = ($textCounts[$layer] ?? 0) + 1;
        }

        // Also extract layers defined in TABLES section if available
        $tableLayers = $this->extractTableLayers($content);
        foreach ($tableLayers as $tLayer) {
            $layers[$tLayer] = true;
        }

        $allLayers = array_values(array_unique(array_keys($layers)));
        sort($allLayers);

        return [
            'layers_found' => $allLayers,
            'polyline_count_per_layer' => $polylineCounts,
            'text_count_per_layer' => $textCounts,
        ];
    }

    /**
     * Parse closed polylines from the selected rooms layer and associate with labels from labels layer.
     *
     * @param UploadedFile|string $file
     * @param string $roomsLayer
     * @param string|null $labelsLayer
     * @return array
     * @throws ValidationException
     */
    public function parseRooms(UploadedFile|string $file, string $roomsLayer, ?string $labelsLayer = null): array
    {
        $content = $this->readAndValidateDxfContent($file);
        $entities = $this->parseEntities($content);

        // Filter polylines on the requested rooms layer
        $roomPolylines = array_values(array_filter($entities['polylines'], function ($poly) use ($roomsLayer) {
            return strcasecmp($poly['layer'] ?? '', $roomsLayer) === 0;
        }));

        if (empty($roomPolylines)) {
            throw ValidationException::withMessages([
                'rooms_layer' => [__('messages.dxf_no_rooms_found')],
            ]);
        }

        // Filter label texts
        $labelTexts = [];
        if (!empty($labelsLayer)) {
            $labelTexts = array_values(array_filter($entities['texts'], function ($txt) use ($labelsLayer) {
                return strcasecmp($txt['layer'] ?? '', $labelsLayer) === 0;
            }));
        }

        $rooms = [];
        $assignedTextIndices = [];

        foreach ($roomPolylines as $idx => $poly) {
            $vertices = $poly['vertices'] ?? [];
            if (count($vertices) < 3) {
                continue;
            }

            // Calculate Area via Shoelace Formula
            $area = $this->calculateShoelaceArea($vertices);
            $perimeter = $this->calculatePerimeter($vertices);
            $centroid = $this->calculateCentroid($vertices);

            // Determine Room Name
            $roomName = $this->matchBestLabel($vertices, $centroid, $labelTexts, $assignedTextIndices);
            if (empty($roomName)) {
                $roomName = "غرفة مستوردة #" . ($idx + 1);
            }

            // Detect regular orthogonal rectangular dimensions if applicable
            $length = null;
            $width = null;
            if (count($vertices) === 4) {
                $d1 = sqrt(pow($vertices[1]['x'] - $vertices[0]['x'], 2) + pow($vertices[1]['y'] - $vertices[0]['y'], 2));
                $d2 = sqrt(pow($vertices[2]['x'] - $vertices[1]['x'], 2) + pow($vertices[2]['y'] - $vertices[1]['y'], 2));
                if ($d1 > 0 && $d2 > 0 && abs(($d1 * $d2) - $area) < 0.1) {
                    $length = round(max($d1, $d2), 2);
                    $width = round(min($d1, $d2), 2);
                }
            }

            $rooms[] = [
                'room_name' => $this->sanitizeText($roomName),
                'area' => round($area, 2),
                'perimeter' => round($perimeter, 2),
                'length' => $length,
                'width' => $width,
                'centroid' => $centroid,
                'vertex_count' => count($vertices),
                'unit' => 'm2',
                'measurement_type' => 'area',
                'notes' => $length && $width
                    ? "مستورد من DXF: أبعاد هندسية {$length} × {$width} م (مساحة: " . round($area, 2) . " م²)."
                    : "مستورد من DXF: مساحة مضلع غير منتظم = " . round($area, 2) . " م² (معادلة Shoelace).",
            ];
        }

        return $rooms;
    }

    /**
     * Calculate Polygon Area using the exact Shoelace formula.
     *
     * Area = 0.5 * |∑(x_i * y_{i+1} - x_{i+1} * y_i)|
     *
     * @param array $vertices Array of ['x' => float, 'y' => float]
     * @return float
     */
    public function calculateShoelaceArea(array $vertices): float
    {
        $n = count($vertices);
        if ($n < 3) {
            return 0.0;
        }

        $sum = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $current = $vertices[$i];
            $next = $vertices[($i + 1) % $n];

            $sum += ($current['x'] * $next['y']) - ($next['x'] * $current['y']);
        }

        return abs($sum) / 2.0;
    }

    /**
     * Calculate Polygon Perimeter.
     *
     * @param array $vertices
     * @return float
     */
    public function calculatePerimeter(array $vertices): float
    {
        $n = count($vertices);
        if ($n < 2) {
            return 0.0;
        }

        $perimeter = 0.0;
        for ($i = 0; $i < $n; $i++) {
            $current = $vertices[$i];
            $next = $vertices[($i + 1) % $n];

            $dx = $next['x'] - $current['x'];
            $dy = $next['y'] - $current['y'];
            $perimeter += sqrt(($dx * $dx) + ($dy * $dy));
        }

        return $perimeter;
    }

    /**
     * Calculate Centroid of a polygon.
     *
     * @param array $vertices
     * @return array ['x' => float, 'y' => float]
     */
    public function calculateCentroid(array $vertices): array
    {
        $n = count($vertices);
        if ($n === 0) {
            return ['x' => 0.0, 'y' => 0.0];
        }

        // Use standard centroid for polygon if non-degenerate, otherwise average
        $cx = 0.0;
        $cy = 0.0;
        $signedArea = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $curr = $vertices[$i];
            $next = $vertices[($i + 1) % $n];

            $cross = ($curr['x'] * $next['y']) - ($next['x'] * $curr['y']);
            $signedArea += $cross;
            $cx += ($curr['x'] + $next['x']) * $cross;
            $cy += ($curr['y'] + $next['y']) * $cross;
        }

        $signedArea *= 0.5;

        if (abs($signedArea) > 1e-6) {
            $cx /= (6.0 * $signedArea);
            $cy /= (6.0 * $signedArea);
            return ['x' => $cx, 'y' => $cy];
        }

        // Fallback: simple arithmetic mean of vertices
        $sumX = array_sum(array_column($vertices, 'x'));
        $sumY = array_sum(array_column($vertices, 'y'));

        return [
            'x' => $sumX / $n,
            'y' => $sumY / $n,
        ];
    }

    /**
     * Determine if a 2D point is inside a polygon (Ray-Casting Algorithm).
     *
     * @param float $px
     * @param float $py
     * @param array $vertices
     * @return bool
     */
    public function isPointInPolygon(float $px, float $py, array $vertices): bool
    {
        $n = count($vertices);
        $inside = false;

        for ($i = 0, $j = $n - 1; $i < $n; $j = $i++) {
            $xi = $vertices[$i]['x'];
            $yi = $vertices[$i]['y'];
            $xj = $vertices[$j]['x'];
            $yj = $vertices[$j]['y'];

            $intersect = (($yi > $py) !== ($yj > $py))
                && ($px < ($xj - $xi) * ($py - $yi) / (($yj - $yi) ?: 1e-9) + $xi);

            if ($intersect) {
                $inside = !$inside;
            }
        }

        return $inside;
    }

    /**
     * Match the best label text for a polygon.
     */
    protected function matchBestLabel(array $vertices, array $centroid, array $labelTexts, array &$assignedIndices): ?string
    {
        if (empty($labelTexts)) {
            return null;
        }

        // 1. First priority: Text whose insertion point is strictly inside polygon
        $bestInsideIndex = null;
        $minInsideDist = PHP_FLOAT_MAX;

        foreach ($labelTexts as $idx => $txt) {
            if (in_array($idx, $assignedIndices, true)) {
                continue;
            }

            $tx = $txt['x'];
            $ty = $txt['y'];

            if ($this->isPointInPolygon($tx, $ty, $vertices)) {
                $dist = sqrt(pow($tx - $centroid['x'], 2) + pow($ty - $centroid['y'], 2));
                if ($dist < $minInsideDist) {
                    $minInsideDist = $dist;
                    $bestInsideIndex = $idx;
                }
            }
        }

        if ($bestInsideIndex !== null) {
            $assignedIndices[] = $bestInsideIndex;
            return $labelTexts[$bestInsideIndex]['text'];
        }

        // 2. Second priority: Nearest unassigned text to centroid
        $bestIndex = null;
        $minDist = PHP_FLOAT_MAX;

        foreach ($labelTexts as $idx => $txt) {
            if (in_array($idx, $assignedIndices, true)) {
                continue;
            }

            $tx = $txt['x'];
            $ty = $txt['y'];
            $dist = sqrt(pow($tx - $centroid['x'], 2) + pow($ty - $centroid['y'], 2));

            if ($dist < $minDist) {
                $minDist = $dist;
                $bestIndex = $idx;
            }
        }

        if ($bestIndex !== null) {
            $assignedIndices[] = $bestIndex;
            return $labelTexts[$bestIndex]['text'];
        }

        return null;
    }

    /**
     * Read and validate DXF file contents.
     *
     * @param UploadedFile|string $file
     * @return string
     * @throws ValidationException
     */
    public function readAndValidateDxfContent(UploadedFile|string $file): string
    {
        $filePath = null;
        $originalName = '';
        $fileSize = 0;

        if ($file instanceof UploadedFile) {
            $filePath = $file->getRealPath();
            $originalName = $file->getClientOriginalName();
            $fileSize = $file->getSize();
        } elseif (is_string($file) && file_exists($file)) {
            $filePath = $file;
            $originalName = basename($file);
            $fileSize = filesize($file);
        } else {
            throw ValidationException::withMessages([
                'file' => [__('messages.dxf_invalid_format')],
            ]);
        }

        // Validate Extension
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        if ($extension === 'dwg' || $extension === 'pdf') {
            throw ValidationException::withMessages([
                'file' => [__('messages.dxf_unsupported_format')],
            ]);
        }

        if ($extension !== 'dxf') {
            throw ValidationException::withMessages([
                'file' => [__('messages.dxf_unsupported_format')],
            ]);
        }

        // Validate File Size (Max 10MB)
        if ($fileSize > self::MAX_FILE_SIZE) {
            throw ValidationException::withMessages([
                'file' => ['حجم الملف يتجاوز الحد الأقصى المسموح به (10 ميجابايت).'],
            ]);
        }

        $content = @file_get_contents($filePath);
        if ($content === false || strlen(trim($content)) === 0) {
            throw ValidationException::withMessages([
                'file' => [__('messages.dxf_invalid_format')],
            ]);
        }

        // Validate that content is a valid ASCII DXF structure
        if (!$this->isDxfSignatureValid($content)) {
            throw ValidationException::withMessages([
                'file' => [__('messages.dxf_invalid_format')],
            ]);
        }

        return $content;
    }

    /**
     * Check if content contains typical DXF ASCII markers.
     */
    public function isDxfSignatureValid(string $content): bool
    {
        $hasSection = (stripos($content, 'SECTION') !== false);
        $hasEntitiesOrHeader = (stripos($content, 'ENTITIES') !== false) || (stripos($content, 'HEADER') !== false);
        $hasEof = (stripos($content, 'EOF') !== false);

        // Check if file looks binary or contains non-ASCII characters in header
        $sample = substr($content, 0, 500);
        if (preg_match('/^AutoCAD Binary DXF/i', $sample)) {
            return false; // Binary DXF is not supported, must be ASCII
        }

        return $hasSection && $hasEntitiesOrHeader && $hasEof;
    }

    /**
     * Parse entities from DXF content (LWPOLYLINE, TEXT, MTEXT, LINE).
     *
     * @param string $content
     * @return array ['polylines' => [], 'texts' => [], 'lines' => []]
     */
    public function parseEntities(string $content): array
    {
        $lines = preg_split("/\r\n|\n|\r/", $content);
        $count = count($lines);

        $polylines = [];
        $texts = [];
        $otherLines = [];

        $inEntitiesSection = false;
        $i = 0;

        while ($i < $count - 1) {
            $code = trim($lines[$i]);
            $val = isset($lines[$i + 1]) ? trim($lines[$i + 1]) : '';

            // Detect ENTITIES section start/end
            if ($code === '0' && strtoupper($val) === 'SECTION') {
                $nextCode = isset($lines[$i + 2]) ? trim($lines[$i + 2]) : '';
                $nextVal = isset($lines[$i + 3]) ? trim($lines[$i + 3]) : '';
                if ($nextCode === '2' && strtoupper($nextVal) === 'ENTITIES') {
                    $inEntitiesSection = true;
                    $i += 4;
                    continue;
                }
            }

            if ($inEntitiesSection && $code === '0' && strtoupper($val) === 'ENDSEC') {
                $inEntitiesSection = false;
                $i += 2;
                continue;
            }

            if ($inEntitiesSection && $code === '0') {
                $entityType = strtoupper($val);

                if ($entityType === 'LWPOLYLINE') {
                    $entityData = $this->parseLwPolyline($lines, $i);
                    if (!empty($entityData['vertices'])) {
                        $polylines[] = $entityData;
                    }
                    continue;
                }

                if ($entityType === 'TEXT' || $entityType === 'MTEXT') {
                    $entityData = $this->parseTextEntity($lines, $i);
                    if (!empty($entityData['text'])) {
                        $texts[] = $entityData;
                    }
                    continue;
                }

                if ($entityType === 'LINE') {
                    $entityData = $this->parseLineEntity($lines, $i);
                    if ($entityData) {
                        $otherLines[] = $entityData;
                    }
                    continue;
                }
            }

            $i += 2;
        }

        return [
            'polylines' => $polylines,
            'texts' => $texts,
            'lines' => $otherLines,
        ];
    }

    /**
     * Parse LWPOLYLINE entity.
     */
    protected function parseLwPolyline(array $lines, int &$i): array
    {
        $i += 2; // Move past 0 \n LWPOLYLINE
        $layer = '0';
        $flags = 0;
        $vertices = [];
        $currentX = null;
        $count = count($lines);

        while ($i < $count - 1) {
            $code = trim($lines[$i]);
            $val = trim($lines[$i + 1]);

            // Next entity begins with 0
            if ($code === '0') {
                break;
            }

            if ($code === '8') {
                $layer = $val;
            } elseif ($code === '70') {
                $flags = (int) $val;
            } elseif ($code === '10') {
                $currentX = (float) $val;
            } elseif ($code === '20') {
                if ($currentX !== null) {
                    $vertices[] = ['x' => $currentX, 'y' => (float) $val];
                    $currentX = null;
                }
            }

            $i += 2;
        }

        // Clean up duplicate closing vertex if last equals first
        if (count($vertices) > 2) {
            $first = $vertices[0];
            $last = $vertices[count($vertices) - 1];
            if (abs($first['x'] - $last['x']) < 1e-6 && abs($first['y'] - $last['y']) < 1e-6) {
                array_pop($vertices);
            }
        }

        return [
            'layer' => $layer,
            'closed' => ($flags & 1) === 1,
            'vertices' => $vertices,
        ];
    }

    /**
     * Parse TEXT / MTEXT entity.
     */
    protected function parseTextEntity(array $lines, int &$i): array
    {
        $i += 2; // Move past 0 \n TEXT
        $layer = '0';
        $text = '';
        $x = 0.0;
        $y = 0.0;
        $count = count($lines);

        while ($i < $count - 1) {
            $code = trim($lines[$i]);
            $val = trim($lines[$i + 1]);

            if ($code === '0') {
                break;
            }

            if ($code === '8') {
                $layer = $val;
            } elseif ($code === '1' || $code === '3') {
                $text .= $val;
            } elseif ($code === '10') {
                $x = (float) $val;
            } elseif ($code === '20') {
                $y = (float) $val;
            }

            $i += 2;
        }

        return [
            'layer' => $layer,
            'text' => $this->cleanMtextString($text),
            'x' => $x,
            'y' => $y,
        ];
    }

    /**
     * Parse LINE entity.
     */
    protected function parseLineEntity(array $lines, int &$i): ?array
    {
        $i += 2;
        $layer = '0';
        $x1 = 0.0;
        $y1 = 0.0;
        $x2 = 0.0;
        $y2 = 0.0;
        $count = count($lines);

        while ($i < $count - 1) {
            $code = trim($lines[$i]);
            $val = trim($lines[$i + 1]);

            if ($code === '0') {
                break;
            }

            if ($code === '8') {
                $layer = $val;
            } elseif ($code === '10') {
                $x1 = (float) $val;
            } elseif ($code === '20') {
                $y1 = (float) $val;
            } elseif ($code === '11') {
                $x2 = (float) $val;
            } elseif ($code === '21') {
                $y2 = (float) $val;
            }

            $i += 2;
        }

        return [
            'layer' => $layer,
            'start' => ['x' => $x1, 'y' => $y1],
            'end' => ['x' => $x2, 'y' => $y2],
        ];
    }

    /**
     * Extract Layer names from TABLES section.
     */
    protected function extractTableLayers(string $content): array
    {
        $layers = [];
        $pattern = '/0\s+LAYER\s+2\s+([^\r\n]+)/i';
        if (preg_match_all($pattern, $content, $matches)) {
            foreach ($matches[1] as $match) {
                $layers[] = trim($match);
            }
        }
        return $layers;
    }

    /**
     * Strip AutoCAD MTEXT formatting tags (e.g. \A1;, \P, font specifications, curly braces).
     */
    public function cleanMtextString(string $text): string
    {
        // Replace paragraph breaks with space
        $text = str_replace(['\P', '\p', '\X', '\x'], ' ', $text);

        // Remove format overrides like \A1;, \C1;, \fArial|b0|i0|c0|p34;
        $text = preg_replace('/\\\\[A-Za-z0-9]+;/', '', $text);
        $text = preg_replace('/\\\\[A-Za-z0-9]+/', '', $text);

        // Remove curly braces
        $text = str_replace(['{', '}'], '', $text);

        return trim(preg_replace('/\s+/', ' ', $text));
    }

    /**
     * Sanitize string against XSS or control characters.
     */
    public function sanitizeText(?string $text): string
    {
        if ($text === null) {
            return '';
        }
        return strip_tags(trim($text));
    }
}
