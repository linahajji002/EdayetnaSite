<?php
namespace App\Service;

class FiltrageService
{
    public function filtrerMotsInappropries(string $text): string
    {
        $inappropriateWords = ['débile', 'stupid']; // Liste des mots à filtrer
        
        foreach ($inappropriateWords as $word) {
            $pattern = '/\b' . preg_quote($word, '/') . '\b/i';
            $replacement = str_repeat('*', mb_strlen($word));
            $text = preg_replace($pattern, $replacement, $text);
        }
        
        return $text;
    }
}


