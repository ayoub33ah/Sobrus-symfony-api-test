<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileNotFoundException;

class BlogArticleService
{
    public function mostFrequentWords(string $text, array $banned): array
    {
        $text = strtolower($text);
        $text = preg_replace('/[^\w\s]/', '', $text); 
        $words = preg_split('/\s+/', $text);
        
        $bannedSet = array_flip($banned); 
        $wordCounts = [];

        foreach ($words as $word) {
            if (!isset($bannedSet[$word]) && !empty($word)) {
                if (isset($wordCounts[$word])) {
                    $wordCounts[$word]++;
                } else {
                    $wordCounts[$word] = 1;
                }
            }
        }

        arsort($wordCounts);
        return array_slice(array_keys($wordCounts), 0, 3);
    }

    public function validateContent(string $text, array $banned): array
    {
        $text = strtolower($text);
        $words = preg_split('/\s+/', $text);
        $foundBannedWords = [];

        foreach ($words as $word) {
            if (in_array($word, $banned)) {
                $foundBannedWords[] = $word;
            }
        }
        
        return $foundBannedWords; 
    }
}
