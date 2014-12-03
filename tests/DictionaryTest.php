<?php

/**
 * Test cases for the Dictionary class.
 * 
 * @author  David Stutz
 * @license http://www.gnu.org/licenses/gpl-3.0
 */
class DictionaryTest extends \PHPUnit_Framework_TestCase {
    
    public function dictionaryFromData($cData, $AData, $bData) {
        $c = new Vector(sizeof($cData));
        $c->fromArray($cData);
        
        $A = new Matrix(sizeof($AData), sizeof($AData[0]));
        $A->fromArray($AData);
        
        $b = new Vector(sizeof($bData));
        $b->fromArray($bData);
        
        $nonBasic = new Vector($c->size());
        for ($i = 0; $i < $nonBasic->size(); $i++) {
            $nonBasic->set($i, $i + 1);
        }
        
        $basic = new Vector($A->rows());
        for ($i = 0; $i < $basic->size(); $i++) {
            $basic->set($i, $nonBasic->size() + $i + 1);
        }
        
        return new Dictionary(0, $c, $A, $b, $nonBasic, $basic);
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
        $dictionary = $this->dictionaryFromData($cData, $AData, $bData);
        
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
        $dictionary = $this->dictionaryFromData($cData, $AData, $bData);
        
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
        $dictionary = $this->dictionaryFromData($cData, $AData, $bData);
        
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
        $dictionary = $this->dictionaryFromData($cData, $AData, $bData);
        
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
        $dictionary = $this->dictionaryFromData($cData, $AData, $bData);
        
        $this->assertSame($unbounded, $dictionary->isUnbounded());
    }
    
    /**
     * Provides data for testing identifyLeavingVariableBland.
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
        $dictionary = $this->dictionaryFromData($cData, $AData, $bData);
        
        $this->assertSame($leaving, $dictionary->identifyLeavingVariable($entering));
    }
    
    /**
     * Provides data for testing performRowOperations
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
        $dictionary = $this->dictionaryFromData($cData, $AData, $bData);
        
        $this->assertEquals($c0, $dictionary->getc0());
        
        $dictionary->performRowOperations($entering, $leaving);
        
        $this->assertEquals($resultcData, $dictionary->getc()->asArray());
        $this->assertEquals($resultc0, $dictionary->getc0());
        $this->assertEquals($resultcData, $dictionary->getc()->asArray());
        $this->assertEquals($resultAData, $dictionary->getA()->asArray());
        $this->assertEquals($resultbData, $dictionary->getb()->asArray());
    }
    
    /**
     * Tests entering and leaving variable based on provided data.
     * 
     * @test
     */
    public function testDataEnteringLeaving() {
        for ($i = 1; $i <= 15; $i++) {
            $dictionary = DictionaryIO::read('../data/dict' . $i);
            $content = file_get_contents('../data/dict' . $i . '.output');
            
            $lines = explode("\n", $content);
            
            $lines = array_map(function($line) {
                return trim($line);
            }, $lines);
            
            $lines = array_filter($lines, function($line) {
                return !empty($line);
            });
            
            if ($dictionary->isUnbounded()) {
                $this->assertEquals(sizeof($lines), 1);
                $this->assertSame($lines[0], 'UNBOUNDED');
            }
            else {
                $entering = $dictionary->identifyEnteringVariable();
                $leaving = $dictionary->identifyLeavingVariable($entering);
                $dictionary->performRowOperations($entering, $leaving);
                $c0 = $dictionary->getc0();
                
                $c0 = round($c0, 1);
                $testC0 = round($lines[2], 1);
                
                $this->assertEquals($entering, $lines[0]);
                $this->assertEquals($leaving, $lines[1]);
                $this->assertEquals($c0, $testC0);
            }
        }
    }
    
    /**
     * Tests complete simplex.
     * 
     * @test
     */
    public function testDataSimplex() {
        for ($i = 1; $i <= 10; $i++) {
            $dictionary = DictionaryIO::read('../data/dict' . $i);
            $content = file_get_contents('../data/dict' . $i . '.solution');
            
            $lines = explode("\n", $content);
            
            $lines = array_map(function($line) {
                return trim($line);
            }, $lines);
            
            $lines = array_filter($lines, function($line) {
                return !empty($line);
            });
            
            $dictionary->optimize();
            
            if ($dictionary->isUnbounded()) {
                $this->assertEquals(sizeof($lines), 1);
                $this->assertSame($lines[0], 'UNBOUNDED');
            }
            else {
                $c0 = $dictionary->getc0();
                
                $c0 = round($c0, 1);
                $testC0 = round($lines[0], 1);
                
                $this->assertEquals(sizeof($lines), 2);
                $this->assertEquals($c0, $testC0);
            }
        }
    }
    
    /**
     * Test dataprovider for testing getAuxiliaryProblem.
     * 
     * @return array
     */
    public function providerGetAuxiliaryProblem() {
        return array(
            array(
                array(1), //c
                array( // A
                    array(-1),
                    array(1),
                ),
                array(5, -1), // b
                array(-1, 0), // aux c
                array( // aux A
                    array(1, -1),
                    array(1, 1),
                ),
                array(5, -1), // aux b
            ),
        );
    }
    
    /**
     * Tests getAuxiliaryProblem.
     * 
     * @test
     * @dataProvider providerGetAuxiliaryProblem
     * @param   array cData
     * @param   array AData
     * @param   array bData
     * @param   array auxcData
     * @param   array auxAData
     * @param   array auxbData
     * @param
     */
    public function testGetAuxiliaryProblem($cData, $AData, $bData, $auxcData, $auxAData, $auxbData) {
        $dictionary = $this->dictionaryFromData($cData, $AData, $bData);
        $this->assertSame($dictionary->isFeasible(), FALSE);
        
        $auxDictionary = $this->dictionaryFromData($auxcData, $auxAData, $auxbData);
        $this->assertSame($auxDictionary->isFeasible(), FALSE);
        
        $genAuxDictionary = $dictionary->getAuxiliaryDictionary();
        $this->assertSame($genAuxDictionary->getc()->asArray(), $auxDictionary->getc()->asArray());
        $this->assertSame($genAuxDictionary->getA()->asArray(), $auxDictionary->getA()->asArray());
        $this->assertSame($genAuxDictionary->getb()->asArray(), $auxDictionary->getb()->asArray());
    }
    
    /**
     * Data for testing ProviderIdentifyMostInfeasibleVariable.
     * 
     * @return array
     */
    public function providerIdentifyMostInfeasibleVariable() {
        return array(
            array(
                array(1), // c
                array( // A
                    array(-1),
                    array(1),
                ),
                array(5, -1), // b
                3, // Most infeasible variable.
            ),
        );
    }
    
    /**
     * Tests identifyMostInfeasibleVariable.
     * 
     * @test
     * @dataProvider providerIdentifyMostInfeasibleVariable
     * @param   array cData
     * @param   array AData
     * @param   array bData
     * @param
     */
    public function testIdentifyMostInfeasibleVariable($cData, $AData, $bData, $mostInfeasibleVariable) {
        $dictionary = $this->dictionaryFromData($cData, $AData, $bData);
        
        $this->assertSame($dictionary->isFeasible(), FALSE);
        $this->assertSame($dictionary->identifyMostInfeasibleVariable(), $mostInfeasibleVariable);
    }
    
    /**
     * Data for testing initialization.
     * 
     * @return array
     */
    public function providerInitialization() {
        return array(
            array(
                array(1), // c
                array( // A
                    array(-1),
                    array(1),
                ),
                array(5, -1), // b
                array(1),
                array(
                    array(-1),
                    array(1),
                ),
                array(4, 1),
                array(2, 1), // basic
                array(3), // non-basic
                5,
            ),
            array(
                array(1, 1), // c
                array( // A
                    array(1, 0),
                    array(-1, 0),
                    array(0, 1),
                    array(0, -1),
                    array(-1, -1),
                ),
                array(-1, 10, -2, 12 ,16), // b
                array(1, 1),
                array(
                    array(1, 0),
                    array(0, -1),
                    array(0, 1),
                    array(-1 ,0),
                    array(-1, -1),
                ),
                array(2, 9, 1, 10, 13),
                array(2, 4, 1, 6, 7), // basic
                array(5, 3), // non-basic
                16,
            )
        );
    }
    
    /**
     * Tests initialization.
     * 
     * @test
     * @dataProvider providerInitialization
     * @param   array cData
     * @param   array AData
     * @param   array bData
     * @param   array feasiblecData
     * @param   array feasibleAData
     * @param   array feasiblebData
     * @param
     */
    public function testInitialization($cData, $AData, $bData, $auxcData, $auxAData, $auxbData, $basicVariableData, $nonBasicVariableData, $optValue) {
        $dictionary = $this->dictionaryFromData($cData, $AData, $bData);
        $initDictionary = $this->dictionaryFromData($auxcData, $auxAData, $auxbData);
        
        $this->assertSame($dictionary->initialize(), TRUE);
        $this->assertEquals($dictionary->getc()->asArray(), $initDictionary->getc()->asArray());
        $this->assertEquals($dictionary->getA()->asArray(), $initDictionary->getA()->asArray());
        $this->assertEquals($dictionary->getb()->asArray(), $initDictionary->getb()->asArray());
        
        $this->assertEquals($dictionary->getBasicVariables()->asArray(), $basicVariableData);
        $this->assertEquals($dictionary->getNonBasicVariables()->asArray(), $nonBasicVariableData);
        
        $dictionary->optimize();
        $this->assertEquals($dictionary->getc0(), $optValue);
    }
    
    /**
     * Tests initialization on data.
     * 
     * @test
     */
    public function testDataInitialization() {
        for ($i = 16; $i <= 30; $i++) {
            $dictionary = DictionaryIO::read('../data/dict' . $i);
            $content = file_get_contents('../data/dict' . $i . '.solution');
            
            $lines = explode("\n", $content);
            
            $lines = array_map(function($line) {
                return trim($line);
            }, $lines);
            
            $lines = array_filter($lines, function($line) {
                return !empty($line);
            });
            
            $this->assertSame($dictionary->isFeasible(), FALSE);
            if ($dictionary->initialize() === FALSE) {
                $this->assertEquals(sizeof($lines), 1);
                    $this->assertSame($lines[0], 'INFEASIBLE');
            }
            else {
                $this->assertSame($dictionary->isFeasible(), TRUE);
                $dictionary->optimize();
                
                if ($dictionary->isUnbounded()) {
                    $this->assertEquals(sizeof($lines), 1);
                    $this->assertSame($lines[0], 'UNBOUNDED');
                }
                else {
                    $c0 = $dictionary->getc0();

                    $c0 = round($c0, 1);
                    $testC0 = round($lines[0], 1);

                    $this->assertEquals(sizeof($lines), 1);
                    $this->assertEquals($c0, $testC0);
                }
            }
        }
    }
}