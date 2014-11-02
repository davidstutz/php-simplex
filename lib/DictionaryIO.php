<?php

/**
 * Class DIctionaryIO.
 * 
 * Provides capabilities to read/write dictionaries from/to files. The format
 * is as follows:
 * 
 *  [Line 1]     m n 
 *  [Line 2]     B_1 B_2 ... B_m [the list of basic indices m integers] 
 *  [Line 3]     N_1 N_2 ... N_n [the list of non-basic indices n integers] 
 *  [Line 4]     b_1 .. b_m
 *  [Line 5]     a_11 ... a_1n
 *  ... 
 *  [Line m + 4] a_m1 ... a_mn
 *  [Line m + 5] c_0 c_1 .. c_n
 * 
 * Here m is the number of basic variables and n is the number of non-basic
 * variables. B_1 to B_m are the indices of the basic variables; N_1 to N_n are
 * the indices of the non-basic variables.
 * 
 * @author  David Stutz
 * @license http://www.gnu.org/licenses/gpl-3.0
 */
class DictionaryIO {
    
    /**
     * Read a dictionary from the given file.
     * 
     * @param string file
     * @return Dictionary
     */
    public static function read($file) {
        new Assert(file_exists($file), 'File does not exist.');
        
        $content = file_get_contents($file);
        $lines = explode("\n", $content);
        
        new Assertion(sizeof($lines) >= 6, 'File has to have at least 6 lines.');
        
        // Check first row (there have to be two integers).
        $m_n = explode(' ', trim($lines[0]));
        new Assertion(sizeof($m_n) == 2, 'First line has to have the format: [Line 1] m n where m and n are integers.');
        
        $m = $lines[0][0];
        $n = $lines [0][1];
        new Assertion(is_int($m), 'First line has to have two integers.');
        new Assertion(is_int($n), 'First line has to have two integers.');
        
        // Second line are indices of basic variables.
        $basic = explode(' ', trim($lines[1]));
        new Assertion(sizeof($basic) == $m, 'Second line has to have m integers.');
        
        // Third line are indices of non-basic variables.
        $basic = explode(' ', trim($lines[2]));
        new Assertion(sizeof($basic) == $n, 'Third line has to have m integers.');
        
        // Fourth lines stores constraints (b vector).
        $b = explode(' ', trim($lines[3]));
        new Assertion(sizeof($b) == $m, 'Fourth line has to have m floating point numbers.');
        
        // Before grabbing the matrix A, check that enough lines are there.
        new Assertion(sizeof($lines) == $m + 5, 'The file has to have m + 5 lines.');
        
        $A = array();
        for ($i = 0; $i < $m; $i++) {
            $A[$i] = explode(' ', trim($lines[$i + 4]));
            
            // Each row of A has to have n floating point numbers.
            new Assertion(sizeof($A[$i]) == $n, 'Each row of A has to have n entries.');
        }
        
        // Last line, has to store obejctive value and objective coefficients.
        $c = explode(' ', trim($lines[$m + 4]));
        new Assertion(sizeof($c) = $n + 1, 'Last line has to have n + 1 entries.');
        
        
    }
    
    public static function write($dictionary, $file) {
        new Assertion($dictionary instanceof Dictionary, 'Given dictionary has to be instance of Dictionary.');
        new Assert(file_exists($file), 'File does not exist.');
        
        $content = $dictionary->getBasicVariables()->size() . ' ' . $dictionary->getNonBasicVariables()->size() . "\n";
        
        for ($i = 0; $i < $dictionary->getBasicVariables()->size(); $i++) {
            $content .= $dictionary->getBasicVariables()->get($i) . ' ';
        }
        $content .= "\n";
        
        for ($i = 0; $i < $dictionary->getNonBasicVariables()->size(); $i++) {
            $content .= $dictionary->getNonBasicVariables()->get($i) . ' ';
        }
        $content .= "\n";
        
        for ($i = 0; $i < $dictionary->getb()->size(); $i++) {
            $content .= $dictionary->getb()->get($i) . ' ';
        }
        $content .= "\n";
        
        for ($i = 0; $i < $dictionary->getA()->rows(); $i++) {
            for ($j = 0; $j < $dictionary->getA()->columns(); $j++) {
                $content .= $dictionary->getA()->get($i, $j) . ' ';
            }
            $content .= "\n";
        }
        
        $content .= $dictionary->getc0() . ' ';
        for ($i = 0; $i < $dictionary->getc()->size(); $i++) {
            $content .= $dictionary->getc()->get($i);
        }
        
        file_put_contents($file, $content);
    }
}