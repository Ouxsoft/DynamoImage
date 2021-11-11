<?php
/**
 * This file is part of the Hoopless package.
 *
 * (c) Ouxsoft <contact@Ouxsoft.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Ouxsoft\DynamoImage;

class Path
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
    public static function decode(string $string, array $args = [], string $catchAll): array
    {
        $parameters = [];
        $unparsed = $string;
        $count = 1;
        do {
            $slug = strtok($unparsed, '/');
            $unparsed = substr($unparsed, strlen("{$slug}/"));

            $count++;

            if (in_array($slug, $args)) {
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
