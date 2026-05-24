<?php
namespace App\Services;

class AliceCommandParser
{
    /**
     * Извлечь название материала из текста команды
     */
    public static function extractMaterial(string $command, array $materials): ?array
    {
        $command = mb_strtolower(trim($command));
        
        foreach ($materials as $m) {
            $materialName = mb_strtolower($m['material']);
            if (mb_strpos($command, $materialName) !== false) {
                return $m;
            }
        }
        
        return null;
    }
    
    /**
     * Извлечь имя клиента из текста
     */
    public static function extractClientName(string $command): string
    {
        $command = mb_strtolower(trim($command));
        
        // Убираем служебные слова
        $clean = str_replace([
            'добавить клиента', 'новый клиент', 'телефон', 'номер', '+'
        ], '', $command);
        
        // Убираем числа (телефон)
        $clean = preg_replace('/\d+/', '', $clean);
        
        return trim($clean);
    }
    
    /**
     * Извлечь телефон из текста
     */
    public static function extractPhone(string $command): string
    {
        preg_match('/\+?\d{10,15}/', $command, $matches);
        return $matches[0] ?? '';
    }
    
    /**
     * Распознать дату из текста
     */
    public static function parseDate(string $text): string
    {
        $map = [
            'сегодня' => date('Y-m-d'),
            'завтра' => date('Y-m-d', strtotime('+1 day')),
            'послезавтра' => date('Y-m-d', strtotime('+2 days')),
        ];
        
        $text = mb_strtolower(trim($text));
        
        foreach ($map as $word => $date) {
            if (mb_strpos($text, $word) !== false) {
                return $date;
            }
        }
        
        return date('Y-m-d');
    }
    
    /**
     * Проверить, содержит ли команда указание на конкретный материал
     */
    public static function hasSpecificMaterial(string $command): bool
    {
        // Если в команде есть слова кроме базовых — значит спрашивают о конкретном
        $command = mb_strtolower($command);
        $baseWords = ['материалы', 'склад', 'запасы', 'наличие', 'что', 'какие', 'посмотреть', 
                      'покажи', 'проверь', 'сколько', 'список', 'есть', 'ли', 'в'];
        
        $words = explode(' ', $command);
        foreach ($words as $word) {
            if (!in_array($word, $baseWords) && strlen($word) > 2) {
                return true;
            }
        }
        
        return false;
    }
}