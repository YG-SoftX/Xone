<?php

namespace App\Services;

use App\Models\Cell;
use App\Models\Sheet;

class FormulaEngine
{
    /**
     * Evaluate a formula string and return the computed value.
     *
     * Supports: SUM, AVERAGE, COUNT, MAX, MIN, IF, CONCATENATE, ABS, ROUND, SQRT, POWER,
     * basic arithmetic (+, -, *, /), cell references (A1), ranges (A1:B10).
     *
     * @param string $formula The formula string (with or without leading =)
     * @param Sheet $sheet The sheet context for cell lookups
     * @return mixed The computed value
     * @throws \InvalidArgumentException
     */
    public static function evaluate(string $formula, Sheet $sheet): mixed
    {
        // Strip leading = if present
        $formula = trim($formula);
        if (str_starts_with($formula, '=')) {
            $formula = substr($formula, 1);
        }

        $formula = trim($formula);

        if ($formula === '') {
            return null;
        }

        // Pre-process: replace cell references and ranges with their values
        $processed = self::preprocessFormula($formula, $sheet);

        // Evaluate the processed formula
        return self::evalExpression($processed, $sheet);
    }

    /**
     * Get all cell addresses referenced in a formula.
     *
     * @param string $formula The formula string
     * @return array<string> Array of cell addresses (uppercase)
     */
    public static function getDependencies(string $formula): array
    {
        $formula = trim($formula);
        if (str_starts_with($formula, '=')) {
            $formula = substr($formula, 1);
        }

        $dependencies = [];

        // Match ranges like A1:B10 — expand them
        preg_match_all('/\b([A-Z]+\d+)\s*:\s*([A-Z]+\d+)\b/i', $formula, $rangeMatches, PREG_SET_ORDER);
        foreach ($rangeMatches as $match) {
            $cells = self::expandRange($match[1], $match[2]);
            $dependencies = array_merge($dependencies, $cells);
        }

        // Remove ranges from formula to find standalone references
        $formulaWithoutRanges = preg_replace('/\b[A-Z]+\d+\s*:\s*[A-Z]+\d+\b/i', '', $formula);

        // Match individual cell references like A1, B2, AA10
        preg_match_all('/\b([A-Z]+\d+)\b/i', $formulaWithoutRanges, $cellMatches, PREG_SET_ORDER);
        foreach ($cellMatches as $match) {
            $dependencies[] = strtoupper($match[1]);
        }

        return array_unique($dependencies);
    }

    /**
     * Preprocess the formula: replace cell references and function range arguments with actual values.
     */
    protected static function preprocessFormula(string $formula, Sheet $sheet): string
    {
        // Handle function calls with ranges: SUM(A1:A10), AVERAGE(A1:B5), etc.
        $formula = preg_replace_callback(
            '/\b(SUM|AVERAGE|COUNT|MAX|MIN)\s*\(\s*([A-Z]+\d+)\s*:\s*([A-Z]+\d+)\s*\)/i',
            function ($matches) use ($sheet) {
                $function = strtoupper($matches[1]);
                $start = strtoupper($matches[2]);
                $end = strtoupper($matches[3]);
                $values = self::getRangeValues($start, $end, $sheet);

                return self::applyFunction($function, $values);
            },
            $formula
        );

        // Handle CONCATENATE with cell references and ranges.
        // We use a balanced-parenthesis scanner instead of a regex to extract the
        // argument list — a lazy regex like (.+?) stops at the first ) it sees,
        // which breaks when string literals contain closing parentheses
        // e.g. =CONCATENATE(A1, "hello (world)").
        if (preg_match('/\bCONCATENATE\s*\(/i', $formula)) {
            $formula = self::replaceConcatenate($formula, $sheet);
        }

        // Handle IF(condition, trueVal, falseVal)
        $formula = preg_replace_callback(
            '/\bIF\s*\((.+?)\)/i',
            function ($matches) use ($sheet) {
                $args = self::splitArguments($matches[1]);
                if (count($args) < 3) {
                    throw new \InvalidArgumentException('IF function requires 3 arguments: condition, trueVal, falseVal');
                }

                $condition = self::evaluateCondition(trim($args[0]), $sheet);
                if ($condition) {
                    return self::resolveValue(trim($args[1]), $sheet);
                } else {
                    return self::resolveValue(trim($args[2]), $sheet);
                }
            },
            $formula
        );

        // Handle single-argument functions: ABS, ROUND, SQRT, POWER, COUNT
        $formula = preg_replace_callback(
            '/\b(ABS|ROUND|SQRT|POWER|COUNT)\s*\((.+?)\)/i',
            function ($matches) use ($sheet) {
                $function = strtoupper($matches[1]);
                $args = self::splitArguments($matches[2]);

                if ($function === 'POWER' && count($args) >= 2) {
                    $base = self::resolveNumericValue(trim($args[0]), $sheet);
                    $exponent = self::resolveNumericValue(trim($args[1]), $sheet);
                    return (string) pow($base, $exponent);
                }

                if ($function === 'ROUND' && count($args) >= 2) {
                    $value = self::resolveNumericValue(trim($args[0]), $sheet);
                    $precision = (int) self::resolveNumericValue(trim($args[1]), $sheet);
                    return (string) round($value, $precision);
                }

                if (count($args) >= 1) {
                    // Check if it's a range
                    $arg = trim($args[0]);
                    if (preg_match('/^([A-Z]+\d+)\s*:\s*([A-Z]+\d+)$/i', $arg, $rangeMatch)) {
                        $values = self::getRangeValues(strtoupper($rangeMatch[1]), strtoupper($rangeMatch[2]), $sheet);
                    } else {
                        $values = [self::resolveNumericValue($arg, $sheet)];
                    }

                    return self::applyFunction($function, $values);
                }

                throw new \InvalidArgumentException("{$function} function requires at least 1 argument");
            },
            $formula
        );

        // Replace remaining single cell references with their values
        $formula = preg_replace_callback(
            '/\b([A-Z]+\d+)\b/i',
            function ($matches) use ($sheet) {
                $cell = self::getCellByAddress($sheet, strtoupper($matches[1]));
                if ($cell === null) {
                    return '0';
                }
                if (is_string($cell)) {
                    return '"' . $cell . '"';
                }
                return (string) $cell;
            },
            $formula
        );

        return $formula;
    }

    /**
     * Evaluate a post-processed expression safely.
     */
    protected static function evalExpression(string $expression, Sheet $sheet): mixed
    {
        // If it's a quoted string, return it
        if (preg_match('/^"(.*)"$/', $expression, $m)) {
            return $m[1];
        }

        // If it's a plain number
        if (is_numeric($expression)) {
            return strpos($expression, '.') !== false ? (float) $expression : (int) $expression;
        }

        // Safely evaluate arithmetic expression
        // Only allow digits, decimal points, spaces, and basic operators
        $cleaned = preg_replace('/[^0-9\.\+\-\*\/\(\)\s]/', '', $expression);

        if ($cleaned === null || trim($cleaned) === '') {
            return $expression;
        }

        // Use a safe eval approach via parsing
        try {
            $result = self::safeMathEval($cleaned);
            return $result;
        } catch (\Exception $e) {
            throw new \InvalidArgumentException('Cannot evaluate expression: ' . $expression);
        }
    }

    /**
     * Safely evaluate a mathematical expression without using eval().
     */
    protected static function safeMathEval(string $expression): float|int
    {
        $expression = trim($expression);

        // Handle parentheses first
        while (preg_match('/\(([^()]+)\)/', $expression, $match)) {
            $innerResult = self::safeMathEval($match[1]);
            $expression = str_replace($match[0], (string) $innerResult, $expression);
        }

        // Handle multiplication and division (left to right)
        while (preg_match('/(-?\d+\.?\d*)\s*([*\/])\s*(-?\d+\.?\d*)/', $expression, $match)) {
            $left = (float) $match[1];
            $op = $match[2];
            $right = (float) $match[3];

            if ($op === '*' ) {
                $result = $left * $right;
            } else {
                if ($right == 0) {
                    throw new \InvalidArgumentException('Division by zero');
                }
                $result = $left / $right;
            }

            $expression = str_replace($match[0], (string) $result, $expression);
        }

        // Handle addition and subtraction (left to right)
        while (preg_match('/(-?\d+\.?\d*)\s*([+\-])\s*(-?\d+\.?\d*)/', $expression, $match)) {
            $left = (float) $match[1];
            $op = $match[2];
            $right = (float) $match[3];

            if ($op === '+') {
                $result = $left + $right;
            } else {
                $result = $left - $right;
            }

            $expression = str_replace($match[0], (string) $result, $expression);
        }

        $result = (float) $expression;
        return ($result == (int) $result) ? (int) $result : $result;
    }

    /**
     * Get cell values within a range.
     *
     * @return array<mixed>
     */
    protected static function getRangeValues(string $start, string $end, Sheet $sheet): array
    {
        $cells = $sheet->getRange($start, $end);
        $values = [];

        foreach ($cells as $cell) {
            $value = $cell->computed_value ?? $cell->value;
            if ($value !== null && $value !== '') {
                $values[] = $value;
            }
        }

        return $values;
    }

    /**
     * Apply a statistical function to an array of values.
     */
    protected static function applyFunction(string $function, array $values): string
    {
        $numericValues = array_filter($values, fn($v) => is_numeric($v));

        return match ($function) {
            'SUM' => (string) array_sum($numericValues),
            'AVERAGE' => count($numericValues) > 0 ? (string) (array_sum($numericValues) / count($numericValues)) : '0',
            'COUNT' => (string) count($numericValues),
            'MAX' => count($numericValues) > 0 ? (string) max($numericValues) : '0',
            'MIN' => count($numericValues) > 0 ? (string) min($numericValues) : '0',
            'ABS' => count($values) > 0 ? (string) abs((float) $values[0]) : '0',
            default => '0',
        };
    }

    /**
     * Replace all CONCATENATE(...) calls in a formula using a balanced-paren
     * scanner so that ) inside quoted string literals does not terminate the match.
     */
    protected static function replaceConcatenate(string $formula, Sheet $sheet): string
    {
        $result = '';
        $i      = 0;
        $len    = strlen($formula);

        while ($i < $len) {
            // Look for CONCATENATE( (case-insensitive)
            if (stripos($formula, 'CONCATENATE(', $i) === $i) {
                $openPos = $i + strlen('CONCATENATE(');
                $depth   = 1;
                $inQuote = false;
                $j       = $openPos;

                while ($j < $len && $depth > 0) {
                    $c = $formula[$j];

                    if ($c === '"' && ($j === 0 || $formula[$j - 1] !== '\\')) {
                        $inQuote = !$inQuote;
                    }

                    if (!$inQuote) {
                        if ($c === '(') $depth++;
                        elseif ($c === ')') $depth--;
                    }

                    $j++;
                }

                // $j now points one past the closing )
                $argsString = substr($formula, $openPos, $j - $openPos - 1);
                $args       = self::splitArguments($argsString);
                $concat     = '';

                foreach ($args as $arg) {
                    $arg = trim($arg);
                    if (preg_match('/^([A-Z]+\d+)\s*:\s*([A-Z]+\d+)$/i', $arg, $rangeMatch)) {
                        $values = self::getRangeValues(strtoupper($rangeMatch[1]), strtoupper($rangeMatch[2]), $sheet);
                        $concat .= implode('', $values);
                    } elseif (preg_match('/^([A-Z]+\d+)$/i', $arg, $cellMatch)) {
                        $concat .= (string) (self::getCellByAddress($sheet, strtoupper($cellMatch[1])) ?? '');
                    } elseif (preg_match('/^"(.*)"$/s', $arg, $strMatch)) {
                        $concat .= $strMatch[1];
                    } elseif (is_numeric($arg)) {
                        $concat .= $arg;
                    }
                }

                $result .= '"' . $concat . '"';
                $i = $j;
            } else {
                $result .= $formula[$i];
                $i++;
            }
        }

        return $result;
    }

    /**
     * Split function arguments respecting quoted strings and parentheses.
     *
     * @return array<string>
     */
    protected static function splitArguments(string $argsString): array
    {
        $args = [];
        $current = '';
        $depth = 0;
        $inQuotes = false;

        for ($i = 0; $i < strlen($argsString); $i++) {
            $char = $argsString[$i];

            if ($char === '"' && ($i === 0 || $argsString[$i - 1] !== '\\')) {
                $inQuotes = !$inQuotes;
            }

            if (!$inQuotes) {
                if ($char === '(') {
                    $depth++;
                } elseif ($char === ')') {
                    $depth--;
                } elseif ($char === ',' && $depth === 0) {
                    $args[] = $current;
                    $current = '';
                    continue;
                }
            }

            $current .= $char;
        }

        if ($current !== '') {
            $args[] = $current;
        }

        return $args;
    }

    /**
     * Evaluate a condition string (for IF function).
     */
    protected static function evaluateCondition(string $condition, Sheet $sheet): bool
    {
        // Replace cell references
        $condition = preg_replace_callback(
            '/\b([A-Z]+\d+)\b/i',
            function ($matches) use ($sheet) {
                $cell = self::getCellByAddress($sheet, strtoupper($matches[1]));
                if ($cell === null) return '0';
                if (is_string($cell)) return '"' . $cell . '"';
                return (string) $cell;
            },
            $condition
        );

        // Handle comparison operators
        if (preg_match('/^(.+?)\s*(>=|<=|!=|<>|>|<|=)\s*(.+)$/', $condition, $matches)) {
            $left = trim($matches[1]);
            $op = $matches[2];
            $right = trim($matches[3]);

            // Resolve quoted strings
            $leftVal = self::resolveValue($left, $sheet);
            $rightVal = self::resolveValue($right, $sheet);

            if ($op === '<>') $op = '!=';

            return match ($op) {
                '>=' => $leftVal >= $rightVal,
                '<=' => $leftVal <= $rightVal,
                '>' => $leftVal > $rightVal,
                '<' => $leftVal < $rightVal,
                '=' => $leftVal == $rightVal,
                '!=' => $leftVal != $rightVal,
                default => false,
            };
        }

        // If it's just a value, treat as truthy
        $value = self::resolveValue($condition, $sheet);
        return (bool) $value;
    }

    /**
     * Resolve a value string (cell ref, number, quoted string).
     */
    protected static function resolveValue(string $value, Sheet $sheet): mixed
    {
        $value = trim($value);

        // Quoted string
        if (preg_match('/^"(.*)"$/', $value, $m)) {
            return $m[1];
        }

        // Cell reference
        if (preg_match('/^([A-Z]+\d+)$/i', $value, $m)) {
            return self::getCellByAddress($sheet, strtoupper($m[1]));
        }

        // Numeric
        if (is_numeric($value)) {
            return strpos($value, '.') !== false ? (float) $value : (int) $value;
        }

        return $value;
    }

    /**
     * Resolve a numeric value (for math functions).
     */
    protected static function resolveNumericValue(string $value, Sheet $sheet): float
    {
        $resolved = self::resolveValue($value, $sheet);

        if (is_null($resolved)) return 0;
        if (is_bool($resolved)) return $resolved ? 1 : 0;
        if (is_numeric($resolved)) return (float) $resolved;

        return 0;
    }

    /**
     * Get the value of a cell by its address.
     */
    protected static function getCellByAddress(Sheet $sheet, string $address): mixed
    {
        $cell = Cell::where('sheet_id', $sheet->id)
            ->where('cell_address', $address)
            ->first();

        if (!$cell) {
            return null;
        }

        return $cell->computed_value ?? $cell->value;
    }

    /**
     * Expand a range into individual cell addresses.
     *
     * @return array<string>
     */
    protected static function expandRange(string $start, string $end): array
    {
        $startParsed = Cell::parseAddress($start);
        $endParsed = Cell::parseAddress($end);

        $minRow = min($startParsed['row'], $endParsed['row']);
        $maxRow = max($startParsed['row'], $endParsed['row']);
        $minCol = min($startParsed['column'], $endParsed['column']);
        $maxCol = max($startParsed['column'], $endParsed['column']);

        $cells = [];
        for ($row = $minRow; $row <= $maxRow; $row++) {
            for ($col = $minCol; $col <= $maxCol; $col++) {
                $cells[] = Cell::getColumnLetter($col) . $row;
            }
        }

        return $cells;
    }
}
