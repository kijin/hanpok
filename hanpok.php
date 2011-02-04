<?php

/**
 * -----------------------------------------------------------------------------
 *  H A N P O K   :   On-Screen Display Width Calculator for Mixed Korean Text
 * -----------------------------------------------------------------------------
 * 
 * @package    HanPok
 * @author     Kijin Sung <kijin.sung@gmail.com>
 * @copyright  (c) 2010-2011, Kijin Sung <kijin.sung@gmail.com>
 * @license    GPL v3 <http://www.opensource.org/licenses/gpl-3.0.html>
 * @link       http://github.com/kijin/hanpok
 * @version    0.1.4
 * 
 * -----------------------------------------------------------------------------
 * 
 * Copyright (c) 2010-2011, Kijin Sung <kijin.sung@gmail.com>
 * 
 * This program is free software: you can redistribute it and/or modify it
 * under the terms of the GNU General Public License as published by the Free
 * Software Foundation, either version 3 of the License, or (at your option)
 * any later version.
 * 
 * This program is distributed in the hope that it will be useful, but WITHOUT
 * ANY WARRANTY; without even the implied warranty of MERCHANTABILITY or
 * FITNESS FOR A PARTICULAR PURPOSE. See the GNU General Public License for
 * more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program.  If not, see <http://www.gnu.org/licenses/>.
 * 
 * ----------------------------------------------------------------------------
 */

class HanPok
{
    /**
     * Font profile is loaded here.
     */
    
    protected $profile = array();
    
    
    /**
     * Font name aliases.
     */
    
    protected $aliases = array(
        '굴림' => 'gulim',
        '돋움' => 'dotum',
        '바탕' => 'batang',
        '맑은 고딕' => 'malgun_gothic',
    );
    
    
    /**
     * Constructor.
     * 
     * @param  string  The name of the font, e.g. 'Gulim'.
     * @param  string  The size of the font, e.g. '9pt', '12px'.
     * @param  bool    True if the font is bold, false otherwise.
     */
    
    public function __construct($font, $size, $bold = false)
    {
        // Convert the font name to lower-case English.
        
        $font = strtolower($font);
        if (array_key_exists($font, $this->aliases)) $font = $this->aliases[$font];
        
        // Convert the font size to pixels.
        
        if (is_numeric($size))
        {
            $size = (int)$size;
        }
        elseif (preg_match('/^(\\d+)pt$/', $size, $matches))
        {
            $size = (int)$matches[1] + 3;
            if ($size > 13) $size += 1;
            if ($size > 26) $size += 2;
        }
        elseif (preg_match('/^(\\d+)px$/', $size, $matches))
        {
            $size = (int)$matches[1];
        }
        else
        {
            $size = (int)$size;
        }
        
        // Find the font profile.
        
        $filename = dirname(__FILE__) . '/fonts/' . $font . '_' . $size . 'px_' . ($bold ? 'bold' : 'normal') . '.php';
        if (!file_exists($filename)) throw new Exception('Font profile not found: ' . $font . ' ' . $size . 'px (' . ($bold ? 'bold' : 'normal') . ')');
        
        // Load the font profile.
        
        $default = null; $ascii = null; $symbols = null;
        include $filename;
        
        $this->profile = array(
            'default' => $default,
            'ascii' => $ascii,
            'symbols' => $symbols,
        );
    }
    
    
    /**
     * Calculate the width of the given string.
     * 
     * The result is not guaranteed to be accurate, because spacing between
     * characters may vary. Therefore, do not use for alignment purposes.
     * 
     * @param   string  The string whose width to calculate.
     * @return  int     The width, in pixels, of the string.
     */
    
    public function width($str)
    {
        // Separate the characters.
        
        $chars = preg_split('//u', $str);
        array_shift($chars);
        array_pop($chars);
        
        // Initialize the width to 0.
        
        $w = 0;
        
        // Loop over the characters.
        
        foreach ($chars as $char)
        {
            // Multibyte characters are full width, except symbols with different widths.
            
            if (ord($char[0]) > 127)
            {
                if (array_key_exists(($hex = bin2hex($char)), $this->profile['symbols']))
                {
                    $w += $this->profile['symbols'][$hex];
                }
                else
                {
                    $w += $this->profile['default'];
                }
            }
            
            // Single-byte characters need to refer to the database.
            
            else
            {
                $w += $this->profile['ascii'][ord($char[0])];
            }
        }
        
        // Return the width.
        
        return $w;
    }
    
    
    /**
     * Method to cut a string to the specified maximum width.
     * 
     * The result is guaranteed never to exceed the maximum, even with the
     * end marker attached to it.
     * 
     * @param   string  The string to cut.
     * @param   int     The maximum width, in pixels.
     * @param   string  The end marker, default is '...'
     * @return  string  The result.
     */
    
    public function cut($str, $max, $end = '...')
    {
        // Separate the characters.
        
        $chars = preg_split('//u', $str);
        array_shift($chars);
        array_pop($chars);
        
        // Get the maximum width with allowance for the end marker.
        
        $safemax = $max - $this->width($end);
        
        // Initialize the return value.
        
        $head = '';
        $safe = false;
        $w = 0;
        
        // Loop over the characters.
        
        foreach ($chars as $char)
        {
            // Multibyte characters are full width, except symbols with different widths.
            
            if (ord($char[0]) > 127)
            {
                if (array_key_exists(($hex = bin2hex($char)), $this->profile['symbols']))
                {
                    $w += $this->profile['symbols'][$hex];
                }
                else
                {
                    $w += $this->profile['default'];
                }
            }
            
            // Single-byte characters need to refer to the database.
            
            else
            {
                $w += $this->profile['ascii'][ord($char[0])];
            }
            
            // If width is greater than the maximum, stop here.
            
            if ($w > $max)
            {
                if ($safe !== false) $head = $safe . $end;
                break;
            }
            
            // If width is greater than the maximum minus allowance for the end marker.
            
            elseif ($w > $safemax)
            {
                if ($safe === false) $safe = $head;
                $head .= $char;
            }
            
            // Otherwise, add to the return string and continue.
            
            else
            {
                $head .= $char;
            }
        }
        
        // Return the result.
        
        return $head;
    }
}
