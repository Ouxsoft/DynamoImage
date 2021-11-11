<?php
/**
 * This file is part of the Hoopless package.
 *
 * (c) Ouxsoft <contact@Ouxsoft.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace App\Model;

class DynamoImagePath
{

    /**
     * Parses URL to array containing key values
     * e.g.
     * ('a/1/b/c/dads/22',['a','b'],'d')
     * returns ['a'=>1,'b'=c','d'=>'dads/22']
     *
     * @param string $string
     * @param array $args
     * @param string $catchAll
     * @return array
     */
    public static function decodeParams(string $string, array $args = [], string $catchAll) : array
    {
        $parameters = [];
        $unparsed = $string;
        $count = 1;
        do {
            $slug = strtok($unparsed, '/');
            $unparsed = substr($unparsed, strlen("{$slug}/"));

            $count++;

            if(in_array($slug, $args)){
                $parameters[$slug] = strtok($unparsed, '/');
                $unparsed = substr($unparsed, strlen("{$parameters[$slug]}/"));
                continue;
            }

            $parameters[$catchAll] = "{$slug}{$unparsed}";
            break;
        } while ((!empty($unparsed))&&($count<10));

        return $parameters;
    }

    /**
     * Decodes string of parameters to array
     *
     * @param string $string
     * @return array
     */
    public static function decode(string $string): array
    {
        $filepath = '';
        $parts = explode('/', $string);
        $parameters = [];
        $previous_key = '';
        foreach ($parts as $value) {
            if ($value == null) {
                continue;
            }

            // start by building out filepath
            if (is_dir(dirname(__DIR__, 2) . $filepath . '/' . $value)) {
                $filepath .= '/' . $value;
                continue;
            }

            switch ($previous_key) {
                case 'height':
                    $parameters['height'] = $value;
                    break;
                case 'width':
                    $parameters['width'] = $value;
                    break;
                case 'dimension':
                    // if dimensions then is array
                    if (preg_match('/([0-9]+x[0-9]+)/', $value)) {
                        list($width, $height) = explode('x', $value);
                        $parameters['width'] = $width;
                        $parameters['height'] = $height;
                    }
                    break;
                case 'offset':
                    // if comma then is array
                    if (strpos($value, ',') !== false) {
                        list($x, $y) = explode(',', $value);
                        $parameters['offset_x'] = $x;
                        $parameters['offset_y'] = $y;
                    }
                    break;
                default:
                    break;
            }

            $previous_key = $value;
        }

        $parameters['filename'] = $filepath . '/' . end($parts);

        return $parameters;
    }

    /**
     * Encodes parameters provided
     *
     * @param array $parameters
     * @return string
     */
    public static function encode(array $parameters): string
    {
        $path = '';
        foreach ($parameters as $parameter => $value) {
            if ($parameter == 'filename') {
                $path .= $value;
                continue;
            }

            if (is_array($value)) {
                switch ($parameter) {
                    case 'dimension':
                        $glue = 'x';
                        break;
                    default:
                        $glue = ',';
                        break;
                }
                $value = implode($glue, $value);
            }

            $path .= $parameter . '/' . $value . '/';
        }

        $path = preg_replace('/^[\w#-]+$/', '', $path);
        $path = str_replace(' ', '-', $path);
        $path = strtolower($path);

        return $path;
    }
}
