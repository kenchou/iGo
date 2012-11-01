<?php
require_once 'Point.php';

class Go_Pawn
{
    const NONE  = null;
    const WHITE = 'O';
    const BLACK = 'X';

    public $color;
    public $x;
    public $y;
    private $_control;
    public $pointAround = array();
    
    public function __construct($color = self::NONE, $x = null, $y = null)
    {
        $this->color = $color;
        $this->x = $x;
        $this->y = $y;
    }

    public function isBlack()
    {
        return (self::BLACK == $this->color);
    }
    
    public function isWhite()
    {
        return (self::WHITE == $this->color);
    }
    
    public function setColor($color)
    {
        $this->color = $color;
        return $this;
    }
    
    public function getColor()
    {
        return $this->color;
    }
    
    public function setCoordinate($x, $y)
    {
        $this->setX($x)->setY($y);
    }
    
    public function setX($x)
    {
        $this->x = $x;
        return $this;
    }
    
    public function setY($y)
    {
        $this->y = $y;
        return $this;
    }

    public function getX()
    {
        return $this->x;
    }
    
    public function getY()
    {
        return $this->y;
    }
    public function getControl()
    {
        return $this->_control;
    }
    
    
    
    public function isLive()
    {
        return (boolean)$this->_control;
    }
    
    public function __get($name)
    {
        return $this->$name;
    }
    
    public function __set($name, $value)
    {
        $this->$name = $value;
    }
    
    public function __toString() {
        return (string)$this->color;
    }
}