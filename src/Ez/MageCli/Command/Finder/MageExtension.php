<?php

namespace Ez\MageCli\Command\Finder;

use Ez\MageBridge\MageBridge;

/**
 * Class MageExtension
 *
 * @package Ez\MageCli\Command\Finder
 * @author Derek Li
 */
class MageExtension extends FinderAbstract
{
    /**
     * @var MageBridge
     */
    protected $mageBridge = null;

    /**
     * Magento extensions to scan.
     *
     * @var array
     */
    protected $extensions = array();

    /**
     * Everything Magento should go through MageBridge.
     *
     * @param MageBridge $mageBridge
     * @return $this
     */
    public function setMageBridge(MageBridge $mageBridge)
    {
        $this->mageBridge = $mageBridge;
        return $this;
    }

    /**
     * Get MageBridge.
     *
     * @return MageBridge
     */
    public function getMageBridge()
    {
        return $this->mageBridge;
    }

    /**
     * Add Magento extension.
     *
     * @param string $extension The extension to add.
     * @return $this
     */
    public function addExtension($extension)
    {
        if (!in_array($extension, $this->extensions)) {
            $this->extensions[] = $extension;
        }
        return $this;
    }

    /**
     * Get all the Magento extensions to scan.
     *
     * @return array
     */
    public function getExtensions()
    {
        return $this->extensions;
    }

    /**
     * Load all the 3rd party extensions.
     *
     * @return $this
     */
    public function loadAllExtensions()
    {
        foreach ($this->getMageBridge()->getMageInfo()->getExtensionNames() as $extensionName) {
            $this->addExtension($extensionName);
        }
        return $this;
    }

    /**
     * Scan all the Magento extensions to find the commands.
     *
     * @return $this
     */
    public function scan()
    {
        foreach ($this->getExtensions() as $extensionName) {
            $extDir = $this
                    ->getMageBridge()
                    ->getMageInfo()
                    ->getExtensionDir($extensionName).DIRECTORY_SEPARATOR.'Command';
            $files = scandir($extDir);
            if (count($files) > 0) {
                foreach ($files as $f) {
                    if (fnmatch('*.php', $f)) {
                        $className = sprintf(
                            '%s_Command_%s',
                            $extensionName,
                            ucfirst(str_replace('.php', '', $f))
                        );
                        if (class_exists($className, true) &&
                            !in_array($className, $this->commandClasses)
                        ) {
                            $this->commandClasses[] = $className;
                        }
                    }
                }
            }
        }
    }
}
