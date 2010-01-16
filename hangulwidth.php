<?php

/**
 * Hangul Width Calculator.
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
 * URL: http://github.com/kijin/hangulwidth
 * Version: 0.1.0
 * 
 * Copyright (c) 2010, Kijin Sung <kijinbear@gmail.com>
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN
 * THE SOFTWARE.
 */

class HangulWidth
{
    /**
     * Constructor.
     * 
     * @param  string  The name of the font, e.g. 'Gulim'.
     * @param  mixed   The size of the font, e.g. '9pt', '12px'.
     */
    
    public function __construct($font, $size)
    {
        // Standardize the font name.
        
        switch ($font)
        {
            case 'Gulim':
            case 'gulim':
            case '굴림':
                $this->font = 'Gulim';
                break;
                
            case 'Malgun Gothic':
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
            $this->size = $matches[1] + 3;
        }
        elseif (preg_match('/^(\\d+)px$/', $size, $matches))
        {
            $this->size = $matches[1];
        }
        
        // If unsupported, throw an exception.
        
        if (!isset($this->widths[$this->font][$this->size])) throw new Exception('Unsupported font and/or size: ' . $font . ' ' . $size);
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
    
    
    /**
     * Width data for common Korean fonts.
     */
    
    private $widths = array(
        
        'Gulim' => array(
            
            11 => array(0),
            
            12 => array(0, 0, 0, 0, 0, 0, 0, 0, 0, 4, 4, 0, 0, 0, 0, 0,
                        0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0,
                        4, 4, 4, 7, 6, 10, 8, 4, 5, 5, 6, 6, 4, 6, 4, 7,
                        6, 6, 6, 6, 6, 6, 6, 6, 6, 6, 4, 4, 8, 6, 8, 6,
                        12, 8, 8, 9, 8, 8, 7, 9, 8, 3, 7, 8, 7, 11, 9, 9,
                        8, 9, 8, 8, 8, 8, 8, 10, 8, 8, 8, 6, 12, 6, 6, 6,
                        4, 7, 7, 7, 7, 7, 4, 7, 7, 3, 4, 6, 3, 11, 7, 7,
                        7, 7, 4, 7, 4, 7, 7, 10, 7, 7, 7, 6, 6, 6, 9, 0),
            
            13 => array(0),
            
        ),
        
        'Batang' => array(
            
            11 => array(0),
            
            12 => array(0),
            
            13 => array(0),
            
        ),
        
        'Malgun Gothic' => array(
            
            11 => array(0),
            
            12 => array(0),
            
            13 => array(0),
            
        ),
    );
}
