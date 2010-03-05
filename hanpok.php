<?php

/**
 * -----------------------------------------------------------------------------
 *  H A N P O K   :   On-Screen Display Width Calculator for Mixed Korean Text
 * -----------------------------------------------------------------------------
 * 
 * Web designers in Korea often need to trim text to fit inside a given box.
 * Although Hangul, the Korean writing system, consists of characters that have
 * identical widths, the calculation gets tricky when alphanumeric symbols are
 * mixed with Korean characters. Designers often underestimate or overestimate
 * the width required to display a given number of bytes or characters, leading
 * to broken layouts and other annoying results.
 * 
 * This library provides a simple means to solve this problem. Given a commonly
 * used font name and size, this library will quickly calculate the number of
 * pixels required to display a string. Even better, one can use this library
 * to trim strings automatically to a given width.
 * 
 * This library requires PHP 5 or later, and PCRE must be installed with support
 * for multibyte strings. (Compile options: --with-pcre-regex --enable-mbregex)
 * Currently, only UTF-8 strings are supported. Don't use EUC-KR with it!
 * 
 * @package    HanPok
 * @author     Kijin Sung <kijinbear@gmail.com>
 * @copyright  (c) 2010, Kijin Sung <kijinbear@gmail.com>
 * @license    GPL v3 <http://www.opensource.org/licenses/gpl-3.0.html>
 * @link       http://github.com/kijin/hanpok
 * @version    0.1.3
 * 
 * -----------------------------------------------------------------------------
 * 
 * Copyright (c) 2010, Kijin Sung <kijinbear@gmail.com>
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
     * Constructor.
     * 
     * @param  string  The name of the font, e.g. 'Gulim'.
     * @param  string  The size of the font, e.g. '9pt', '12px'.
     * @param  bool    True if the font is bold, false otherwise.
     */
    
    public function __construct($font, $size, $bold = false)
    {
        // Standardize the font name.
        
        switch (strtolower($font))
        {
            case 'gulim':
            case '굴림':
                $this->font = 'Gulim';
                break;
                
            case 'dotum':
            case '돋움':
                $this->font = 'Dotum';
                break;
                
            case 'batang':
            case '바탕':
                $this->font = 'Batang';
                break;
                
            case 'malgun gothic':
            case '맑은 고딕':
                $this->font = 'Malgun Gothic';
                break;
        }
        
        // Standardize the font size.
        
        if (is_numeric($size))
        {
            $this->size = (int)$size;
        }
        elseif (preg_match('/^(\\d+)pt$/', $size, $matches))
        {
            $this->size = (int)$matches[1] + 3;
            if ($this->size > 13) $this->size++;
        }
        elseif (preg_match('/^(\\d+)px$/', $size, $matches))
        {
            $this->size = (int)$matches[1];
        }
        
        // If unsupported, throw an exception.
        
        if (!isset($this->widths[$this->font][$this->size])) throw new Exception('Unsupported font and/or size: ' . $font . ' ' . $size);
        
        // Set the bold status.
        
        $this->bold = (bool)$bold;
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
            // Multibyte characters are assumed to be full width.
            
            if (ord($char[0]) > 127)
            {
                $w += $this->size;
            }
            
            // Single-byte characters need to refer to the database.
            
            else
            {
                $w += $this->widths[$this->font][$this->size][ord($char[0])];
            }
            
            // If bold, add 1 pixel.
            
            if ($this->bold) $w++;
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
            // Multibyte characters are assumed to be full width.
            
            if (ord($char[0]) > 127)
            {
                $w += $this->size;
            }
            
            // Single-byte characters need to refer to the database.
            
            else
            {
                $w += $this->widths[$this->font][$this->size][ord($char[0])];
            }
            
            // If bold, add 1 pixel.
            
            if ($this->bold) $w++;
            
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
    
    
    /**
     * Keep font name and size info here.
     */
    
    private $font = '';
    private $size = 0;
    private $bold = false;
    
    
    /**
     * Width data for common Korean fonts.
     */
    
    private $widths = array(
        
        'Gulim' => array(
            
            11 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        4, 3, 5, 7, 7, 10, 7, 3, 4, 4, 5, 5, 3, 5, 3, 5, 
                        6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 3, 3, 7, 6, 7, 6, 
                        11, 8, 7, 8, 8, 7, 7, 9, 8, 3, 5, 7, 6, 9, 7, 9, 
                        8, 9, 8, 8, 7, 8, 9, 11, 8, 8, 8, 4, 11, 4, 5, 6, 
                        3, 7, 7, 7, 7, 7, 4, 7, 7, 3, 3, 6, 3, 9, 7, 7, 
                        7, 7, 4, 7, 4, 7, 7, 9, 6, 7, 6, 4, 3, 4, 6, 0),
            
            12 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        4, 4, 4, 6, 6, 10, 8, 4, 5, 5, 6, 6, 4, 6, 4, 6, 
                        6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 4, 4, 8, 6, 8, 6, 
                        12, 8, 8, 9, 8, 8, 7, 9, 8, 3, 6, 8, 7, 11, 9, 9, 
                        8, 9, 8, 8, 8, 8, 8, 10, 8, 8, 8, 6, 11, 6, 6, 6, 
                        4, 7, 7, 7, 7, 7, 3, 7, 7, 3, 3, 6, 3, 11, 7, 7, 
                        7, 7, 4, 7, 3, 7, 6, 10, 7, 7, 7, 6, 6, 6, 9, 0),
                        
            13 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        4, 4, 4, 8, 7, 12, 8, 4, 5, 5, 7, 7, 4, 7, 4, 6, 
                        7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 4, 4, 7, 7, 7, 7, 
                        11, 8, 9, 9, 9, 8, 7, 10, 9, 3, 6, 8, 7, 11, 9, 10, 
                        9, 10, 9, 9, 8, 9, 8, 12, 8, 8, 9, 7, 13, 7, 7, 7, 
                        4, 8, 8, 8, 8, 8, 3, 8, 8, 3, 3, 6, 3, 11, 8, 8, 
                        8, 8, 5, 8, 3, 8, 6, 10, 7, 7, 7, 7, 7, 7, 10, 0),
            
            14 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 5, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        5, 5, 5, 8, 9, 11, 9, 5, 6, 6, 7, 8, 5, 8, 5, 6, 
                        8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 5, 5, 10, 8, 10, 8, 
                        13, 9, 10, 10, 10, 9, 8, 11, 10, 3, 6, 8, 8, 12, 9, 12, 
                        10, 12, 10, 10, 8, 10, 8, 14, 9, 8, 9, 7, 14, 7, 7, 7, 
                        5, 8, 8, 8, 8, 8, 4, 8, 8, 3, 3, 7, 3, 11, 8, 9, 
                        8, 8, 4, 8, 4, 8, 8, 10, 9, 8, 8, 7, 7, 7, 11, 0),
            
            15 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 5, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        5, 5, 5, 8, 9, 11, 9, 5, 6, 6, 7, 8, 5, 8, 5, 6, 
                        8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 5, 5, 10, 8, 10, 8, 
                        13, 9, 10, 10, 10, 9, 8, 11, 10, 3, 6, 8, 8, 12, 9, 12, 
                        10, 12, 10, 10, 8, 10, 8, 14, 9, 8, 9, 7, 14, 7, 7, 8, 
                        5, 8, 8, 8, 8, 8, 4, 8, 8, 3, 3, 7, 3, 11, 8, 9, 
                        8, 8, 4, 8, 4, 8, 8, 10, 9, 8, 8, 7, 7, 7, 11, 0),

            16 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 6, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        6, 6, 5, 11, 9, 14, 10, 5, 6, 6, 7, 8, 5, 8, 5, 6, 
                        8, 8, 8, 8, 9, 8, 8, 8, 8, 8, 5, 5, 11, 8, 11, 8, 
                        13, 10, 10, 11, 10, 10, 9, 11, 10, 3, 6, 10, 9, 13, 11, 12, 
                        10, 12, 10, 10, 9, 11, 10, 14, 11, 10, 10, 8, 14, 8, 8, 8, 
                        6, 9, 9, 9, 9, 9, 5, 9, 9, 3, 4, 8, 3, 13, 9, 10, 
                        9, 9, 5, 9, 5, 9, 8, 10, 9, 8, 9, 8, 8, 8, 12, 0),
        ),
        
        'Dotum' => array(
            
            11 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        4, 3, 4, 7, 6, 10, 7, 3, 4, 4, 5, 6, 3, 6, 2, 4, 
                        6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 3, 3, 7, 6, 7, 6, 
                        11, 8, 7, 8, 8, 7, 7, 9, 8, 3, 5, 7, 6, 9, 7, 9, 
                        8, 9, 8, 8, 7, 8, 9, 11, 8, 8, 8, 4, 11, 4, 5, 6, 
                        3, 7, 7, 7, 7, 7, 4, 7, 7, 3, 3, 6, 3, 9, 7, 7, 
                        7, 7, 4, 7, 4, 7, 6, 9, 6, 7, 6, 4, 3, 4, 6, 0),
            
            12 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        4, 3, 5, 7, 7, 11, 8, 4, 5, 5, 6, 6, 4, 6, 4, 6, 
                        6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 4, 4, 8, 6, 8, 6, 
                        10, 8, 8, 9, 8, 8, 7, 9, 8, 3, 6, 7, 7, 11, 8, 9, 
                        8, 9, 8, 8, 7, 8, 8, 10, 8, 8, 8, 6, 11, 6, 6, 6, 
                        4, 7, 7, 7, 7, 7, 3, 7, 7, 3, 3, 6, 3, 9, 7, 7, 
                        7, 7, 4, 7, 3, 7, 6, 10, 6, 6, 7, 6, 6, 6, 9, 0),
            
            13 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        4, 3, 5, 7, 7, 11, 8, 4, 5, 5, 6, 6, 4, 6, 4, 6, 
                        6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 4, 4, 7, 6, 7, 6, 
                        10, 8, 8, 9, 8, 8, 7, 9, 8, 3, 6, 7, 7, 11, 8, 9, 
                        8, 9, 8, 8, 7, 8, 8, 10, 8, 8, 8, 6, 11, 6, 6, 6, 
                        4, 7, 7, 7, 7, 7, 3, 7, 7, 3, 3, 6, 3, 9, 7, 7, 
                        7, 7, 4, 7, 3, 7, 6, 10, 6, 6, 7, 6, 6, 6, 9, 0),
            
            14 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 5, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        5, 5, 5, 8, 9, 10, 8, 5, 6, 6, 7, 8, 5, 8, 5, 6, 
                        9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 5, 5, 9, 8, 9, 8, 
                        13, 8, 10, 10, 10, 9, 8, 11, 9, 3, 6, 8, 8, 12, 10, 12, 
                        10, 12, 10, 10, 8, 10, 8, 14, 10, 10, 9, 7, 14, 7, 7, 7, 
                        4, 8, 8, 8, 8, 8, 4, 8, 8, 3, 3, 7, 3, 11, 8, 9, 
                        8, 8, 4, 7, 4, 8, 8, 10, 8, 8, 7, 7, 7, 7, 11, 0),
            
            15 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 5, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        5, 5, 5, 8, 9, 10, 8, 5, 6, 6, 7, 8, 5, 8, 5, 6, 
                        9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 5, 5, 9, 8, 9, 8, 
                        13, 8, 10, 10, 10, 9, 8, 11, 9, 3, 6, 8, 8, 12, 10, 12, 
                        10, 12, 10, 10, 8, 10, 8, 14, 10, 10, 9, 7, 14, 7, 7, 8, 
                        4, 8, 8, 8, 8, 8, 4, 8, 8, 3, 3, 7, 3, 11, 8, 9, 
                        8, 8, 4, 7, 4, 8, 8, 10, 8, 8, 7, 7, 7, 7, 11, 0),
            
            16 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 6, 0, 0, 0, 0, 0, 
                        6, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        5, 5, 6, 13, 9, 13, 12, 5, 6, 6, 7, 8, 5, 8, 5, 7, 
                        8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 5, 5, 9, 8, 9, 8, 
                        16, 10, 10, 11, 10, 10, 9, 12, 10, 3, 8, 10, 9, 13, 11, 12, 
                        10, 12, 10, 10, 9, 11, 10, 14, 11, 10, 10, 8, 14, 8, 8, 6, 
                        5, 9, 9, 9, 9, 9, 5, 9, 9, 3, 4, 8, 3, 13, 9, 10, 
                        9, 9, 5, 9, 5, 9, 8, 12, 9, 8, 9, 9, 9, 9, 12, 0),
        ),
        
        'Batang' => array(
        
            11 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        4, 3, 4, 6, 6, 9, 8, 3, 4, 4, 6, 6, 2, 6, 2, 4, 
                        6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 3, 3, 6, 6, 6, 6, 
                        10, 9, 8, 8, 8, 7, 6, 9, 8, 4, 5, 8, 6, 9, 8, 8, 
                        7, 8, 8, 7, 7, 8, 7, 10, 7, 7, 7, 4, 11, 4, 6, 6, 
                        3, 7, 7, 6, 7, 6, 4, 7, 6, 3, 3, 6, 3, 9, 6, 6, 
                        6, 6, 5, 6, 4, 6, 7, 9, 6, 6, 6, 4, 3, 4, 6, 0),
            
            12 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        4, 4, 5, 7, 7, 10, 9, 4, 5, 5, 6, 6, 4, 6, 4, 6, 
                        6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 4, 4, 8, 6, 8, 6, 
                        11, 9, 8, 8, 8, 8, 8, 9, 9, 4, 5, 9, 8, 11, 9, 9, 
                        8, 10, 9, 7, 7, 8, 9, 11, 7, 7, 7, 6, 11, 6, 6, 6, 
                        4, 7, 7, 7, 8, 7, 5, 7, 8, 4, 3, 7, 4, 10, 8, 7, 
                        7, 8, 6, 6, 5, 7, 7, 11, 7, 7, 6, 6, 6, 6, 9, 0),
            
            13 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        4, 4, 4, 8, 7, 12, 11, 4, 5, 5, 7, 7, 4, 7, 4, 6, 
                        7, 7, 7, 7, 7, 7, 7, 7, 7, 7, 4, 4, 8, 7, 8, 7, 
                        12, 9, 9, 9, 9, 8, 8, 10, 9, 3, 5, 8, 8, 11, 9, 10, 
                        8, 10, 8, 8, 9, 9, 9, 11, 8, 9, 9, 7, 12, 7, 7, 7, 
                        4, 7, 8, 8, 8, 8, 4, 8, 7, 3, 4, 7, 3, 11, 7, 8, 
                        8, 8, 5, 8, 4, 7, 7, 11, 7, 7, 7, 7, 7, 7, 8, 0),
            
            14 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 5, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        5, 5, 5, 10, 9, 13, 11, 5, 6, 6, 7, 8, 5, 8, 5, 6, 
                        8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 5, 5, 8, 8, 8, 8, 
                        13, 9, 9, 9, 9, 9, 8, 10, 9, 3, 5, 9, 8, 11, 9, 10, 
                        8, 10, 9, 9, 9, 9, 9, 11, 8, 9, 10, 7, 12, 7, 8, 7, 
                        5, 7, 8, 8, 8, 8, 4, 8, 7, 3, 4, 8, 4, 12, 8, 7, 
                        8, 8, 6, 6, 4, 7, 7, 11, 8, 8, 7, 7, 7, 7, 11, 0),
            
            15 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 5, 5, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        5, 5, 5, 10, 9, 13, 11, 5, 6, 6, 7, 8, 5, 8, 5, 6, 
                        8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 5, 5, 8, 8, 8, 8, 
                        13, 9, 9, 9, 9, 9, 8, 10, 9, 3, 5, 9, 8, 11, 9, 10, 
                        8, 10, 9, 9, 9, 9, 9, 11, 8, 9, 10, 7, 12, 7, 8, 8, 
                        5, 7, 8, 8, 8, 8, 4, 8, 7, 3, 4, 8, 4, 12, 8, 7, 
                        8, 8, 6, 6, 4, 7, 7, 11, 8, 8, 7, 7, 7, 7, 11, 0),
            
            16 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 6, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        6, 5, 5, 10, 9, 15, 13, 5, 5, 5, 9, 9, 5, 9, 5, 7, 
                        9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 5, 5, 8, 9, 8, 9, 
                        14, 12, 12, 12, 12, 11, 10, 12, 10, 5, 7, 12, 10, 15, 12, 13, 
                        11, 13, 11, 10, 11, 13, 13, 14, 12, 11, 10, 8, 17, 8, 9, 8, 
                        5, 8, 9, 8, 9, 8, 6, 9, 9, 4, 5, 9, 4, 14, 9, 9, 
                        9, 9, 7, 8, 6, 9, 9, 11, 8, 9, 7, 8, 8, 8, 12, 0),
        ),
        
        'Malgun Gothic' => array(
        
            11 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 6, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        6, 5, 6, 9, 8, 11, 11, 5, 5, 5, 7, 10, 4, 7, 4, 6, 
                        8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 4, 4, 10, 10, 10, 7, 
                        13, 9, 8, 9, 10, 8, 7, 10, 10, 5, 6, 9, 7, 12, 10, 11, 
                        8, 11, 9, 8, 8, 10, 9, 12, 9, 8, 8, 5, 10, 5, 10, 7, 
                        5, 8, 9, 7, 9, 8, 5, 9, 8, 5, 5, 8, 5, 12, 8, 9, 
                        9, 9, 6, 7, 6, 8, 7, 10, 7, 7, 7, 5, 5, 5, 10, 0),
            
            12 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 6, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        6, 5, 7, 9, 9, 12, 12, 5, 6, 6, 7, 10, 5, 7, 5, 7, 
                        9, 9, 9, 9, 9, 9, 9, 9, 9, 9, 5, 5, 10, 10, 10, 8, 
                        14, 10, 9, 10, 11, 8, 8, 10, 11, 5, 6, 9, 8, 13, 11, 11, 
                        9, 11, 9, 9, 8, 10, 10, 13, 9, 9, 9, 6, 11, 6, 10, 7, 
                        5, 8, 9, 8, 9, 8, 6, 9, 9, 5, 5, 8, 5, 13, 9, 9, 
                        9, 9, 6, 7, 6, 9, 8, 11, 8, 8, 8, 6, 5, 6, 10, 0),
            
            13 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 6, 6, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        6, 5, 6, 9, 8, 12, 12, 4, 5, 5, 7, 10, 4, 6, 4, 6, 
                        8, 8, 8, 8, 8, 8, 8, 8, 8, 8, 4, 4, 10, 10, 10, 7, 
                        14, 10, 9, 9, 10, 8, 7, 10, 10, 5, 6, 9, 7, 13, 11, 11, 
                        8, 11, 9, 8, 8, 10, 9, 13, 9, 8, 9, 5, 11, 5, 10, 7, 
                        5, 8, 9, 7, 9, 8, 5, 9, 9, 4, 4, 8, 4, 12, 9, 9, 
                        9, 9, 6, 7, 5, 9, 7, 11, 7, 7, 7, 5, 4, 5, 10, 0),
            
            14 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 7, 7, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        7, 6, 8, 10, 10, 14, 13, 5, 6, 6, 8, 12, 5, 8, 5, 8, 
                        10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 5, 5, 12, 12, 12, 8, 
                        16, 11, 10, 11, 12, 9, 9, 12, 12, 6, 7, 10, 9, 15, 13, 13, 
                        10, 13, 11, 10, 9, 12, 11, 15, 10, 10, 10, 6, 13, 6, 12, 8, 
                        6, 9, 10, 9, 10, 10, 6, 10, 10, 5, 5, 9, 5, 14, 10, 10, 
                        10, 10, 7, 8, 7, 10, 9, 12, 9, 9, 8, 6, 5, 6, 12, 0),
            
            15 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 8, 8, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        8, 6, 8, 11, 10, 15, 14, 5, 7, 7, 8, 13, 5, 8, 5, 8, 
                        10, 10, 10, 10, 10, 10, 10, 10, 10, 10, 5, 5, 13, 13, 13, 9, 
                        17, 12, 11, 12, 13, 10, 9, 13, 13, 6, 7, 11, 9, 16, 13, 14, 
                        11, 14, 11, 10, 10, 13, 12, 16, 11, 10, 11, 7, 13, 7, 13, 8, 
                        6, 10, 11, 9, 11, 10, 7, 11, 11, 6, 6, 10, 6, 15, 11, 11, 
                        11, 11, 7, 9, 7, 11, 9, 13, 9, 9, 9, 7, 6, 7, 13, 0),
            
            16 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 9, 9, 0, 0, 0, 0, 0, 
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 
                        9, 8, 9, 13, 12, 16, 16, 7, 8, 8, 10, 14, 7, 10, 7, 9, 
                        12, 12, 12, 12, 12, 12, 12, 12, 12, 12, 7, 7, 14, 14, 14, 10, 
                        19, 14, 12, 13, 14, 11, 11, 14, 15, 7, 9, 12, 11, 18, 15, 15, 
                        12, 15, 13, 12, 12, 14, 13, 18, 13, 12, 12, 8, 15, 8, 14, 10, 
                        7, 11, 13, 11, 13, 12, 8, 13, 12, 7, 7, 11, 7, 17, 12, 13, 
                        13, 13, 9, 10, 9, 12, 11, 15, 10, 11, 10, 8, 7, 8, 14, 0),
        ),
    );
}
