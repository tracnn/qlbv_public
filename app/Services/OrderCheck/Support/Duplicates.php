<?php

namespace App\Services\OrderCheck\Support;

class Duplicates
{
    /**
     * Gom các phần tử theo key; trả về [key => items[]] cho nhóm có số lượng > $min.
     * Key rỗng/null bị bỏ qua.
     *
     * @param iterable $items
     * @param callable $keyFn fn($item) => string|null
     * @param int $min
     * @return array
     */
    public static function groupsWithCountAbove($items, callable $keyFn, $min = 1)
    {
        $byKey = [];
        foreach ($items as $it) {
            $key = $keyFn($it);
            if ($key === null || trim((string) $key) === '') {
                continue;
            }
            $byKey[$key][] = $it;
        }
        $out = [];
        foreach ($byKey as $key => $group) {
            if (count($group) > $min) {
                $out[$key] = $group;
            }
        }
        return $out;
    }
}
