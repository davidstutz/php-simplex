<?php

/**
 * Dictionary class.
 * 
 * This class represents a dictionary corresponding ot a linear program
 * 
 *  max  c^T x
 *  s.t. A x <= b
 * 
 * after introducing slack variables.
 * 
 * @author  David Stutz
 * @license http://www.gnu.org/licenses/gpl-3.0
 */
class Dictionary {
    
    /**
     * Constant indicating to use Bland's Rule.
     */
    const BLANDS_RULE = 0;
    
    /**
     * Constant indicating to use the Largest Coefficient Rule.
     */
    const LARGEST_COEFFICIENT_RULE = 1;
    
    /**
     * Matrix A of the linear program.
     */
    private $_A;
    
    /**
     * Vector c of the linear program.
     */
    private $_c;
    
    /**
     * Objective c_0 of dictionary.
     */
    private $_c0;
    
    /**
     * Vector b of the linear program.
     */
    private $_b;
    
    /**
     * Vector of basic variables where the i'th element contains the number of
     * the variable and its corresponding coefficients can be found in the
     * i'th _row_ of b and A.
     */
    private $_basic;
    
    /**
     * Vector of non basic variables where the i'th element contains the number
     * of the variable and its correpsonding coefficients can be found in
     * the i'th element of c and the i'th _column_ of A.
     * @var type 
     */
    private $_nonBasic;
    
    /**
     * Stores the history of entering/leaving variable analysis.
     */
    private $_history;
    
    /**
     * Constructs a dictionary from a linear program or by directly
     * defining all ingredients.
     * 
     * @param mixed linear program or objective value
     * 
     */
    public function __construct($mixed, $c = NULL, $A = NULL, $b = NULL, $nonBasic = NULL, $basic = NULL) {
        new Assertion($mixed instanceof LinearProgram OR is_numeric($mixed), 'First parameter needs to be an integer or instance of LinearProgram.');
        
        if ($mixed instanceof LinearProgram) {
            new Assertion($mixed instanceof LinearProgram AND $c === NULL AND $A === NULL
                    AND $b === NULL AND $nonBasic === NULL AND $basic === NULL, 'Invalid parameters.');
            
            $linearProgram = $mixed;
            $this->_A = $linearProgram->getA()->copy();
            $this->_c = $linearProgram->getc()->copy();
            $this->_c0 = 0;
            $this->_b = $linearProgram->getb()->copy();
            $this->_nonBasic = new Vector($this->_c->size());

            // Set initial non absic variables.
            for ($i = 0; $i < $this->_c->size(); $i++) {
                $this->_nonBasic->set($i, $i + 1);
            }

            $this->_basic = new Vector($this->_A->rows());

            // Set initial non basic variables.
            for ($i = 0; $i < $this->_A->rows(); $i++) {
                $this->_basic->set($i, $this->_c->size() + $i + 1);
            }
        }
        
        if (is_numeric($mixed)) {
            new Assertion(is_numeric($mixed) AND $c instanceof Vector AND $A instanceof Matrix
                    AND $b instanceof Vector AND $nonBasic instanceof Vector
                    AND $basic instanceof Vector, 'Invalid parameters.');
            
            $this->_c0 = $mixed;
            $this->_c = $c->copy();
            $this->_A = $A->copy();
            $this->_b = $b->copy();
            $this->_nonBasic = $nonBasic->copy();
            $this->_basic = $basic->copy();
        }
    }
    
    /**
     * Get the entering variable corresponding to the given rule.
     * 
     * @param int rule
     * @return int
     */
    public function identifyEnteringVariable($rule = Dictionary::BLANDS_RULE) {
        new Assertion($rule === Dictionary::BLANDS_RULE OR $rule === Dictionary::LARGEST_COEFFICIENT_RULE, 'Invalid rule provided.');
        
        $index = -1;
        $max = 0;
        
        for ($i = 0; $i < $this->_c->size(); $i++) {
            if ($this->_c->get($i) > 0) {
                if ($rule === Dictionary::BLANDS_RULE) {
                    if ($this->_nonBasic->get($i) < $index || $index < 0) {
                        $index = $this->_nonBasic->get($i);
                    }
                }
                else if ($rule === Dictionary::LARGEST_COEFFICIENT_RULE) {
                    if ($this->_c->get($i) > $max) {
                        $max = $this->_c->get($i);
                        $index = $this->_nonBasic->get($i);
                    }
                }
            }
        }
        
        new Assertion($index >= 0, 'Could not identify entering variable as dictionary may be final.');
        
        return $index;
    }
    
    /**
     * Given the entering variable, this method identifies the corresponding leaving
     * variable with the given rule.
     * 
     * @param int entering
     * @param int rule
     * @return int
     */
    public function identifyLeavingVariable($entering, $rule = Dictionary::BLANDS_RULE) {
        
        $entering = (int) $entering;
        new Assertion(is_int($entering), 'Entering variable needs to be of type integer.');
        
        // Find index of entering variable.
        $enteringIndex = $this->getNonBasicVariableIndex($entering);
        
        $min = -1;
        $index = -1;
        
        for ($i = 0; $i < $this->_b->size(); $i++) {
            
            new Assertion($this->_b->get($i) >= 0, 'Right hand side has to be greater than zero - possibly infeasible!.');
            if ($this->_A->get($i, $enteringIndex) < 0 AND $this->_b->get($i) > 0) {
                
                // b_i is always positive for feasible dictionary, a_ij is negative.
                $constraint = $this->_b->get($i) / abs($this->_A->get($i, $enteringIndex));
                new Assertion($constraint > 0, 'Constraint on increasing entering variable has to be greater than zero.');
                
                if ($rule === Dictionary::BLANDS_RULE) {
                    if ($constraint <= $min OR $min < 0) {
                        if ($constraint < $min OR ($this->_basic->get($i) < $index OR $index < 0)) {
                            $min = $constraint;
                            $index = $this->_basic->get($i);
                        }
                    }
                }
            }
        }
        
        new Assertion($index >= 0, 'Could not identify leaving variable as dictionary may be unbounded.');
        
        return $index;
    }
    
    /**
     * After identifying the entering and leaving variable, this method performs
     * the corresponding row operations, that is one step of the Simplex algorithm.
     * 
     * @param int entering
     * @param int leaving
     */
    public function performRowOperations($entering, $leaving) {
        $entering = (int) $entering;
        $leaving = (int) $leaving;
        
        new Assertion(is_int($entering), 'Entering variable needs to be of type integer.');
        new Assertion(is_int($leaving), 'Leaving variable needs to be of type integer.');
        
        $enteringIndex = $this->getNonBasicVariableIndex($entering);
        $leavingIndex = $this->getBasicVariableIndex($leaving);
        
        // First, solve $leavingIndex's row in A and b for the $leavingIndex's
        // non-basic variable.
        $a_leaving_entering = $this->_A->get($leavingIndex, $enteringIndex);
        $b_leaving = $this->_b->get($leavingIndex);
        
        // All values in b should be equal or greater than zero.
        new Assertion($b_leaving > 0, 'Vector b should be greater or equal to zero, dictionary may be infeasible.');
        
        $a_leaving = new Vector($this->_A->columns());
        $c_entering = $this->_c->get($enteringIndex);
        
        for ($i = 0; $i < $this->_A->columns(); $i++) {
            $a_leaving->set($i, $this->_A->get($leavingIndex, $i));
        }
        
        // Update b vector.
        for ($i = 0; $i < $this->_b->size(); $i++) {
            if ($i == $leavingIndex) {
                // b_i does not change.
            }
            else {
                $this->_b->set($i, $this->_b->get($i) + $this->_A->get($i, $enteringIndex)*$b_leaving);
            }
        }
        
        // Update A matrix.
        for ($i = 0; $i < $this->_A->rows(); $i++) {
            if ($i == $leavingIndex) {
                // This row needs to be solved for the entering variable.
                for ($j = 0; $j < $this->_A->columns(); $j++) {
                    if ($j == $enteringIndex) {
                        $this->_A->set($i, $j, 1.0/$a_leaving_entering);
                    }
                    else {
                        $this->_A->set($i, $j, $this->_A->get($i, $j)/(-1.0*$a_leaving_entering));
                    }
                }
            }
            else {
                $a_i_entering = $this->_A->get($i, $enteringIndex);
                for ($j = 0; $j < $this->_A->columns(); $j++) {
                    if ($j == $enteringIndex) {
                       $this->_A->set($i, $enteringIndex, $a_i_entering*(1.0/$a_leaving_entering));
                    }
                    else {
                        $this->_A->set($i, $j, $a_i_entering*($a_leaving->get($j)/(-1.0*$a_leaving_entering)) + $this->_A->get($i, $j));
                    }
                }
            }
        }
        
        // Update c vector.
        for ($i = 0; $i < $this->_c->size(); $i++) {
            if ($i == $enteringIndex) {
                $this->_c->set($i, $this->_c->get($i)*(1.0/$a_leaving_entering));
            }
            else {
                $this->_c->set($i, $c_entering*($a_leaving->get($i)/(-1.0*$a_leaving_entering)) + $this->_c->get($i));
            }
        }
        
        // Update objective value c_0.
        $this->_c0 = $this->_c0 + $c_entering*$b_leaving/(-1.0*$a_leaving_entering);
        
        $this->_nonBasic->set($enteringIndex, $leaving);
        $this->_basic->set($leavingIndex, $entering);
        
        // Update history;
        $this->_history[] = array($entering, $leaving);
    }
    
    /**
     * Get last leaving variable.
     * 
     * @return int
     */
    public function getLatestEnteringVariable() {
        return $this->_history[sizeof($this->_history) - 1][0];
    }
    
    /**
     * Get last leaving variable.
     * 
     * @return int
     */
    public function getLatestLeavingVariable() {
        return $this->_history[sizeof($this->_history) - 1][1];
    }
    
    /**
     * Gets the index of the given basic variable within the basic variable vector.
     * 
     * @param int basic
     * @return int
     */
    public function getBasicVariableIndex($basic) {
        // Find index of entering variable.
        $index = -1;
        for ($i = 0; $i < $this->_basic->size(); $i++) {
            if ($this->_basic->get($i) == $basic) {
                $index = $i;
            }
        }
        
        new Assertion($index >= 0, 'Basic variable index needs to be greater or equal to 0.');
        return $index;
    }
    
    /**
     * Gets the index of the given non-basic variable within the non-basic
     * variable vector.
     * 
     * @param int nonBasic
     * @return int
     */
    public function getNonBasicVariableIndex($nonBasic) {
        // Find index of entering variable.
        $index = -1;
        for ($i = 0; $i < $this->_nonBasic->size(); $i++) {
            if ($this->_nonBasic->get($i) == $nonBasic) {
                $index = $i;
            }
        }
        
        new Assertion($index >= 0, 'Non-basic variable index needs to be greater or equal to 0.');
        return $index;
    }
    
    /**
     * Check whether the dictionary is feasible.
     * 
     * @return boolean
     */
    public function isFeasible() {
        for ($i = 0; $i < $this->_b->size(); $i++) {
            if ($this->_b->get($i) < 0) {
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    /**
     * Check whether the dictionary is unbounded.
     * 
     * @return boolean
     */
    public function isUnbounded() {
        for ($i = 0; $i < $this->_c->size(); $i++) {
            if ($this->_c->get($i) > 0) {
                $unbounded = TRUE;
                for ($j = 0; $j < $this->_A->rows(); $j++) {
                    // b_i > 0 for feasible dictionary, so only check the
                    // sign of the coefficients.
                    if ($this->_A->get($j, $i) < 0) {
                        $unbounded = FALSE;
                    }
                }
                
                if ($unbounded === TRUE) {
                    return TRUE;
                }
            }
        }
        
        return FALSE;
    }
    
    /**
     * Check whether the dictionary is final.
     * 
     * @return boolean
     */
    public function isFinal() {
        for ($i = 0; $i < $this->_c->size(); $i++) {
            if ($this->_c->get($i) > 0) {
                return FALSE;
            }
        }
        
        return TRUE;
    }
    
    /**
     * Get the current objective value.
     * 
     * @return double
     */
    public function getc0() {
        return $this->_c0;
    }
    
    /**
     * Get coefficient matrix of dictionary.
     * 
     * @return Matrix
     */
    public function getA() {
        return $this->_A;
    }
    
    /**
     * Get constraint matrix of dictionary, these correspond to the current solution
     * of the basic variables.
     * 
     * @return Vector
     */
    public function getb() {
        return $this->_b;
    }
    
    /**
     * Get coefficients of objective.
     * 
     * @return type
     */
    public function getc() {
        return $this->_c;
    }
    
    /**
     * Get vector of basic variables.
     * 
     * @return type
     */
    public function getBasicVariables() {
        return $this->_basic;
    }
    
    /**
     * Get vector of non-basic variables.
     * 
     * @return type
     */
    public function getNonBasicVariables() {
        return $this->_nonBasic;
    }
}