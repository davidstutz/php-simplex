<?php

/**
 * Test cases for the LinearProgram class.
 * 
 * @author  David Stutz
 * @license http://www.gnu.org/licenses/gpl-3.0
 */
class LinearProgramTest extends \PHPUnit_Framework_TestCase {
    
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
     * @param   int rows
     * @param   int columns
     */
    public function testConstruct($cData, $AData, $bData) {
        $c = new Vector(sizeof($cData));
        $c->fromArray($cData);
        
        $A = new Matrix(sizeof($AData), sizeof($AData[0]));
        $A->fromArray($AData);
        
        $b = new Vector(sizeof($bData));
        $b->fromArray($bData);
        
        $linearProgram = new LinearProgram($c, $A, $b);
        
        $this->assertSame($c->size(), $linearProgram->getc()->size());
        $this->assertSame($A->rows(), $linearProgram->getA()->rows());
        $this->assertSame($A->columns(), $linearProgram->getA()->columns());
        $this->assertSame($b->size(), $linearProgram->getb()->size());
    }
}