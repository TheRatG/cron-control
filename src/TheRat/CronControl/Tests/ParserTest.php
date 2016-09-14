<?php
namespace TheRat\CronControl\Tests;

use TheRat\CronControl\Parser;

/**
 * Class ParserTest
 * @package TheRat\CronControl\Tests
 */
class ParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider testForexCrontabDataProvider
     * @param array $input
     * @param array $expected
     */
    public function testForexCrontab($input, $expected)
    {
        $parser = new Parser();
        $actual = $parser->generateJobsFromString($input);

        $this->assertEquals($expected, $actual);
    }

    /**
     * @return array
     */
    public function testForexCrontabDataProvider()
    {
        $data = [];
        $dir = implode(DIRECTORY_SEPARATOR, [__DIR__, 'parser_resources']);
        $files = glob(implode(DIRECTORY_SEPARATOR, [$dir, 'crontab_*\.conf']));

        foreach ($files as $filename) {
            if (!file_exists($filename.'.php')) {
                continue;
            }
            $expected = include $filename.'.php';
            $data[] = [
                file_get_contents($filename),
                $expected,
            ];
        }

        return $data;
    }
}
