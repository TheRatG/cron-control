<?php
namespace TheRat\CronControl\Model;

class VariableModel extends AbstractModel
{
    const NAME_MAIL = 'MAILTO';

    const NAME_LOG_DIR = 'CRON_CONTROL_LOG_DIR';

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string
     */
    protected $value;

    /**
     * Parse a variable line
     *
     * @param string $varLine
     * @return self
     */
    public static function parse($varLine)
    {
        $parts = explode('=', $varLine, 2);
        if (!$parts || count($parts) !== 2) {
            throw new \InvalidArgumentException('Line does not appear to contain a variable');
        }

        $variable = new self();
        $variable->setName(trim(array_shift($parts)))
            ->setValue(trim(array_shift($parts)));

        return $variable;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        try {
            return $this->render();
        } catch (\Exception $exception) {
            return '# '.$exception;
        }
    }

    /**
     * @return string
     */
    public function render()
    {
        return $this->getName().'='.$this->getValue();
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return self
     * @throws \InvalidArgumentException if the variable name contain spaces or a $
     */
    public function setName($name)
    {
        if (strpos($name, ' ') !== false) {
            throw new \InvalidArgumentException('self names cannot contain spaces');
        } else {
            if (strpos($name, '$') !== false) {
                throw new \InvalidArgumentException('self names cannot contain a \'$\' character');
            }
        }
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @param string $value
     * @return self
     */
    public function setValue($value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return string
     */
    public function getHash()
    {
        return hash(
            'md5',
            serialize(
                [
                    strval($this->getName()),
                    strval($this->getValue()),
                ]
            )
        );
    }
}
