<?php

namespace Hyper;

/**
 * Class Translator
 * 
 * Translator class for handling multilingual text translations.
 * 
 * @package hyper
 * @author Shahin Moyshan <shahin.moyshan2@gmail.com>
 */
class Translator
{
    /**
     * Holds translated texts.
     * 
     * @var array
     */
    private array $translatedTexts = [];

    /**
     * Constructor for the translator class.
     * 
     * Optionally loads a language file during initialization.
     * 
     * @param string|null $lang_file Path to the language file. Defaults to the file based on environment settings.
     */
    public function __construct(?string $lang_file = null)
    {
        // Determine the default language file if none is provided
        if ($lang_file === null) {
            $lang_file = env(
                'lang_file',
                sprintf('%s/%s.php', env('lang_dir'), env('lang', 'en'))
            );
        }

        // Load translated texts from the file if it exists
        if (file_exists($lang_file)) {
            $this->translatedTexts = require $lang_file;
        }
    }

    /**
     * Merges the given translations with the existing ones.
     * 
     * Useful for adding or overriding translations for a specific context.
     * 
     * @param array $translatedTexts The translations to be merged.
     * @return void
     */
    public function mergeTranslatedTexts(array $translatedTexts)
    {
        $this->translatedTexts = array_merge($this->translatedTexts, $translatedTexts);
    }


    /**
     * Sets the translations for the translator.
     * 
     * Replaces any existing translations with the provided array of translated texts.
     * 
     * @param array $translatedTexts The translations to set.
     * @return void
     */
    public function setTranslatedTexts(array $translatedTexts)
    {
        $this->translatedTexts = $translatedTexts;
    }

    /**
     * Translates a given text based on the local translations or returns the original text if translation is unavailable.
     * Supports pluralization and argument substitution.
     *
     * @param string $text The text to be translated.
     * @param $arg The number to determine pluralization or replace placeholder in the translated text.
     * @param array $args An array of arguments to replace placeholders in the translated text.
     * @param array $args2 An array of arguments to replace plural placeholders in the translated text.
     * @return string The translated text with any placeholders replaced by the provided arguments.
     */
    public function translate(string $text, $arg = null, array $args = [], array $args2 = []): string
    {
        // Check if the text has a translation
        $translation = $this->translatedTexts[$text] ?? $text;

        // Determine if the translation has plural forms
        if (is_array($translation)) {
            $translation = $arg > 1 ? $translation[1] : $translation[0];
            $args = $arg > 1 && !empty($args2) ? $args2 : $args;
        } elseif (!empty($args) && !empty($args2)) {
            $args = $arg > 1 ? $args2 : $args;
        }

        // Determine if the translation has arguments, else substitute with the first argument.
        if ($arg !== null && empty($args)) {
            $args = is_array($arg) ? $arg : [$arg];
        }

        // Use vsprintf to substitute any placeholders with args
        return vsprintf($translation, $args);
    }
}
