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
    public function __construct($c0, $c, $A, $b, $nonBasic, $basic) {
        new Assertion(is_numeric($c0), 'Value c_0 needs to be numeric.');
        new Assertion($c instanceof Vector, 'c needs to be of type Vector.');
        new Assertion($A instanceof Matrix, 'A needs to be of type Matrix.');
        new Assertion($b instanceof Vector, 'b needs to be of type Vector.');
        new Assertion($nonBasic instanceof Vector, 'Non basic variables need to be of type Vector.');
        new Assertion($basic instanceof Vector, 'Basic variables need to be of type Vector.');
            
        $this->_c0 = $c0;
        $this->_c = $c->copy();
        $this->_A = $A->copy();
        $this->_b = $b->copy();
        $this->_nonBasic = $nonBasic->copy();
        $this->_basic = $basic->copy();
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
            if ($this->_A->get($i, $enteringIndex) < 0) {
                
                // b_i is always positive for feasible dictionary, a_ij is negative.
                $constraint = $this->_b->get($i) / abs($this->_A->get($i, $enteringIndex));
                //new Assertion($constraint > 0, 'Constraint on increasing entering variable has to be greater than zero.');
                
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
     * Get the "most infeasible" variable for the auxiliary problem.
     */
    public function identifyMostInfeasibleVariable() {
        $index = -1;
        // There will be a b_i < 0 as the dictionary would be feasible
        // otherwise.
        $min = 0;
        
        for ($i = 0; $i < $this->_b->size(); $i++) {
            if ($this->_b->get($i) < $min) {
                $min = $this->_b->get($i);
                $index = $this->_basic->get($i);
            }
        }
        
        new Assertion($index > 0, 'Could not identify most infeasible basic variable - dictionary may be feasible.');
        
        return $index;
    }
    
    /**
     * After identifying the entering and leaving variable, this method performs
     * the corresponding row operations, that is one step of the Simplex algorithm.
     * 
     * @param int entering
     * @param int leaving
     */
    public function performRowOperations($entering, $leaving, $aux = FALSE) {
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
        
        new Assertion($a_leaving_entering < 0, 'Entering/leaving coefficient should be less than zero.');
        
        if ($aux === TRUE) {
            // When an auxiliary problem is given, the leaving variables has the
            // most negative value.
            new Assertion($b_leaving < 0, 'Leaving variable is not negative. Auxiliary problem may not be setup correctly.');
        }
        else {
            // All values in b should be equal or greater than zero.
            new Assertion($b_leaving >= 0, 'Vector b should be greater or equal to zero, dictionary may be infeasible.');
        }
        
        $a_leaving = new Vector($this->_A->columns());
        $c_entering = $this->_c->get($enteringIndex);
        
        for ($i = 0; $i < $this->_A->columns(); $i++) {
            $a_leaving->set($i, $this->_A->get($leavingIndex, $i));
        }
        
        // Update b vector.
        for ($i = 0; $i < $this->_b->size(); $i++) {
            if ($i == $leavingIndex) {
                $this->_b->set($i, $b_leaving/(-1.0*$a_leaving_entering));
            }
            else {
                $this->_b->set($i, $this->_b->get($i) + $this->_A->get($i, $enteringIndex)*$b_leaving/(-1.0*$a_leaving_entering));
            }
        }
        
        // Update A matrix.
        for ($i = 0; $i < $this->_A->rows(); $i++) {
            if ($i == $leavingIndex) {
                // This row needs to be solved for the entering variable.
                for ($j = 0; $j < $this->_A->columns(); $j++) {
                    if ($j == $enteringIndex) {
                        $this->_A->set($i, $j, 1.0/(1.0*$a_leaving_entering));
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
                       $this->_A->set($i, $j, $a_i_entering*(1.0/(1.0*$a_leaving_entering)));
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
                $this->_c->set($i, $this->_c->get($i)*(1.0/(1.0*$a_leaving_entering)));
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
    
    public function getAuxiliaryDictionary() {
        $nonBasic = new Vector($this->_nonBasic->size() + 1);
        
        // Add helper variable x_0:
        $nonBasic->set(0, 0);
        for ($i = 0; $i < $this->_nonBasic->size(); $i++) {
            $nonBasic->set($i + 1, $this->_nonBasic->get($i));
        }
        
        // Form new coefficient matrix where the first column has only 1's
        // correpsonding to the helper variable x_0.
        $A = $this->_A->copy();
        $A->resize($this->_A->rows(), $this->_A->columns() + 1);
        for ($i = 0; $i < $this->_A->rows(); $i++) {
            $A->set($i, 0, 1);
            for ($j = 0; $j < $this->_A->columns(); $j++) {
                $A->set($i, $j + 1, $this->_A->get($i, $j));
            }
        }
        
        $c = new Vector($this->_nonBasic->size() + 1);
        $c->set(0, -1);
        for ($i = 1; $i < $c->size(); $i++) {
            $c->set($i, 0);
        }
        
        return new Dictionary(0, $c, $A, $this->_b, $nonBasic, $this->_basic);
    }
    
    /**
     * Initialize: If the dictionary is not feasible, create the auxiliary 
     * problem and optimize it.
     */
    public function initialize() {
        $auxDictionary = $this->getAuxiliaryDictionary();
        
        // Helper variable will be entering
        $entering = 0;
        $leaving = $auxDictionary->identifyMostInfeasibleVariable();
        $auxDictionary->performRowOperations($entering, $leaving, TRUE);
        echo $auxDictionary;
        // Now the auxiliary dictionary should be feasible.
        new Assertion($auxDictionary->isFeasible(), 'Auxiliary problem is not feasible after "magic" pivoting step.');
        $optValue = $auxDictionary->optimize();
        
        $helperVariableColumn = -1;
        for ($i = 0; $i < $this->_nonBasic->size(); $i++) {
            if ($this->_nonBasic->get($i)) {
                $helperVariableColumn = $i;
            }
        }
        
        if ($optValue == 0 AND $helperVariableColumn >= 0) {
            // Original problem is feasible; change the dictionary to
            // the feasible version.
            
            // First, udpate the amtrix A:
            for ($i = 0; $i < $auxDictionary->getA()->rows(); $i++) {
                for ($j = 0; $j < $auxDictionary->getA()->columns(); $j++) {
                    if ($j == $helperVariableColumn) {
                        // Nothing to do here ...
                    }
                    else if ($j > $helperVariableColumn) {
                        $this->_A->set($i, $j - 1, $auxDictionary->getA()->get($i, $j));
                    }
                    else {
                        $this->_A->set($i, $j, $auxDictionary->getA()->get($i, $j));
                    }
                }
            }
            
            // Save old non basic variables for updating objective.
            $oldNonBasic = $this->_nonBasic->copy();
            
            // Update non basic variables.
            for ($i = 0; $i < $auxDictionary->getNonBasicVariables(); $i++) {
                if ($i == $helperVariableColumn) {
                    // NOthing to do here ...
                }
                else if ($i > $helperVariableColumn) {
                    $this->_nonBasic->set($i - 1, $auxDictionary->getNonBasicVariables()->get($i));
                }
                else {
                    $this->_nonBasic->set($i, $auxDictionary->getNonBasicVariables()->get($i));
                }
            }
            
            // Basic variables can simply be copied.
            for ($i = 0; $i < $auxDictionary->getBasicVariables(); $i++) {
                $this->_basic->set($i, $auxDictionary->getBasicVariables()->get($i));
            }
            
            // As x_0 is non basic, b can be copied as well.
            for ($i = 0; $i < $auxDictionary->getb(); $i++) {
                $this->_b->set($i, $auxDictionary->getb()->get($i));
            }
            
            // Reset obejctive to zero.
            $this->_c0 = 0;
            
            // Updateing the obejct is a bit more complicated; original objective
            // may include basic variables of the new dictionary.
            $newC = new Vector($this->_nonBasic->size());
            $newC->setAll(0);
            
            // First, set all non basic variables which are non absic in the old
            // objective as well.
            for ($i = 0; $i < $this->_nonBasic->size(); $i++) {
                
                $nonBasicIndex = -1;
                for ($j = 0; $j < $oldNonBasic->size(); $j++) {
                    if ($oldNonBasic->get($j) == $this->_nonBasic->get($i)) {
                        $nonBasicIndex = $j;
                    }
                }
                
                if ($nonBasicIndex >= 0) {
                    $newC->set($i, $this->_c->get($nonBasicIndex));
                }
            }
            
            // Now substitute ...
            for ($i = 0; $i < $this->_c->size(); $i++) {
                
                // Check whether the old non basic variable is basic now.
                $basicIndex = -1;
                for ($j = 0; $j < $this->_basic->size(); $j++) {
                    if ($oldNonBasic->get($i) == $this->_basic($j)) {
                        $basicIndex = $j;
                    }
                }
                
                if ($basicIndex >= 0) {
                    $c_index = $this->_c->get($i);
                    
                    for ($j = 0; $j < $newC->size(); $j++) {
                        $newC->set($j, $c_index*$this->_A->get($basicIndex, $j));
                    }
                }
            }
            
            return TRUE;
        }
        else {
            // Original problem infeasible.
            return FALSE;
        }
    }
    
    /**
     * Givne a feasible dictionary, it can be optimized until a final
     * dictionary is detected.
     */
    public function optimize() {
        while (FALSE === $this->isFinal()) {
            if (!$this->isFeasible()) {
                return FALSE;
            }
            
            if ($this->isUnbounded()) {
                return FALSE;
            }
            
            $entering = $this->identifyEnteringVariable();
            $leaving = $this->identifyLeavingVariable($entering);
            $this->performRowOperations($entering, $leaving);
        }
        
        return $this->_c0;
    }
    
    /**
     * Get history.
     * 
     * @return array
     */
    public function getHistory() {
        return $this->_history;
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
     * @return Vector
     */
    public function getBasicVariables() {
        return $this->_basic;
    }
    
    /**
     * Get vector of non-basic variables.
     * 
     * @return Vector
     */
    public function getNonBasicVariables() {
        return $this->_nonBasic;
    }
    
    /**
     * toString method for printing.
     * 
     * @return string
     */
    public function __toString() {
        $string = $this->_nonBasic->size() . ' ' . $this->_basic->size() . "\n";
        
        for ($i = 0; $i < $this->_basic->size(); $i++) {
            $string .= $this->_basic->get($i) . ' ';
        }
        $string .= "\n";
        
        for ($i = 0; $i < $this->_nonBasic->size(); $i++) {
            $string .= $this->_nonBasic->get($i) . ' ';
        }
        $string .= "\n";
        
        for ($i = 0; $i < $this->_b->size(); $i++) {
            $string .= $this->_b->get($i) . ' ';
        }
        $string .= "\n";
        
        for ($i = 0; $i < $this->_A->rows(); $i++) {
            for ($j = 0; $j < $this->_A->columns(); $j++) {
                $string .= $this->_A->get($i, $j) . ' ';
            }
            $string .= "\n";
        }
        
        $string .= $this->_c0;
        for ($i = 0; $i < $this->_c->size(); $i++) {
            $string .= $this->_c->get($i);
        }
        $string .= "\n";
        
        return $string;
    }
}