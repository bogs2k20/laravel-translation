<?php

namespace JoeDixon\Translation;

use Illuminate\Filesystem\Filesystem;

class Scanner
{
    private $disk;

    private $scanPaths;

    private $translationMethods;

    public function __construct(Filesystem $disk, $scanPaths, $excludedFolders, $translationMethods)
    {
        $this->disk = $disk;
        $this->scanPaths = $scanPaths;
        $this->translationMethods = $translationMethods;
        $this->excludedFolders = $excludedFolders;
    }

    /**
     * Scan all the files in the provided $scanPath for translations.
     *
     * @return array
     */
    public function findTranslations()
    {
        $results = ['single' => [], 'group' => []];

        // This has been derived from a combination of the following:
        // * Laravel Language Manager GUI from Mohamed Said (https://github.com/themsaid/laravel-langman-gui)
        // * Laravel 5 Translation Manager from Barry vd. Heuvel (https://github.com/barryvdh/laravel-translation-manager)
        $matchingPattern =
            '[^\w]'. // Must not start with any alphanum or _
            '(?<!->)'. // Must not start with ->
            '('.implode('|', $this->translationMethods).')'. // Must start with one of the functions
            "\(". // Match opening parentheses
            "[\'\"]". // Match " or '
            '('. // Start a new group to match:
            '.+'. // Must start with group
            ')'. // Close group
            "[\'\"]". // Closing quote
            "[\),]";  // Close parentheses or new parameter

        foreach ($this->disk->allFiles($this->scanPaths) as $file) {
            $skipFile = false;
            foreach ($this->excludedFolders as $folder) {
                if(strstr($file->getPath(),DIRECTORY_SEPARATOR  . $folder) || substr($file->getPath(), -strlen($folder)) == $folder) {
                    $skipFile = true;
                    continue;
                }
            }
            if ($skipFile) continue;

            if (preg_match_all("/$matchingPattern/siU", $file->getContents(), $matches)) {
                foreach ($matches[2] as $key) {
                    if (preg_match("/(^[a-zA-Z0-9:_-]+([.][^\1)\ ]+)+$)/siU", $key, $arrayMatches)) {
                        [$file, $k] = explode('.', $arrayMatches[0], 2);
                        $results['group'][$file][$k] = '';
                        continue;
                    } else {
                        $results['single']['single'][$key] = '';
                    }
                }
            }
        }

        return $results;
    }
}
