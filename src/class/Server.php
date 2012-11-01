<?php
require_once 'Zend/Cache.php';
require_once 'Zend/Debug.php';
require_once 'Zend/Json.php';
require_once 'Go.php';
require_once 'Go/Pawn.php';
require_once 'Point.php';


/**
 * Go Server
 * Manage chess data
 */
class Server
{
    /**
     * Here is game data 
     *
     * @var Go
     */
    private $_go;
    /**
     * 游戏步骤
     *
     * @var array
     */
    private $_step = array();
    /**
     * 数据存储
     *
     * @var Zend_Cache
     */
    private $_cache;
    
    private $_player = Go_Pawn::BLACK;
    
    private $_finish = false;
    
    private $_pass = null;

    /**
     * 构造器，初始化数据（从缓存读取）
     *
     * @return void
     */
    public function __construct()
    {
        //Load Go data from file
        $frontendOptions = array(
            //'cache_id_prefix' => '',
            'lifetime' => 7200, // cache lifetime
            'automatic_serialization' => true,
            'write_control' => true,
            'automatic_cleaning_factor' => 1000,
            'ignore_user_abort' => true,
        );
        
        $backendOptions = array(
            'file_name_prefix' => 'go',
            'cache_dir' => './tmp/', // Directory where to put the cache files
            'file_locking' => true,
            'read_control' => true,
            //'read_control_type' => 'md5',
        );

        // getting a Zend_Cache_Core object
        $this->_cache = Zend_Cache::factory('Core', 'File', $frontendOptions, $backendOptions);
        //load chess data
        $go = $this->_cache->load('go');
        if (!$go instanceof Go) {
            $go = new Go();
        } 
        $this->_go = $go;
        
        $status = $this->_cache->load('status');
        if (empty($status)) {
            $player = Go_Pawn::BLACK;
            $finish = false;
            $pass = null;
        } else {
            $player = $status['player'];
            $finish = $status['finish'];
            $pass = $status['pass'];
        }
        $this->_player = $player;
        $this->_finish = $finish;
        $this->_pass = $pass;
    }

    /**
     * Hello world :)
     *
     * @return string
     */
    public function sayHello()
    {
        $username = isset($_SESSION['user_name']) ? $_SESSION['user_name'] : NULL;
        return "Hello $username!";
    }

    /**
     * login, save username and color in session
     *
     * @param $username
     * @param string $color
     * @return mixed
     */
    public function login($username, $color)
    {
        $_SESSION['user_name'] = $username;
        $_SESSION['color'] = $color;
        
        return array('username' => $username, 'color' => $color);
    }
    
    /**
     * get user info from session
     *
     * @return mixed
     */
    public function getUserInfo()
    {
        $username = empty($_SESSION['user_name']) ? NULL : $_SESSION['user_name'];
        $color = empty($_SESSION['color']) ? NULL : $_SESSION['color'];
        
        return array('username' => $username, 'color' => $color);
    }
    
    /**
     * new game
     *
     * @return unknown
     */
    public function newGame()
    {
        $this->_cache->clean(Zend_Cache::CLEANING_MODE_ALL);
        return __METHOD__;
    }
    
    /**
     * skip your turn
     *
     * @return unknown
     */
    public function pass()
    {
        $curPlayer = ($_SESSION['color'] == 'black' || $_SESSION['color'] == Go_Pawn::BLACK) ? Go_Pawn::BLACK : Go_Pawn::WHITE;
        
        if (empty($this->_pass)) {
            $pass = $curPlayer;
        }else {
            $pass = ($this->_pass == 'black' || $this->_pass == Go_Pawn::BLACK) ? Go_Pawn::BLACK : Go_Pawn::WHITE;;
        }
        
        if ($this->_player != $curPlayer) {
            $color = ($this->_player == Go_Pawn::WHITE) ? 'White' : 'Black';
            throw new Exception('Please wait Player ' . $color);
        }
        
        $this->_player = ($this->_player == Go_Pawn::BLACK) ? Go_Pawn::WHITE : Go_Pawn::BLACK;
        
        if (!empty($pass) && $pass != $curPlayer) {
            $this->_finish = 1;
        }
        
        $this->_pass = $pass;
        
        $status = array(
            'player' => $this->_player,
            'finish' => $this->_finish,
            'pass' => $this->_pass,
        );
        $this->_cache->save($status, 'status');
        return __METHOD__ . "($curPlayer) = " . Zend_Debug::dump($status, '', false);
    }

    /**
     * surrender
     *
     * @return unknown
     */
    public function surrender()
    {
        $curPlayer = $_SESSION['color'];
        $this->_finish = 2;
        $this->_pass = $curPlayer;
        $status = array(
            'player' => $this->_player,
            'finish' => $this->_finish,
            'pass' => $this->_pass,
        );
        $this->_cache->save($status, 'status');
        
        return __METHOD__ . "({$curPlayer})";
    }

    public function doStep($index, $color)
    {
        $color = ($color == 'white' || $color == Go_Pawn::WHITE) ? Go_Pawn::WHITE : Go_Pawn::BLACK;
        if ($this->_player != $color) {
            $color = ($this->_player == Go_Pawn::WHITE) ? 'White' : 'Black';
            throw new Exception('Please wait Player ' . $color);
        }
        
        $go = $this->_go;
        $go->doStep($index, $color);
        $this->_cache->save($go, 'go');
        
        $this->_player = ($color == Go_Pawn::BLACK) ? Go_Pawn::WHITE : Go_Pawn::BLACK;
        
        $status = array(
            'player' => $this->_player,
            'finish' => $this->_finish,
            'pass' => $this->_pass,
        );
        
        $this->_cache->save($status, 'status');

        $x = $go->getX($index);
        $y = $go->getY($index);
        return __METHOD__ . "($index($x, $y), $color)";
    }
    
    public function getLastStep()
    {
        $go = $this->_go;
        $point = $go->getLastStep();
        if (NULL != $point) {
            return $go->getIndex($point->x, $point->y);
        } else {
            return -1;
        }
    }
    
    public function getStepNumber()
    {
        return $this->_go->getStep();
    }
    
    public function getStatus()
    {
        $s = array(
            0 => 'playing',
            1 => 'gameover',
            2 => 'surrender',
        );
        $status = $s[$this->_finish];
        return array(
            'status' => $status,
            'pass' => $this->_pass, 
            'stepNumber' => $this->getStepNumber()
        );
    }
    
    public function getScore()
    {
        $go = $this->_go;
        return $go->getScore();
    }
    
    public function checkStep($num)
    {
        if (count($this->_step) != $num) {
            return true;
        } else {
            return false;
        }
    }
    
    /**
     * get game data
     *
     * @return unknown
     */
    public function getData()
    {
       $go = $this->_go;
       $data = $go->getArray();
       $result = array();
       foreach ($data as $x => $row) {
           foreach ($row as $y => $pawn) {
               $result[$x][$y] = (string)$pawn;
           }
       }
       return $result;
    }
    
    /**
     * get game data by index
     *
     * @return unknown
     */
    public function getCollection()
    {
        $go = $this->_go;
        $data = $go->getCollection();
        $result = array();
        foreach ($data as $index => $pawn) {
            $result[$index] = (string)$pawn;    //call $pawn->__toString()
        }
        return $result;
    }
}
