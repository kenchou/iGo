<?php
require_once 'Go/Pawn.php';

/**
 * I-go Core
 */
class Go
{
    private $_maxCols = 19;
    private $_maxRows = 19;

    /**
     * Go pawn collection hash table
     *
     * @var array()
     */
    private $_pawns = array();
    
    //status
    private $_pointNow = null;
    private $robPoint = null;
    private $vec = array();
    private $_step = 0;
    private $_stone = array();

    /**
     * Constructor
     *
     * @param int $maxCols
     * @param int $maxRows
     * @return void
     */
    public function __construct($maxCols = 19, $maxRows = 19)
    {
        $this->setMaxCols($maxCols)
             ->setMaxRows($maxRows);
        
        $this->_stone = array(
            'black' => 0,
            'white' => 0,
            'gray' => 0,
        );
        //init chessboard
        //$row = array_fill(0, $this->_maxCols, Go_Pawn::NONE);
        //$this->_data = array_fill(0, $this->_maxRows, $row);
        
        $this->_initHashTable();
        //Zend_Debug::dump($this, '$this');
    }
    
    private function _initHashTable()
    {
        // y
        for ($j = 0; $j < $this->_maxRows; $j++) {
            // x
            for ($i = 0; $i < $this->_maxCols; $i++) {
                //$point = new Point($i, $j);
                $index = $this->getIndex($i, $j);
                $pawn = new Go_Pawn();
                $pawn->pointAround[0] = ($j == 0) ? NULL: new point($i, $j - 1); //up
                $pawn->pointAround[1] = ($j == $this->_maxCols - 1) ? NULL : new Point($i, $j + 1); //down
                $pawn->pointAround[2] = ($i == 0) ? NULL: new Point($i - 1, $j); //left
                $pawn->pointAround[3] = ($i == $this->_maxRows - 1) ? NULL : new Point($i + 1, $j); //roght
                
                $this->_pawns[$index] = $pawn;
            }
        }
    }
    
    /**
     * do step
     *
     * @param int $index
     * @param string $color
     * @return Go
     */
    public function doStep($index, $color)
    {
        $x = $this->getX($index);
        $y = $this->getY($index);
        
        $point = new Point($x, $y);
        
        //error_log("($x, $y)");
        //var_dump($x,$y,$point);
        
        if ($x < 0 || $x >= $this->_maxRows || $y < 0 || $y >= $this->_maxCols) {
            throw new Exception('Out of board.');
        }
        
        if (Go_Pawn::NONE != $this->getPawn($point)->getColor()) {
            throw new Exception('Has a pawn here.');
        }
        
        //set point
        $this->updateHash($point, $color);
        $this->getRival($point, $color);
        
        //check around
        if((!$this->isLink($point, $color) && !$this->isLink($point, Go_Pawn::NONE)) || !$this->isLive($point)) {
            $this->singleRemove($point);
            throw new Exception('Not allow here.');
        }
        
        $this->_pointNow->x = $point->x;
        $this->_pointNow->y = $point->y;
        
        return $this;
    }
    
    /**
     * remove rival
     * FIXME: remove problem
     *
     * @param Point $point
     * @param string $color
     */
    public function getRival(Point $point, $color)
    {
        $removeFlag = false;
        
        $pawn = $this->getPawn($point);
        $otherPoint = $pawn->pointAround;
        for ($i = 0; $i < 4; $i++) {
            $p = $otherPoint[$i];
            //Zend_Debug::dump($p, '$p');
            if (NULL != $p) {
                $otherPawn = $this->getPawn($p);
                //Zend_Debug::dump($otherPawn, '$otherPawn');
                if ($otherPawn->color != Go_Pawn::NONE && $otherPawn->color != $color) {
                    if ($this->isLive($p)) {
                        //echo "isLive";
                        $this->vec = array();
                    } else {
                        //echo "xxx";
                        $this->makeRobber($p);
                        $this->doRemove();
                        $this->vec = array();
                        $removeFlag = true;
                    }
                }
            }
        }
        if (!$removeFlag) {
            $this->robPoint = null;
        }
    }
    
    public function isRob(Point $p)
    {
        if ($this->robPoint == null) {
            return false;
        }
        if ($this->robPoint->x == $p->x && $this->robPoint->y == $p->y) {
            return true;
        }
        return false;
    }
    
    /**
     * single
     *
     * @param Point $point
     */
    public function makeRobber(Point $point)
    {
        if (count($this->vec) == 1) {
            $this->robPoint = $point;
        } else {
            $this->robPoint = null;
        }
    }
    
    /**
     * is any pawn link to empty?
     *
     * @param Point $point
     * @return bool
     */
    public function isLive(Point $point)
    {
        $index = $this->getIndex($point->x, $point->y);
        
        if (isset($this->vec[$index])) { //has been checked
            return false;
        }
        if ($this->isLink($point, Go_Pawn::NONE)) { //is live
            return true;
        }
        //save to temp var
        $this->vec[$index] = $point;
        
        $pawn = $this->getPawn($point);
        $otherPoint = $pawn->pointAround;
        for ($i = 0; $i < 4; $i++) {
            $p = $otherPoint[$i];
            if (NULL != $p) {
                $otherPawn = $this->getPawn($p);
                if ($otherPawn->color == $pawn->color) {
                    if ($this->isLive($p)) {
                        return true;
                    }
                }
            }
        }
        return false;
    }
    
    /**
     * remove all saved point
     *
     */
    public function doRemove()
    {
        foreach ($this->vec as $point) {
            $this->singleRemove($point);
        }
    }
    
    /**
     * remove a point
     *
     * @param Point $point
     */
    public function singleRemove(Point $point)
    {
        //$index = $this->getIndex($point->x, $point->y);
        $pawn = $this->getPawn($point);
        $pawn->isThere = false;
        $pawn->color = Go_Pawn::NONE;
        //echo __METHOD__ . "({$point->x}, {$point->y})\n";
        //unset($this->_pawns[$index]);
    }
    
    /**
     * check Adjacent points
     *
     * @param Point $point
     * @param string $color
     */
    public function isLink(Point $point, $color)
    {
        $pawn = $this->getPawn($point);
        $otherPoint = $pawn->pointAround;
        
        //Zend_Debug::dump($pawn, '$pawn');
        //Zend_Debug::dump($otherPoint, 'other point');
        for ($i = 0; $i < 4; $i++) {
            $p = $otherPoint[$i];
            if (NULL != $p) {
                $otherPawn = $this->getPawn($otherPoint[$i]);
                if ($otherPawn->color == $color) {
                    return true;
                }
            }
        }
        return false;
    }
    
    /**
     * set a pawn at point
     *
     * @param Point $point
     * @param string $color
     * @return Go
     */
    public function updateHash(Point $point, $color)
    {
        $pawn = $this->getPawn($point);
        $pawn->isThere = true;
        $pawn->color = $color;
        $this->_step = $this->_step + 1;
        $pawn->whichStep = $this->_step;
        
        return $this;
    }
    
    /**
     * get Pawn at point
     *
     * @param Point $point
     * @return Go_Pawn
     */
    public function getPawn(Point $point)
    {
        $index = $this->getIndex($point->x, $point->y);
        return $this->_pawns[$index];
    }
    
    /**
     * @return int
     */
    public function getMaxCols()
    {
        return $this->_maxCols;
    }

    /**
     * @return int
     */
    public function getMaxRows()
    {
        return $this->_maxRows;
    }

    /**
     * @param int $_maxCols
     * @return Go
     */
    public function setMaxCols($maxCols)
    {
        $this->_maxCols = $maxCols;
        return $this;
    }

    /**
     * @param int $_maxRows
     * @return Go
     */
    public function setMaxRows($maxRows)
    {
        $this->_maxRows = $maxRows;
        return $this;
    }
    
    public function getArray()
    {
        return $this->_data;
    }
    
    public function getCollection()
    {
        return $this->_pawns;
    }

    /**
     * set a pawn by Index
     *
     * @param int $index
     * @param mixed $pawn
     * @return Go
     */
    public function setIndex($index, $pawn)
    {
        $x = $this->getX($index);
        $y = $this->getY($index);
        $this->setPoint($x, $y, $pawn);
        return $this;
    }

    /**
     * set a pawn by Coordinate
     *
     * @param int $x
     * @param int $y
     * @param mixed $pawn
     * @return Go
     */
    public function setPoint($x, $y, $pawn)
    {
        if (Go_Pawn::NONE == (string)$this->_data[$x][$y]) {
            $pawn->setCoordinate($x, $y);
            $this->_data[$x][$y] = $pawn;
            //重新计算"气"
        } else {
            throw new Exception("The point ($x, $y) has already a pawn ({$this->_data[$x][$y]})");
        }
        return $this;
    }

    /**
     * Calculate score
     *
     * @return mixed
     */    
    public function getScore()
    {
        foreach ($this->_pawns as $pawn) {
            if ($pawn->isBlack()) {
                $this->_stone['black']++;
            } elseif ($pawn->isWhite()) {
                $this->_stone['white']++;
            } else { //empty
                $black = 0;
                $white = 0;
                
                $otherPoint = $pawn->pointAround;
                for ($i = 0; $i < 4; $i++) {
                    $p = $otherPoint[$i];
                    if (NULL != $p) {
                        $otherPawn = $this->getPawn($p);
                        if ($otherPawn->color == Go_Pawn::BLACK) {
                            ++$black;
                        } elseif ($otherPawn->color == Go_Pawn::WHITE) {
                            ++$white;
                        }
                    }
                }
                if ($black > 0 && $white > 0) {
                    $this->_stone['gray']++;
                } elseif ($black > 0) {
                    $this->_stone['black']++;
                } elseif ($white > 0) {
                    $this->_stone['white']++;
                }
            }
        }
        
        return ($this->_stone);
    }

    public function display()
    {
        //var_dump($this->_data);
        foreach ($this->_data as $row) {
            foreach ($row as $col) {
                echo $col;
            }
            echo "\n";
        }
    }
    
    /**
     * get Coord X 
     *
     * @param int $index
     * @return int
     */
    public function getX($index)
    {
        return $index % $this->_maxCols;
    }
    
    /**
     * get Coord Y
     *
     * @param int $index
     * @return int
     */
    public function getY($index)
    {
        return (int)floor($index / $this->_maxCols);
    }
    
    public function getIndex($x, $y)
    {
        return $x + $y * $this->_maxCols;
    }
    
    public function getStep()
    {
        return $this->_step;
    }
    
    public function getLastStep()
    {
        return $this->_pointNow;
    }
}

/*
//test case
$iGo = new Go();

for ($i = 0; $i < 20; $i++) {
    $iGo->setPoint(rand(0, 18), rand(0, 18), rand(0, 1) ? Go::WHITE : Go::BLACK);
}
$iGo->display();
*/