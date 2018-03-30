<?php
namespace Shirou\Db\Model\Annotations;

use Phalcon\{
    Events\Event,
    Mvc\Model\Manager as ModelsManager,
    Mvc\User\Plugin as PhPlugin
};

class Initializer extends PhPlugin
{
    public function afterInitialize(Event $event, ModelsManager $manager, $model)
    {
        //Reflector
        $reflector = $this->annotations->get($model);

        /**
         * Read the annotations in the class' docblock
         */
        $annotations = $reflector->getClassAnnotations();
        if ($annotations) {
            /**
             * Traverse the annotations
             */
            foreach ($annotations as $annotation) {
                switch ($annotation->getName()) {
                    /**
                     * Initializes the model's source
                     */
                    case 'Source':
                        $arguments = $annotation->getArguments();
                        $manager->setModelSource($model, $arguments[0]);
                        break;

                    /**
                     * Initializes Has-One relations
                     */
                    case 'HasOne':
                        $arguments = $annotation->getArguments();
                        if (isset($arguments[3])) {
                            $manager->addHasOne($model, $arguments[0], $arguments[1], $arguments[2], $arguments[3]);
                        } else {
                            $manager->addHasOne($model, $arguments[0], $arguments[1], $arguments[2]);
                        }
                        break;

                    /**
                     * Initializes Has-Many relations
                     */
                    case 'HasMany':
                        $arguments = $annotation->getArguments();
                        if (isset($arguments[3])) {
                            $manager->addHasMany($model, $arguments[0], $arguments[1], $arguments[2], $arguments[3]);
                        } else {
                            $manager->addHasMany($model, $arguments[0], $arguments[1], $arguments[2]);
                        }
                        break;

                    /**
                     * Initializes BelongsTo relations
                     */
                    case 'BelongsTo':
                        $arguments = $annotation->getArguments();
                        if (isset($arguments[3])) {
                            $manager->addBelongsTo($model, $arguments[0], $arguments[1], $arguments[2], $arguments[3]);
                        } else {
                            $manager->addBelongsTo($model, $arguments[0], $arguments[1], $arguments[2]);
                        }
                        break;

                    /**
                     * Initializes the model's Behavior
                     */
                     case 'Behavior':
                        $arguments = $annotation->getArguments();
                        $behaviorName = $arguments[0];
                        if (isset($arguments[1])) {
                            $manager->addBehavior($model, new $behaviorName($arguments[1]));
                        } else {
                            $manager->addBehavior($model, new $behaviorName);
                        }
                        break;

                    /**
             		 * Initializes the model's source connection
                     */
                    case 'setConnectionService':
                        $arguments = $annotation->getArguments();
                        $manager->setConnectionService($model, $arguments[0]);
                        break;
                }
            }
        }

        return $event->getType();
    }
}
