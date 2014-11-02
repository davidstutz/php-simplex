<?php

/**
 * Test cases for the Dictionary class.
 * 
 * @author  David Stutz
 * @license http://www.gnu.org/licenses/gpl-3.0
 */
class DictionaryTest extends \PHPUnit_Framework_TestCase {
    
    /**
     * Helper function to get a LInearProgram from the given arrays which
     * correspond to c, A and b.
     * 
     * @return  LinearProgram
     */
    public function linearProgramFromArrays($cData, $AData, $bData) {
        $c = new Vector(sizeof($cData));
        $c->fromArray($cData);
        
        $A = new Matrix(sizeof($AData), sizeof($AData[0]));
        $A->fromArray($AData);
        
        $b = new Vector(sizeof($bData));
        $b->fromArray($bData);
        
        return new LinearProgram($c, $A, $b);
    }
    
    /**
     * Provides data for testing constructor.
     * 
     * @return  array   data
     */
    public function providerConstruct() {
        return array(
            array(
                // c vector.
                array(1, 1),
                // A matrix
                array(
                    array(1, 0),
                    array(0, 1),
                ),
                // b vector.
                array(5, 10),
            )
        );
    }
    
    /**
     * Tests the constructor.
     * 
     * @test
     * @dataProvider providerConstruct
     * @param   array cData
     * @param   array AData
     * @param   array bData
     */
    public function testConstruct($cData, $AData, $bData) {
        $linearProgram = $this->linearProgramFromArrays($cData, $AData, $bData);
        
        $dictionary = new Dictionary($linearProgram);
        
        $this->assertSame($dictionary->getBasicVariables()->asArray(), array(3, 4));
        $this->assertSame($dictionary->getNonBasicVariables()->asArray(), array(1, 2));
        $this->assertSame($dictionary->getc0(), 0);
        $this->assertSame($dictionary->getc()->size(), $linearProgram->getc()->size());
        $this->assertSame($dictionary->getA()->rows(), $linearProgram->getA()->rows());
        $this->assertSame($dictionary->getA()->columns(), $linearProgram->getA()->columns());
        $this->assertSame($dictionary->getb()->size(), $linearProgram->getb()->size());
    }
    
    /**
     * Provides data for testing identifyEnteringVariable.
     * 
     * @return  array   data
     */
    public function providerIdentifyEnteringVariableBland() {
        return array(
            array(
                array(1, -1),
                array(
                    array(1, 0),
                    array(0, 1),
                ),
                array(5, 10),
                1, // Entering variable.
            ),
            array(
                array(-1, 1),
                array(
                    array(1, 0),
                    array(0, 1),
                ),
                array(5, 10),
                2, // Entering variable.
            ),
        );
    }
    
    /**
     * Tests identifyEnteringVariable.
     * 
     * @test
     * @dataProvider providerIdentifyEnteringVariableBland
     * @param   array cData
     * @param   array AData
     * @param   array bData
     * @partam  int   entering
     */
    public function testIdentifyEnteringVariableBland($cData, $AData, $bData, $entering) {
        $linearProgram = $this->linearProgramFromArrays($cData, $AData, $bData);
        $dictionary = new Dictionary($linearProgram);
        
        $this->assertSame($entering, $dictionary->identifyEnteringVariable());
    }
    
    /**
     * Provides data for testing identifyEnteringVariable.
     * 
     * @return  array   data
     */
    public function providerIdentifyEnteringVariableLargestCoefficient() {
        return array(
            array(
                array(2, 1),
                array(
                    array(1, 0),
                    array(0, 1),
                ),
                array(5, 10),
                1, // Entering variable.
            ),
            array(
                array(1, 2),
                array(
                    array(1, 0),
                    array(0, 1),
                ),
                array(5, 10),
                2, // Entering variable.
            ),
        );
    }
    
    /**
     * Tests identifyEnteringVariable.
     * 
     * @test
     * @dataProvider providerIdentifyEnteringVariableLargestCoefficient
     * @param   array cData
     * @param   array AData
     * @param   array bData
     * @param   int   entering
     */
    public function testIdentifyEnteringVariableLargestCoefficient($cData, $AData, $bData, $entering) {
        $linearProgram = $this->linearProgramFromArrays($cData, $AData, $bData);
        $dictionary = new Dictionary($linearProgram);
        
        $this->assertSame($entering, $dictionary->identifyEnteringVariable(Dictionary::LARGEST_COEFFICIENT_RULE));
    }
    
    /**
     * Provides data for testing identifyEnteringVariable.
     * 
     * @return  array   data
     */
    public function providerIdentifyEnteringVariableException() {
        return array(
            array(
                array(-1, -1),
                array(
                    array(1, 0),
                    array(0, 1),
                ),
                array(5, 10),
                -1, // Entering variable.
            ),
        );
    }
    
    /**
     * Tests identifyEnteringVariable to throw exception.
     * 
     * @test
     * @dataProvider providerIdentifyEnteringVariableException
     * @expectedException InvalidArgumentException
     * @param   array cData
     * @param   array AData
     * @param   array bData
     * @param   int   entering
     */
    public function testIdentifyEnteringVariableException($cData, $AData, $bData, $entering) {
        $linearProgram = $this->linearProgramFromArrays($cData, $AData, $bData);
        $dictionary = new Dictionary($linearProgram);
        
        $this->assertSame($entering, $dictionary->identifyEnteringVariable());
    }
    
    /**
     * Provides data for testing isFeasible.
     * 
     * @return  array   data
     */
    public function providerIsFeasible() {
        return array(
            array(
                array(1, 1),
                array(
                    array(1, 0),
                    array(0, 1),
                ),
                array(5, 10),
                TRUE,
            ),
            array(
                array(1, 1),
                array(
                    array(1, 0),
                    array(0, 1),
                ),
                array(5, 0),
                TRUE,
            ),
            array(
                array(1, 1),
                array(
                    array(1, 0),
                    array(0, 1),
                ),
                array(0, 5),
                TRUE,
            ),
            array(
                array(1, 1),
                array(
                    array(1, 0),
                    array(0, 1),
                ),
                array(-1, 10),
                FALSE,
            ),
            array(
                array(1, 1),
                array(
                    array(1, 0),
                    array(0, 1),
                ),
                array(5, -1),
                FALSE,
            ),
        );
    }
    
    /**
     * Tests isFeasible to throw exception.
     * 
     * @test
     * @dataProvider providerIsFeasible
     * @param   array cData
     * @param   array AData
     * @param   array bData
     * @param   int   feasible
     */
    public function testIsFeasible($cData, $AData, $bData, $feasible) {
        $linearProgram = $this->linearProgramFromArrays($cData, $AData, $bData);
        $dictionary = new Dictionary($linearProgram);
        
        $this->assertSame($feasible, $dictionary->isFeasible());
    }
    
    /**
     * Provides data for testing IsUnbounded.
     * 
     * @return  array   data
     */
    public function providerIsUnbounded() {
        return array(
            array(
                array(1, 1),
                array(
                    array(1, 0),
                    array(0, 1),
                ),
                array(5, 10),
                TRUE,
            ),
            array(
                array(1, 1),
                array(
                    array(-1, 0),
                    array(0, -1),
                ),
                array(5, 10),
                FALSE,
            ),
        );
    }
    
    /**
     * Tests IsUnbounded to throw exception.
     * 
     * @test
     * @dataProvider providerIsUnbounded
     * @param   array cData
     * @param   array AData
     * @param   array bData
     * @param   int   unbounded
     */
    public function testIsUnbounded($cData, $AData, $bData, $unbounded) {
        $linearProgram = $this->linearProgramFromArrays($cData, $AData, $bData);
        $dictionary = new Dictionary($linearProgram);
        
        $this->assertSame($unbounded, $dictionary->isUnbounded());
    }
    
    /**
     * Provides data for testing IsUnbounded.
     * 
     * @return  array   data
     */
    public function providerIdentifyLeavingVariableBland() {
        return array(
            array(
                array(1, 1),
                array(
                    array(-1, -1),
                    array(-1, -1),
                ),
                array(5, 10),
                1,
                3,
            ),
            array(
                array(1, 1),
                array(
                    array(-1, -1),
                    array(-1, -1),
                ),
                array(10, 5),
                1,
                4,
            ),
            array(
                array(1, 1),
                array(
                    array(-1, -1),
                    array(-1, -1),
                ),
                array(10, 10),
                1,
                3,
            ),
        );
    }
    
    /**
     * Tests IsUnbounded to throw exception.
     * 
     * @test
     * @dataProvider providerIdentifyLeavingVariableBland
     * @param   array cData
     * @param   array AData
     * @param   array bData
     * @param   int   entering
     * @param   int   leaving
     */
    public function testIdentifyLeavingVariableBland($cData, $AData, $bData, $entering, $leaving) {
        $linearProgram = $this->linearProgramFromArrays($cData, $AData, $bData);
        $dictionary = new Dictionary($linearProgram);
        
        $this->assertSame($leaving, $dictionary->identifyLeavingVariable($entering));
    }
    
    /**
     * Provides data for testing IsUnbounded.
     * 
     * @return  array   data
     */
    public function providerPerformRowOperations() {
        return array(
            array(
                array(1, 1),
                array(
                    array(-1, -1),
                    array(-1, -1),
                ),
                array(5, 10),
                0, // c_0
                1, // entering
                3, // leaving
                // dictionary after row operations.
                array(-1, 0),
                array(
                    array(-1, -1),
                    array(1, 0),
                ),
                array(5, 5),
                5, // c_0
            ),
        );
    }
    
    /**
     * Tests IsUnbounded to throw exception.
     * 
     * @test
     * @dataProvider providerPerformRowOperations
     * @param   array cData
     * @param   array AData
     * @param   array bData
     * @param   int   entering
     * @param   int   leaving
     */
    public function testPerformRowOperations($cData, $AData, $bData, $c0, $entering, $leaving, $resultcData, $resultAData, $resultbData, $resultc0) {
        $linearProgram = $this->linearProgramFromArrays($cData, $AData, $bData);
        $dictionary = new Dictionary($linearProgram);
        
        $this->assertEquals($c0, $dictionary->getc0());
        
        $dictionary->performRowOperations($entering, $leaving);
        
        $this->assertEquals($resultcData, $dictionary->getc()->asArray());
        $this->assertEquals($resultc0, $dictionary->getc0());
        $this->assertEquals($resultcData, $dictionary->getc()->asArray());
        $this->assertEquals($resultAData, $dictionary->getA()->asArray());
        $this->assertEquals($resultbData, $dictionary->getb()->asArray());
    }
}