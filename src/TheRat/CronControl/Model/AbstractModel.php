<?php
namespace TheRat\CronControl\Model;

abstract class AbstractModel
{
    /**
     * @return string
     */
    abstract public function getHash();
}
