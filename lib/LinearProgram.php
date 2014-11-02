<?php

/**
 * Represents a linear program in standard form:
 * 
 *  max c^T x
 *  s.t. AX <= b
 *       x  >= 0
 * 
 * @author  David Stutz
 * @license http://www.gnu.org/licenses/gpl-3.0
 */
class LinearProgram {
    
    /**
     * The matrix A storing the coefficients for all the constraints.
     */
    private $_A;
    
    /**
     * Right hand vector b of contraints.
     */
    private $_b;
    
    /**
     * Coefficients c of objective.
     */
    private $_c;
    
    /**
     * Construct a linear program in standard form.
     * 
     * @param Vector $c
     * @param Matrix $A
     * @param Vector $b
     */
    function __construct($c, $A, $b) {
        new Assertion($c instanceof Vector, 'Coefficients of objective need to be given as Vector.');
        new Assertion($A instanceof Matrix, 'Coefficients of constraints need to be assembled in a Matrix.');
        new Assertion($b instanceof Vector, 'Right hand side of constraints needs to be given as Vector.');
        
        new Assertion($b->size() == $A->rows(), 'Size of vector b needs to equal number of rows of matrix A.');
        new Assertion($A->columns() == $c->size(), 'Number of columns of A needs to be equal to size of vector c.');
        
        $this->_c = $c;
        $this->_A = $A;
        $this->_b = $b;
    }
    
    /**
     * Get a dictionary based on this linear program.
     * 
     * @return Dictionary
     */
    function getDictionary() {
        return new Dictionary($this);
    }
    
    /**
     * Get objective coefficients.
     * 
     * @return type
     */
    function getc() {
        return $this->_c;
    }
    
    /**
     * Get constraint coefficients.
     * 
     * @return type
     */
    function getA() {
        return $this->_A;
    }
    
    /**
     * Get constraints.
     * 
     * @return type
     */
    function getb() {
        return $this->_b;
    }
}