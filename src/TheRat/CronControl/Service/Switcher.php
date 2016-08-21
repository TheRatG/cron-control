<?php
namespace TheRat\CronControl\Service;

use TheRat\CronControl\Config;
use TheRat\LoggerTrait;

/**
 * Class Switcher
 * @package TheRat\CronControl\Service
 */
class Switcher
{
    use LoggerTrait;

    /**
     * @var Config
     */
    protected $config;

    /**
     * Switcher constructor.
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    /**
     * @param array $files
     * @param array $exceptions
     * @param       $dryRun
     * @return array
     */
    public function disableFiles(array $files, array $exceptions, $dryRun)
    {
        $files = $this->filterFiles($files, $exceptions);

        $result = [];
        foreach ($files as $filename) {
            $result[$filename] = $this->disable($filename, $dryRun);
        }

        return $result;
    }

    /**
     * @param array $files
     * @param array $exceptions
     * @param       $dryRun
     * @return array
     */
    public function enableFiles(array $files, array $exceptions, $dryRun)
    {
        $files = $this->filterFiles($files, $exceptions);

        $result = [];
        foreach ($files as $filename) {
            $result[$filename] = $this->enable($filename, $dryRun);
        }

        return $result;
    }

    /**
     * @param       $files
     * @param array $exceptions
     * @return array
     */
    public function filterFiles($files, array $exceptions)
    {
        $result = [];
        foreach ($files as $filename) {
            $exception = $this->isInExceptionList($filename, $exceptions);
            if ($exception) {
                $this->getLogger()->debug(
                    'Skipped file, because in exception list',
                    [
                        $filename,
                        $exception,
                    ]
                );
                continue;
            }

            $result[] = $filename;
        }

        return $result;
    }

    /**
     * @return Config
     */
    protected function getConfig()
    {
        return $this->config;
    }

    protected function isInExceptionList($filename, array $exceptions)
    {
        $result = false;
        if (!empty($exceptions)) {
            foreach ($exceptions as $exception) {
                if (preg_match('#'.preg_quote($exception, '#').'#', $filename)) {
                    $result = $exception;
                    break;
                }
            }
        }

        return $result;
    }

    protected function disable($filename, $dryRun)
    {
        $disabledFilename = null;
        if (!$this->isDisabledFilename($filename)) {
            $disabledFilename = $filename.$this->getConfig()->getDisablePostfix();
            $this->rename($filename, $disabledFilename, $dryRun);
        } else {
            $this->getLogger()->notice('File already disabled', ['src' => $filename]);
        }

        return $disabledFilename;
    }

    protected function enable($filename, $dryRun)
    {
        $enabledFilename = null;
        if ($this->isDisabledFilename($filename)) {
            $enabledFilename = substr(
                $filename,
                0,
                -1 * strlen($this->getConfig()->getDisablePostfix())
            );
            $this->rename($filename, $enabledFilename, $dryRun);
        } else {
            $this->getLogger()->notice('File already enabled', ['src' => $filename]);
        }

        return $enabledFilename;
    }

    protected function isDisabledFilename($filename)
    {
        $postfix = $this->getConfig()->getDisablePostfix();

        $result = false;
        if (substr($filename, -1 * strlen($postfix)) === $postfix) {
            $result = true;
        }

        return $result;
    }

    /**
     * @param $src
     * @param $dst
     * @param $dryRun
     */
    protected function rename($src, $dst, $dryRun)
    {
        $context = ['src' => $src, 'dst' => $dst];
        if (!$dryRun) {
            $timeSrc = 1;
            $timeDst = 0;
            if (is_file($dst)) {
                $timeSrc = filemtime($src);
                $timeDst = filemtime($dst);
            }

            if ($timeSrc > $timeDst) {
                if (!rename($src, $dst)) {
                    $this->getLogger()->error('Rename did not work', $context);
                } else {
                    $this->getLogger()->info('Rename success', $context);
                }
            } else {
                if (!unlink($src)) {
                    $this->getLogger()->error('Rename did not work', $context);
                } else {
                    $this->getLogger()->info(
                        'Unlink src success, dst file already exists and newer than src',
                        $context
                    );
                }
            }
        } else {
            $this->getLogger()->debug('Rename skipped, because dry run mode', $context);
        }
    }
}
