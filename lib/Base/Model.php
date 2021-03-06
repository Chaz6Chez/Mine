<?php
# -------------------------- #
#  Name: chaz6chez           #
#  Email: admin@chaz6chez.cn #
#  Date: 2018/10/17          #
# -------------------------- #
namespace Mine\Model;


use Mine\Db\Connection;
use Mine\Db\Db;

/**
 * 模型类是一个享元模式的单例容器
 * 与 Instance类相同
 *
 * Class Model
 * @package core\base
 */
class Model {

    protected $_table;
    protected $_dbName;
    protected $_dbMaster = [];
    protected $_dbSlave = [];
    private static $_instances = [];

    /**
     * Model constructor.
     */
    final private function __construct() {
        $this->_init();
    }

    protected function _init() {}

    /**
     * 查看已实例的类
     * @param string $className
     * @return array|mixed|null
     */
    final public function getInstances($className = ''){
        if($className){
            return isset(self::$_instances[$className]) ? self::$_instances[$className] : null;
        }
        return self::$_instances;
    }

    /**
     * 容器 GC
     * @param int $limit
     */
    final private static function GC($limit = 10){
        # 判断容器容量
        if(!$limit){
            return;
        }
        $count = count(self::$_instances);
        if($count > 0){
            if(($redundant = $count - (int)$limit) > 0){
                # 溢出的对象出队 等待PHP GC
                do{
                    array_shift(self::$_instances);
                    $redundant --;
                }while($redundant > 0);
            }
        }
    }

    /**
     * 单例模式
     *
     *  对象会存入单例容器，随着进程而保持，不会被PHP GC主动回收
     *
     * @return static
     */
    final public static function instance() {
        $class = get_called_class();
        # 容器中不存在
        if (!isset(self::$_instances[$class]) or !self::$_instances[$class] instanceof Model) {
            self::GC();
            return self::$_instances[$class] = new $class();
        }
        return self::$_instances[$class];
    }

    /**
     * 工厂模式
     *
     *  对象不会存入单例容器，随着方法体执行完毕而被PHP GC主动回收
     *
     * @return static
     */
    final public static function factory() {
        $class = get_called_class();
        return new $class();
    }

    /**
     * 单例容器全清
     *
     *  清除后交给PHP GC进行回收
     *
     */
    final public function instanceClean(){
        self::$_instances = [];
    }

    /**
     * 单例容器清除
     *
     *  清除后交给PHP GC进行回收
     *
     */
    final public function instanceRemove(){
        $class = get_called_class();
        unset(self::$_instances[$class]);
    }

    /**
     * 获得数据库组件
     * @return Db
     */
    public function db() {
        return Db::instance();
    }

    /**
     * 获得主数据库连接
     * @param string $name
     * @return Connection|bool
     */
    public function dbName($name = 'default') {
        if ($name == 'default') {
            $dbName = !$this->_dbName ? $name : $this->_dbName;
        } else {
            $dbName = $name;
        }
        if (!isset($this->_dbMaster[$dbName]) or !$this->_dbMaster[$dbName] instanceof Connection) {
            $this->_dbMaster[$dbName] = $this->db()->dbName($dbName);
        }

        return $this->_dbMaster[$dbName];
    }

    /**
     * 获取表名
     * @param string $name
     * @return string
     */
    public function tb($name = '') {
        if ($name === '') {
            return $this->_table;
        }
        $v = "_table_{$name}";
        return $this->$v;
    }

    /**
     * 应用额外选项,其实就是通过数组的方式调用方法
     * @param $db
     * @param $options
     */
    protected function _applyOptions($db, $options) {
        foreach ($options as $m => $opt) {
            if (method_exists($db, $m)) {
                call_user_func_array([$db, $m], $opt);
            }
        }
    }

}
