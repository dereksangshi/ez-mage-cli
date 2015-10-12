<?php

namespace Ez\MageCli\Command\Finder;

/**
 * Class Psr0
 *
 * @package Ez\MageCli\Command\Finder
 * @author Derek Li
 */
class Psr0 extends FinderAbstract
{
    /**
     * Directories to scan.
     *
     * @var array
     */
    protected $dirs = array();

    /**
     * Add directory.
     *
     * @param string $dir The dir to add.
     * @return $this
     */
    public function addDir($dir)
    {
        if (!in_array($dir, $this->dirs)) {
            $this->dirs[] = $dir;
        }
        return $this;
    }

    /**
     * Get all the directories to scan.
     *
     * @return array
     */
    public function getDirs()
    {
        return $this->dirs;
    }

    /**
     * Scan all the directories to find the commands.
     *
     * @return $this
     */
    public function scan()
    {
        foreach ($this->getDirs() as $dir) {
            $files = scandir($dir);
            if (count($files) > 0) {
                foreach ($files as $f) {
                    if (fnmatch('*.php', $f)) {
                        $this->findCommandClass($dir, $f);
                    }
                }
            }
        }
    }

    /**
     * Find the command by default psr0 naming conventions.
     * e.g.
     * Reindex.php => Ez_MageCli_Command_Reindex
     *
     * @param string $dir The directory to look into.
     * @param string $file The name of the file.
     * @return $this
     */
    protected function findCommandClass($dir, $file)
    {
        $filename = $dir.DIRECTORY_SEPARATOR.$file;
        require_once $filename;
        $class = sprintf('Ez_MageCli_Command_%s', ucfirst(str_replace('.php', '', $file)));
        if (class_exists($class, true)) {
            $this->commandClasses[$filename] = $class;
        }
        return $this;
    }
}
