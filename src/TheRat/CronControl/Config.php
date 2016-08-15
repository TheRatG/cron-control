<?php
namespace TheRat\CronControl;

/**
 * Class Config
 * @package TheRat\CronControl
 */
class Config
{
    /**
     * @var string
     */
    protected $disablePostfix;

    /**
     * @var string[]
     */
    protected $globPatterns;

    /**
     * Config constructor.
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->disablePostfix = $config['disable_postfix'];
        $this->globPatterns = $config['glob_patterns'];
    }

    /**
     * @return mixed
     */
    public function getGlobPatterns()
    {
        return $this->globPatterns;
    }

    /**
     * @return array
     */
    public function getDisabledGlobPatterns()
    {
        $patterns = $this->getGlobPatterns();
        $postfix = $this->getDisablePostfix();
        $result = array_map(
            function ($item) use ($postfix) {
                return $item.$postfix;
            },
            $patterns
        );

        return $result;
    }

    /**
     * @return mixed
     */
    public function getDisablePostfix()
    {
        return $this->disablePostfix;
    }

    /**
     * @return array
     */
    public function getEnabledCrontabFiles()
    {
        return $this->getCrontabFiles($this->getGlobPatterns());
    }

    /**
     * @return array
     */
    public function getDisabledCrontabFiles()
    {
        return $this->getCrontabFiles($this->getDisabledGlobPatterns());
    }

    /**
     * @param array $patterns
     * @return array
     */
    public function getCrontabFiles(array $patterns)
    {
        $result = [];
        foreach ($patterns as $pattern) {
            $files = glob($pattern);
            foreach ($files as $filename) {
                if (is_file($filename) && is_readable($filename)) {
                    $result[] = $filename;
                }
            }
        }

        return $result;
    }
}
