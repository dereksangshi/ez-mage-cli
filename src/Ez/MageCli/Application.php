<?php

namespace Ez\MageCli;

use Symfony\Component\Console\Application as SymfonyConsole;
use Ez\MageCli\Command\Finder\Psr0 as Psr0Finder;
use Ez\MageCli\Command\Finder\MageExtension as MageExtensionFinder;
use Ez\MageBridge\MageBridge;

/**
 * Class Application
 *
 * @todo To find a better way to cache as much as possible.
 *      The current problem is when caching all the commands,
 *      errors might occur due to the fail of serializing closures.
 * @package Ez\MageCli
 * @author Derek Li
 */
class Application
{
    /**
     * @var
     */
    protected $symfonyConsole = null;

    /**
     * The directories or extensions to look for commands.
     *
     * @var array
     */
    protected $find = array();

    /**
     * @var null
     */
    protected $cacheDir = null;

    /**
     * If the cache is enabled.
     *
     * @var bool
     */
    protected $cacheEnabled = false;

    /**
     * @var null
     */
    protected $assembler = null;

    /**
     * @var Psr0Finder
     */
    protected $psr0Finder = null;

    /**
     * @var null
     */
    protected $mageExtensionFinder = null;

    /**
     * @var MageBridge
     */
    protected $mageBridge = null;

    /**
     * Constructor.
     *
     * @param SymfonyConsole $symfonyConsole The Symfony console to use.
     * @param array $find OPTIONAL The places to look for commands.
     * @param MageBridge $mageBridge OPTIONAL Everything Magento should go through MageBridge.
     * @param string $cacheDir OPTIONAL The directory for the caches.
     */
    public function __construct(SymfonyConsole $symfonyConsole, array $find = null, MageBridge $mageBridge = null, $cacheDir = null)
    {
        $this->symfonyConsole = $symfonyConsole;
        if (isset($find)) {
            $this->setFind($find);
        }
        if (isset($mageBridge)) {
            $this->setMageBridge($mageBridge);
        }
        if (isset($cacheDir)) {
            $this->setCacheDir($cacheDir);
        }
    }

    /**
     * Set the places to look for commands.
     *
     * @param array $find The places to look for commands.
     * @return $this
     */
    public function setFind(array $find)
    {
        $this->find = $find;
        return $this;
    }

    /**
     * Get the places to look for commands.
     *
     * @return array
     */
    public function getFind()
    {
        return $this->find;
    }

    /**
     * Get the assembler.
     *
     * @return Assembler
     */
    public function getAssembler()
    {
        if (!isset($this->assembler)) {
            $this->assembler = new Assembler($this->getSymfonyConsole());
        }
        return $this->assembler;
    }

    /**
     * Get the psr0 finder.
     *
     * @return Psr0Finder
     */
    public function getPsr0Finder()
    {
        if (!isset($this->psr0Finder)) {
            $this->psr0Finder = new Psr0Finder();
        }
        return $this->psr0Finder;
    }

    /**
     * Get magento extension finder.
     *
     * @return MageExtensionFinder
     */
    public function getMageExtensionFinder()
    {
        if (!isset($this->mageExtensionFinder)) {
            $this->mageExtensionFinder = new MageExtensionFinder();
            $this->mageExtensionFinder->setMageBridge($this->getMageBridge());
        }
        return $this->mageExtensionFinder;
    }

    /**
     * Tell the application the places to find commands.
     *
     * @return $this
     */
    public function findCommands()
    {
        $find = $this->getFind();
        if (array_key_exists('psr0', $find)) {
            $this->findPsr0Commands($find['psr0']);
        }
        if (array_key_exists('mageExtension', $find)) {
            $this->findMageExtensionCommands($find['mageExtension']);
        }
        return $this;
    }

    /**
     * Enable caching.
     *
     * @return $this
     */
    public function enableCache()
    {
        $this->cacheEnabled = true;
        return $this;
    }

    /**
     * If the caching is enabled.
     *
     * @return bool
     */
    public function isCacheEnabled()
    {
        return $this->cacheEnabled;
    }

    /**
     * Create the cache directory.
     *
     * @param string $dir The cache directory.
     * @return $this
     */
    protected function createCacheDir($dir)
    {
        mkdir($dir, 0777, true);
        return $this;
    }

    /**
     * Set the cache directory, create it if not exists, and enable cache.
     *
     * @param string $dir The cache directory.
     * @return $this
     */
    public function setCacheDir($dir)
    {
        if (isset($dir)) {
            if (!file_exists($dir)) {
                $this->createCacheDir($dir);
            }
            $this->cacheDir = $dir;
            $this->enableCache();
        }
        return $this;
    }

    /**
     * Get the cache directory.
     *
     * @return null
     */
    public function getCacheDir()
    {
        return $this->cacheDir;
    }

    /**
     * All the magneto related information will be retrieved from MageBridge.
     *
     * @param MageBridge $mageBridge
     * @return $this
     */
    public function setMageBridge(MageBridge $mageBridge)
    {
        $this->mageInfo = $mageBridge;
        return $this;
    }

    /**
     * Get MageBridge.
     *
     * @return MageBridge
     */
    public function getMageBridge()
    {
        return $this->mageInfo;
    }

    /**
     * Get the cache filename for the assembler.
     *
     * @return string
     */
    public function getAssemblerCacheFilename()
    {
        return sprintf(
            '%s%s',
            rtrim($this->getCacheDir(), DIRECTORY_SEPARATOR),
            DIRECTORY_SEPARATOR.'ez-magecli-assembler.ezc'
        );
    }

    /**
     * Set the directories of commands to look for based on psr0 naming conventions.
     *
     * @param mixed $dirs The directory or directories to look for.
     * @return $this
     */
    public function findPsr0Commands($dirs)
    {
        // Add multiple directories.
        if (is_array($dirs)) {
            foreach ($dirs as $d) {
                if (file_exists($d)) {
                    $this->getPsr0Finder()->addDir($d);
                }
            }
            return $this;
        }
        // Add a single directory.
        $this->getPsr0Finder()->addDir($dirs);
        return $this;
    }

    /**
     * Set the extensions to look for commands.
     *
     * @param mixed $extensions The extensions to look for.
     * @return $this
     */
    public function findMageExtensionCommands($extensions = '*')
    {
        // '*' means to include all the 3rd party extensions.
        if ($extensions == '*') {
            $this->getMageExtensionFinder()->loadAllExtensions();
            return $this;
        }
        // To include specific extensions.
        if (is_array($extensions)) {
            foreach ($extensions as $e) {
                $this->getMageExtensionFinder()->addExtension($e);
            }
            return $this;
        }
        // To include one extension.
        $this->getMageExtensionFinder()->addExtension($extensions);
        return $this;
    }

    /**
     * Get the Symfony console.
     *
     * @return SymfonyConsole
     */
    public function getSymfonyConsole()
    {
        return $this->symfonyConsole;
    }

    /**
     * Cache the assembler.
     *
     * @return $this
     */
    public function cacheAssembler()
    {
        // No caching if it's not enabled.
        if (!$this->isCacheEnabled()) {
            return $this;
        }
        // No caching if no specified cache directory.
        if (!isset($this->cacheDir)) {
            return $this;
        }
        $assembler = $this->getAssembler();
        // No caching if the assembler itself doesn't exist.
        if (!isset($assembler)) {
            return $this;
        }
        // Create the cache directory if it doesn't exist.
        if (!file_exists($this->cacheDir)) {
            $this->createCacheDir($this->cacheDir);
        }
        file_put_contents($this->getAssemblerCacheFilename(), serialize($assembler));
        return $this;
    }

    /**
     * Load assembler from cache.
     *
     * @return mixed
     */
    public function loadAssemblerFromCache()
    {
        $assemblerCacheFilename = $this->getAssemblerCacheFilename();
        if (!file_exists($assemblerCacheFilename)) {
            return null;
        }
        $cacheContent = file_get_contents($assemblerCacheFilename);
        if (empty($cacheContent)) {
            return null;
        }
        return unserialize($cacheContent);
    }

    /**
     * Start the console.
     *
     * @return $this
     */
    public function run()
    {
        if ($this->isCacheEnabled()) {
            $assembler = $this->loadAssemblerFromCache();
            if ($assembler instanceof Assembler) {
                $assembler->getConsole()->run();
                return;
            }
        }
        $this->findCommands();
        $assembler =
            $this->getAssembler()
            ->addCommands($this->getPsr0Finder()->getCommands())
            ->addCommands($this->getMageExtensionFinder()->getCommands())
            ->assemble();
        if ($this->isCacheEnabled()) {
            $this->cacheAssembler();
        }
        $assembler->getConsole()->run();
    }
}
