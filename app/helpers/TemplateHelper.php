<?php

namespace App\Helpers;

use App\Core\FileManager;

/**
 * TemplateHelper contains useful methods for working with templates
 * 
 * @author Lukas Velek
 */
class TemplateHelper {
    /**
     * Loads all components in a given template file
     * 
     * @param string $filePath Path to the file
     * @param array $otherVariables Array with names of other variables found in the template file
     * @return array<string, string> Array where the key is component name and the value is the createComponentX action name
     */
    public static function loadComponentsFromTemplateFile(string $filePath, array &$otherVariables = []) {
        if(FileManager::fileExists($filePath)) {
            $templateContent = FileManager::loadFile($filePath);

            $variables = [];
            preg_match_all('/\$[a-zA-Z0-9\s]*\$/', $templateContent, $variables);
            $variables = $variables[0];
            $tmp = [];
            foreach($variables as $variable) {
                $v = substr($variable, 1, -1);

                if(str_contains($v, 'component')) {
                    $componentName = substr($v, strlen('component') + 1);
                    $componentAction = 'createComponent' . ucfirst($componentName);

                    $tmp[$componentName] = $componentAction;
                } else {
                    $otherVariables[] = $v;
                }
            }

            return $tmp;
        } else {
            return [];
        }
    }

    /**
     * Loads all components in a given template content
     * 
     * @param string $templateContent Template content
     * @param array $otherVariables Array with names of other variables found in the template file
     * @return array<string, string> Array where the key is component name and the value is the createComponentX action name
     */
    public static function loadComponentsFromTemplateContent(string $templateContent, array &$otherVariables = []) {
        $variables = [];
        preg_match_all('/\$[a-zA-Z0-9\s]*\$/', $templateContent, $variables);
        $variables = $variables[0];
        $tmp = [];
        foreach($variables as $variable) {
            $v = substr($variable, 1, -1);

            if(str_contains($v, 'component')) {
                $componentName = substr($v, strlen('component') + 1);
                $componentAction = 'createComponent' . ucfirst($componentName);

                $tmp[$componentName] = $componentAction;
            } else {
                $otherVariables[] = $v;
            }
        }

        return $tmp;
    }
}

?>