<?php
namespace Shirou\Behavior\Model;

use Phalcon\{
    Mvc\Model\Behavior,
    Mvc\Model\BehaviorInterface,
    Mvc\ModelInterface
};

class Timestampable extends Behavior implements BehaviorInterface
{
    public function __construct($options = null)
    {
    }

    public function notify($eventType, ModelInterface $model)
    {
        switch ($eventType) {
            case 'beforeCreate':
                $model->datecreated = time();
                break;
            case 'beforeUpdate':
                $model->datemodified = time();
                break;
        }
    }

    public function missingMethod(ModelInterface $model, $method, $arguments = null)
    {
        if (!method_exists($this, $method)) {
            return null;
        }

        if (!$this->db) {
            if ($model->getDi()->has('db')) {
                $this->db = $model->getDi()->get('db');
            } else {
                throw new \Exception('Undefined database handler.');
            }
        }

        $this->setOwner($model);
        $result = call_user_func_array(array($this, $method), $arguments);
        if ($result === null) {
            return '';
        }

        return $result;
    }
}
