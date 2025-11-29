<?php

namespace App\Services;

class ExcelColumnMapper
{
    /**
     * Tìm index của cột trong header dựa trên danh sách tên cột có thể có
     *
     * @param array $headerRow Mảng chứa tên các cột trong header
     * @param array $possibleNames Mảng chứa các tên cột có thể có (theo thứ tự ưu tiên)
     * @return int|null Index của cột tìm được, null nếu không tìm thấy
     */
    public function findColumnIndex(array $headerRow, array $possibleNames): ?int
    {
        // Chuẩn hóa header row: loại bỏ khoảng trắng thừa, chuyển về uppercase
        $normalizedHeader = array_map(function ($col) {
            return $this->normalizeColumnName($col);
        }, $headerRow);

        // Tìm exact match trước
        foreach ($possibleNames as $name) {
            $normalizedName = $this->normalizeColumnName($name);
            $index = array_search($normalizedName, $normalizedHeader, true);
            if ($index !== false) {
                return $index;
            }
        }

        // Nếu không tìm thấy exact match, thử fuzzy matching
        foreach ($possibleNames as $name) {
            $normalizedName = $this->normalizeColumnName($name);
            foreach ($normalizedHeader as $index => $headerCol) {
                if ($this->fuzzyMatch($normalizedName, $headerCol)) {
                    return $index;
                }
            }
        }

        return null;
    }

    /**
     * Tạo mapping từ header row sang các field trong database
     *
     * @param array $headerRow Mảng chứa tên các cột trong header
     * @param array $fieldMapping Mapping config từ field => possible column names
     * @return array Mapping từ field => column index
     */
    public function createFieldMapping(array $headerRow, array $fieldMapping): array
    {
        $mapping = [];

        foreach ($fieldMapping as $field => $possibleNames) {
            $index = $this->findColumnIndex($headerRow, $possibleNames);
            if ($index !== null) {
                $mapping[$field] = $index;
            }
        }

        return $mapping;
    }

    /**
     * Chuẩn hóa tên cột: loại bỏ khoảng trắng, chuyển về uppercase, loại bỏ ký tự đặc biệt
     *
     * @param string $columnName
     * @return string
     */
    private function normalizeColumnName(string $columnName): string
    {
        // Loại bỏ khoảng trắng thừa
        $normalized = trim($columnName);
        
        // Chuyển về uppercase
        $normalized = mb_strtoupper($normalized, 'UTF-8');
        
        // Loại bỏ khoảng trắng
        $normalized = str_replace(' ', '', $normalized);
        
        // Loại bỏ ký tự đặc biệt không cần thiết (giữ lại chữ, số, dấu gạch dưới)
        $normalized = preg_replace('/[^\p{L}\p{N}_]/u', '', $normalized);
        
        return $normalized;
    }

    /**
     * Fuzzy matching: kiểm tra xem hai tên cột có tương tự nhau không
     *
     * @param string $name1
     * @param string $name2
     * @return bool
     */
    private function fuzzyMatch(string $name1, string $name2): bool
    {
        // Nếu một trong hai rỗng, không match
        if (empty($name1) || empty($name2)) {
            return false;
        }

        // Exact match sau khi normalize
        if ($name1 === $name2) {
            return true;
        }

        // Kiểm tra nếu một chuỗi chứa chuỗi kia (với độ dài tối thiểu)
        $minLength = min(mb_strlen($name1), mb_strlen($name2));
        if ($minLength >= 3) {
            if (mb_strpos($name1, $name2) !== false || mb_strpos($name2, $name1) !== false) {
                return true;
            }
        }

        // Tính similarity bằng cách so sánh số ký tự giống nhau
        $similarity = $this->calculateSimilarity($name1, $name2);
        
        // Nếu similarity >= 80%, coi như match
        return $similarity >= 0.8;
    }

    /**
     * Tính độ tương đồng giữa hai chuỗi (Levenshtein distance)
     *
     * @param string $str1
     * @param string $str2
     * @return float Similarity từ 0 đến 1
     */
    private function calculateSimilarity(string $str1, string $str2): float
    {
        $len1 = mb_strlen($str1);
        $len2 = mb_strlen($str2);
        
        if ($len1 === 0 && $len2 === 0) {
            return 1.0;
        }
        
        if ($len1 === 0 || $len2 === 0) {
            return 0.0;
        }

        $maxLen = max($len1, $len2);
        $distance = levenshtein($str1, $str2);
        
        return 1 - ($distance / $maxLen);
    }

    /**
     * Detect loại catalog dựa trên key columns
     *
     * @param array $headerRow
     * @param array $catalogConfigs
     * @return string|null Tên catalog type hoặc null nếu không detect được
     */
    public function detectCatalogType(array $headerRow, array $catalogConfigs): ?string
    {
        $bestMatch = null;
        $maxMatches = 0;

        foreach ($catalogConfigs as $catalogType => $config) {
            if (!isset($config['detect_keys']) || empty($config['detect_keys'])) {
                continue;
            }

            $matches = 0;
            foreach ($config['detect_keys'] as $key) {
                $index = $this->findColumnIndex($headerRow, [$key]);
                if ($index !== null) {
                    $matches++;
                }
            }

            // Nếu tìm thấy ít nhất 2/3 key columns, coi như match
            $requiredMatches = max(2, (int)ceil(count($config['detect_keys']) * 0.6));
            if ($matches >= $requiredMatches) {
                if ($matches > $maxMatches) {
                    $maxMatches = $matches;
                    $bestMatch = $catalogType;
                }
            }
        }

        return $bestMatch;
    }
}

