<?php

require_once('vendor/php-matrix-decompositions/lib/Assertion.php');
require_once('vendor/php-matrix-decompositions/lib/Vector.php');
require_once('vendor/php-matrix-decompositions/lib/Matrix.php');
require_once('lib/DictionaryIO.php');
require_once('lib/Dictionary.php');

echo 'PHP Simplex<br>';
for ($i = 1; $i <= 10; $i++) {
    $dictionary = DictionaryIO::read('data/part' . $i . '.dict');
    
    $output = 'data/part' . $i . '.dict: ';
    if (!$dictionary->isFeasible()) {
        if ($dictionary->initialize() === FALSE) {
            $output .= 'INFEASIBLE';
        }
        else {
            $dictionary->optimize();

            if ($dictionary->isUnbounded()) {
                $output .= 'UNBOUNDED';
            }
            else {
                $output .= $dictionary->getc0();
            }
        }
    }
    elseif ($dictionary->isUnbounded()) {
        $output .= 'UNBOUNDED';
    }
    else {
        $optValue = $dictionary->optimize();

        if ($optValue === FALSE) {
            if ($dictionary->isUnbounded()) {
                $output .= 'UNBOUNDED';
            }
            elseif (!$dictionary->isFeasible()) {
                $output .= 'INFEASIBLE';
            }
        }
        else {
            $output .= $optValue;
        }
    }
    
    echo $output . '<br>';
}