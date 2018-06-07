<?php

namespace Faker\Provider;

/**
 * Depends on image generation from http://lorempixel.com/
 */
class Image extends Base
{
    protected static $categories = array(
        'abstract', 'animals', 'business', 'cats', 'city', 'food', 'nightlife',
        'fashion', 'people', 'nature', 'sports', 'technics', 'transport'
    );

    /**
     * Generate the URL that will return a random image
     *
     * Set randomize to false to remove the random GET parameter at the end of the url.
     *
     * @example 'http://lorempixel.com/640/480/?12345'
     *
     * @param integer $width
     * @param integer $height
     * @param string|null $category
     * @param bool $randomize
     * @param string|null $word
     * @param bool $gray
     *
     * @return string
     */
    public static function imageUrl($width = 640, $height = 480, $category = null, $randomize = true, $word = null, $gray = false, $alternative = null)
    {
        $url = '';
        if ($alternative == null) {
            $baseUrl = "https://lorempixel.com/";
            if ($gray) {
                $url = "gray/" . $url;
            }

            if ($category) {
                if (!in_array($category, static::$categories)) {
                    throw new \InvalidArgumentException(sprintf('Unknown image category "%s"', $category));
                }
                $url .= "{$category}/";
                if ($word) {
                    $url .= "{$word}/";
                }
            }

            if ($randomize) {
                $url .= '?' . static::randomNumber(5, true);
            }
        } else
            $baseUrl = $alternative;

        $url = "{$width}/{$height}/" . $url;



        return $baseUrl . $url;
    }

    /**
     * Download a remote random image to disk and return its location
     *
     * Requires curl, or allow_url_fopen to be on in php.ini.
     *
     * @example '/path/to/dir/13b73edae8443990be1aa8f1a483bc27.jpg'
     */
    public static function image($dir = null, $width = 640, $height = 480, $category = null, $fullPath = true, $randomize = true, $word = null)
    {
        $dir = is_null($dir) ? sys_get_temp_dir() : $dir; // GNU/Linux / OS X / Windows compatible
        // Validate directory path
        if (!is_dir($dir) || !is_writable($dir)) {
            throw new \InvalidArgumentException(sprintf('Cannot write to directory "%s"', $dir));
        }

        // Generate a random filename. Use the server address so that a file
        // generated at the same time on a different server won't have a collision.
        $name = md5(uniqid(empty($_SERVER['SERVER_ADDR']) ? '' : $_SERVER['SERVER_ADDR'], true));
        $filename = $name .'.jpg';
        $filepath = $dir . DIRECTORY_SEPARATOR . $filename;

        $url = static::imageUrl($width, $height, $category, $randomize, $word);

        $fp = fopen($filepath, 'w');
         
         //alternatives to lorem îxel if it fails
         $alternatives = [
            'http://www.placecage.com/c/',
            'https://lorempixel.com/',
            'https://pixelipsum.com/',
            'https://placebear.com/'
        ];
         
        // save file
        if (function_exists('curl_exec')) {
            do {
                // use cURL
                echo 'looped' . "\r\n";
                $success = false;
                $ch = curl_init($url);
                curl_setopt($ch, CURLOPT_FILE, $fp);
                $success = curl_exec($ch) && curl_getinfo($ch, CURLINFO_HTTP_CODE) === 200;
                if (!$success) {
                    try {
                        unlink($filepath);
                        echo 'unlinked' . "\r\n";
                    } catch (\Exception $exception) {
                        echo 'exception' . "\r\n";
                    }


                }  
                curl_close($ch);
                // gets an image from a random alternative filler image provider
                $url = static::imageUrl($width, $height, $category, $randomize, $word, false, $alternatives[rand(0,count($alternatives)-1)]);
            } while (!$success);
            fclose($fp);


        } elseif (ini_get('allow_url_fopen')) {
            // use remote fopen() via copy()
            $success = copy($url, $filepath);
        } else {
            return new \RuntimeException('The image formatter downloads an image from a remote HTTP server. Therefore, it requires that PHP can request remote hosts, either via cURL or fopen()');
        }

        return $fullPath ? $filepath : $filename;
    }
}
