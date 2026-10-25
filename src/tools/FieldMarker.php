<?php

declare(strict_types=1);

namespace tommyknocker\struct\tools;

use PhpToken;
use tommyknocker\struct\Struct;

/** Static, namespace-aware scaffolding. Target files are never loaded. */
final class FieldMarker
{
    private const TRIVIA = [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT];

    public function addMarkers(string $source): string
    {
        $tokens = [];
        foreach (PhpToken::tokenize($source, TOKEN_PARSE) as $token) {
            $tokens[] = $token;
        }
        $classes = $this->classes($tokens);
        $parents = [];
        foreach ($classes as $class) {
            $parents[$class['name']] = $class['parent'];
        }
        $edits = [];
        foreach ($classes as $class) {
            $parent = $class['parent'];
            $visited = [];
            while (isset($parents[$parent]) && !isset($visited[$parent])) {
                $visited[$parent] = true;
                $parent = $parents[$parent];
            }
            if ($parent === strtolower(Struct::class)) {
                $edits += $this->propertyEdits($source, $tokens, $class['body']);
            }
        }
        krsort($edits);
        foreach ($edits as $position => $text) {
            $source = substr_replace($source, $text, $position, 0);
        }
        PhpToken::tokenize($source, TOKEN_PARSE);

        return $source;
    }

    /** @param list<PhpToken> $tokens
     * @return list<array{name: string, parent: string, body: int}>
     */
    private function classes(array $tokens): array
    {
        $namespace = '';
        $namespaceDepth = 0;
        $depth = 0;
        $aliases = [];
        $classes = [];
        $count = count($tokens);
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($token->id === T_NAMESPACE && $depth === 0) {
                $name = '';
                for ($j = $i + 1; $j < $count && !in_array($tokens[$j]->text, [';', '{'], true); $j++) {
                    if (!in_array($tokens[$j]->id, self::TRIVIA, true)) {
                        $name .= $tokens[$j]->text;
                    }
                }
                $namespace = trim($name, '\\');
                $aliases = [];
                $namespaceDepth = ($tokens[$j]->text ?? '') === '{' ? 1 : 0;
                $depth = $namespaceDepth;
                $i = $j;

                continue;
            }
            if ($token->id === T_USE && $depth === $namespaceDepth) {
                $next = $this->next($tokens, $i + 1);
                // A closure's use(...) is not a namespace import.
                if (($tokens[$next]->text ?? '') !== '(') {
                    $statement = '';
                    for ($j = $i + 1; $j < $count && $tokens[$j]->text !== ';'; $j++) {
                        $statement .= in_array($tokens[$j]->id, self::TRIVIA, true) ? ' ' : $tokens[$j]->text;
                    }
                    $aliases = array_replace($aliases, $this->imports($statement));
                    $i = $j;

                    continue;
                }
            }
            if ($token->id === T_CLASS) {
                $nameIndex = $this->next($tokens, $i + 1);
                // Skip anonymous classes and Foo::class tokens.
                if (($tokens[$nameIndex]->id ?? null) === T_STRING) {
                    $parent = '';
                    for ($j = $nameIndex + 1; $j < $count && $tokens[$j]->text !== '{'; $j++) {
                        if ($tokens[$j]->id === T_EXTENDS) {
                            $parentIndex = $this->next($tokens, $j + 1);
                            $parent = $this->resolve($tokens[$parentIndex]->text, $namespace, $aliases);
                        }
                    }
                    if ($parent !== '' && $j < $count) {
                        $classes[] = [
                            'name' => strtolower(ltrim($namespace . '\\' . $tokens[$nameIndex]->text, '\\')),
                            'parent' => strtolower($parent),
                            'body' => $j,
                        ];
                    }
                }
            }
            if ($token->text === '{' || in_array($token->id, [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true)) {
                $depth++;
            } elseif ($token->text === '}') {
                $depth--;
                if ($namespaceDepth === 1 && $depth === 0) {
                    $namespace = '';
                    $namespaceDepth = 0;
                    $aliases = [];
                }
            }
        }

        return $classes;
    }

    /** @return array<string, string> */
    private function imports(string $statement): array
    {
        $statement = trim($statement);
        if (preg_match('/^(function|const)\s/i', $statement)) {
            return [];
        }
        $prefix = '';
        if (($brace = strpos($statement, '{')) !== false) {
            $prefix = rtrim(trim(substr($statement, 0, $brace)), '\\') . '\\';
            $statement = substr($statement, $brace + 1, (int) strrpos($statement, '}') - $brace - 1);
        }
        $aliases = [];
        foreach (explode(',', $statement) as $part) {
            $part = trim($part);
            if ($part === '' || preg_match('/^(function|const)\s/i', $part)) {
                continue;
            }
            $parts = preg_split('/\s+as\s+/i', $part);
            if ($parts === false) {
                continue;
            }
            $name = ltrim($prefix . trim($parts[0]), '\\');
            $segments = explode('\\', $name);
            $alias = trim($parts[1] ?? $segments[count($segments) - 1]);
            $aliases[strtolower($alias)] = $name;
        }

        return $aliases;
    }

    /** @param array<string, string> $aliases */
    private function resolve(string $name, string $namespace, array $aliases): string
    {
        if (str_starts_with($name, '\\')) {
            return ltrim($name, '\\');
        }
        if (str_starts_with(strtolower($name), 'namespace\\')) {
            return ltrim($namespace . '\\' . substr($name, 10), '\\');
        }
        $parts = explode('\\', $name);
        if (isset($aliases[strtolower($parts[0])])) {
            $parts[0] = $aliases[strtolower($parts[0])];

            return implode('\\', $parts);
        }

        return ltrim($namespace . '\\' . $name, '\\');
    }

    /** @param list<PhpToken> $tokens */
    private function next(array $tokens, int $index): int
    {
        while (isset($tokens[$index]) && in_array($tokens[$index]->id, self::TRIVIA, true)) {
            $index++;
        }

        return $index;
    }

    /** @param list<PhpToken> $tokens
     * @return array<int, string>
     */
    private function propertyEdits(string $source, array $tokens, int $body): array
    {
        $depth = 1;
        $attributed = false;
        $edits = [];
        $count = count($tokens);
        for ($i = $body + 1; $i < $count && $depth > 0; $i++) {
            $token = $tokens[$i];
            if ($token->id === T_ATTRIBUTE && $depth === 1) {
                $attributed = true;
            }
            if ($token->text === '{' || in_array($token->id, [T_CURLY_OPEN, T_DOLLAR_OPEN_CURLY_BRACES], true)) {
                $depth++;
            } elseif ($token->text === '}') {
                $depth--;
                if ($depth === 1) {
                    $attributed = false;
                }
            } elseif ($depth === 1 && $token->text === ';') {
                $attributed = false;
            } elseif ($depth === 1 && $token->id === T_PUBLIC) {
                $readonly = false;
                $variables = 0;
                $property = true;
                for ($j = $i + 1; $j < $count && $tokens[$j]->text !== ';'; $j++) {
                    if (in_array($tokens[$j]->id, [T_FUNCTION, T_STATIC, T_CONST], true)) {
                        $property = false;

                        break;
                    }
                    $readonly = $readonly || $tokens[$j]->id === T_READONLY;
                    $variables += $tokens[$j]->id === T_VARIABLE ? 1 : 0;
                }
                if (!$attributed && $property && $readonly && $variables === 1) {
                    $start = strrpos(substr($source, 0, $token->pos), "\n");
                    $prefix = substr($source, $start === false ? 0 : $start + 1, $token->pos - ($start === false ? 0 : $start + 1));
                    $indent = preg_match('/^\s*$/', $prefix) ? $prefix : '';
                    $edits[$token->pos] = '#[\\tommyknocker\\struct\\Field]' . (str_contains($source, "\r\n") ? "\r\n" : "\n") . $indent;
                }
                $attributed = false;
            }
        }

        return $edits;
    }
}
