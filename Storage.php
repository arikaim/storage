<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Core\Storage;

use League\Flysystem\MountManager;
use League\Flysystem\Adapter\Local;
use League\Flysystem\Filesystem;

use Arikaim\Core\Utils\File;
use Arikaim\Core\Utils\Path;
use Arikaim\Core\Interfaces\Events\EventDispatcherInterface;
use Arikaim\Core\Interfaces\StorageInterface;

/**
 * Storage module class
 */
class Storage implements StorageInterface
{
    /**
     * Mount manager obj ref
     *
     * @var MountManager
     */
    private $manager;

    /**
     * Event Dispatcher
     *
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * Constructor
     */
    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->manager = new MountManager();
        $this->eventDispatcher = $eventDispatcher;
        $this->boot();
    }

    /**
     * Install module
     *
     * @return boolean
     */
    public function install()
    {
        if (File::exists(Path::STORAGE_PATH) == false) {
            File::makeDir(Path::STORAGE_PATH);
        };
        if (File::exists(Path::STORAGE_PATH . 'repository') == false) {
            File::makeDir(Path::STORAGE_PATH . 'repository');
        };

        return true;
    }

    /**
     * Boot module
     *
     * @return void
     */
    public function boot()
    {
        $localAdapter = new Local(Path::STORAGE_PATH);
        $this->mount('storage',$localAdapter);      
    }

    /**
     * Mount local filesystem
     *
     * @param string $name
     * @param string $path
     * @return MountManager|false
     */
    public function mountLocal($name, $path = null)
    {
        $path = (empty($path) == true) ? Path::STORAGE_PATH : $path;
        $adapter = new Local($path);

        return $this->mount($name,$adapter);
    }

    /**
     * Mouny filesystem
     *
     * @param string $name
     * @param object|string $adapter  Adapter object or  driver name
     * @return MountManager|false
     */
    public function mount($name, $adapter)
    {
        if (is_object($adapter) == false) {
            return false;
        }
        $filesystem = new Filesystem($adapter);
        return $this->manager->mountFilesystem($name,$filesystem);
    }

    /**
     * Get filesystem
     *
     * @param string $name
     * @return object
     */
    public function get($name)
    {
        return $this->manager->getFilesystem($name);
    }

    /**
     * Get full file path
     *
     * @param string $path
     * @param string $fileSystemName
     * @return string
     */
    public function getFuillPath($path = '', $fileSystemName = 'storage')
    {
        return $this->get($fileSystemName)->getAdapter()->getPathPrefix() . $path;
    }

    /**
     * Get directory contents
     *
     * @param string $path
     * @param boolean $recursive
     * @return array|false
     */
    public function listContents($path, $recursive = false)
    {
        if ($this->has($path) == true) {
            return $this->get('storage')->listContents($path,$recursive);
        }

        return false;
    }

    /**
     * Write files
     *
     * @param string $path
     * @param string $contents
     * @param array $config
     * @param boolean $dispatchEvent
     * @return bool 
     */
    public function write($path, $contents, $config = [], $dispatchEvent = true)
    {
        if ($this->has($path) == true) {
            return $this->update($path,$contents,$config);
        } else {
            $result = $this->get('storage')->write($path,$contents,$config);
            if ($result == true && $dispatchEvent == true) {               
                $this->eventDispatcher->dispatch('core.storage.write.file',$this->getEventParams($path));
            }
        }
       
        return $result;
    }

    /**
     * Write file stream
     *
     * @param string $path
     * @param resource $resource
     * @param array $config
     * @param boolean $dispatchEvent
     * @return bool
     */
    public function writeStream($path, $resource, $config = [], $dispatchEvent = true)
    {        
        $result = $this->get('storage')->writeStream($path,$resource,$config);
        if ($result == true && $dispatchEvent == true) {              
            $this->eventDispatcher->dispatch('core.storage.write.file',$this->getEventParams($path));               
        }
        
        return $result;
    }

    /**
     * Update files
     *
     * @param string $path
     * @param string $contents
     * @param array $config
     * @param boolean $dispatchEvent
     * @return bool 
     */
    public function update($path, $contents, $config = [], $dispatchEvent = true)
    {
        $result = $this->get('storage')->update($path,$contents,$config);       
        if ($result == true && $dispatchEvent == true) { 
            $this->eventDispatcher->dispatch('core.storage.update.file',$this->getEventParams($path));
        }
        
        return $result;
    }

    /**
     * Update files using a stream
     *
     * @param string $path
     * @param resource $contents
     * @param array $config
     * @param boolean $dispatchEvent
     * @return bool 
     */
    public function updateStream($path, $resource, $config = [], $dispatchEvent = true)
    {
        $result = $this->get('storage')->updateStream($path,$resource,$config);      
        if ($result == true && $dispatchEvent == true) { 
            $this->eventDispatcher->dispatch('core.storage.update.file',$this->getEventParams($path));
        } 

        return $result;
    }

    /**
     * Read file
     *
     * @param string $path
     * @return string|false
     */
    public function read($path)
    {
        return $this->get('storage')->read($path);
    }

    /**
     * Read file as a stream
     *
     * @param string $path
     * @return resource|false
     */
    public function readStream($path)
    {
        return $this->get('storage')->readStream($path);
    }

    /**
     * Delete file from storage folder
     *
     * @param string $path
     * @param boolean $dispatchEvent
     * @return boolean
     */
    public function delete($path, $dispatchEvent = true)
    {
        $result = $this->get('storage')->delete($path);   
        if ($result == true && $dispatchEvent == true) {           
            $this->eventDispatcher->dispatch('core.storage.delete.file',$this->getEventParams($path));
        }
      
        return $result;
    }
    
    /**
     * Rename files
     *
     * @param string $from
     * @param string $to
     * @param boolean $dispatchEvent
     * @return boolean
     */
    public function rename($from, $to, $dispatchEvent = true)
    {
        $result = $this->get('storage')->rename($from,$to);
        if ($result == true && $dispatchEvent == true) { 
            $this->eventDispatcher->dispatch('core.storage.rename.file',$this->getEventParams($from,$to));
        }
        
        return $result;
    }

    /**
     * Delete directory in storage folder
     *
     * @param string $path
     * @param boolean $dispatchEvent
     * @return boolean
     */
    public function deleteDir($path, $dispatchEvent = true)
    {
        $result = $this->get('storage')->deleteDir($path);
        if ($result == true && $dispatchEvent == true) {            
            $this->eventDispatcher->dispatch('core.storage.delete.dir',$this->getEventParams($path));            
        }
        
        return $result;
    }

    /**
     * Create directory in storage folder
     *
     * @param string $path
     * @param boolean $dispatchEvent
     * @return boolean
     */
    public function createDir($path, $dispatchEvent = true)
    {
        $result = $this->get('storage')->createDir($path);
        if ($result == true && $dispatchEvent == true) {            
            $this->eventDispatcher->dispatch('core.storage.create.dir',$this->getEventParams($path));
        }
        
        return $result;
    }

    /**
     * Return true if file exist
     *
     * @param string $path
     * @return boolean
     */
    public function has($path)
    {
        return $this->get('storage')->has($path);
    }

    /**
     * Copy files
     *
     * @param string $from
     * @param string $to
     * @param boolean $dispatchEvent
     * @return void
     */
    public function copy($from, $to, $dispatchEvent = true)
    {
        $result = $this->get('storage')->copy($from,$to);
        if ($result == true && $dispatchEvent == true) {            
            $this->eventDispatcher->dispatch('core.storage.copy.file',$this->getEventParams($from,$to));
        }

        return $result;
    }

    /**
     * Get Mimetypes
     *
     * @param string $path
     * @return string
     */
    public function getMimetype($path)
    {
        return $this->get('storage')->getMimetype($path);
    }

    /**
     * Get file size
     *
     * @param string $path
     * @return integer|false
     */
    public function getSize($path)
    {
        return $this->get('storage')->getSize($path);
    }

    /**
     * Get Mount Manager
     *
     * @return MountManager
     */
    public function manager()
    {
        return $this->manager;
    }

    /**
     * Get event params
     *
     * @param string $path
     * @param string|null $to
     * @return array
     */
    private function getEventParams($path, $to = null)
    {
        $file = [
            'path'      => $path,
            'full_path' => $this->getFuillPath($path),
            'size'      => $this->getSize($path),
            'mime_type' => $this->getMimetype($path)
        ];
        if (empty($to) == false) {
            return [
                'from'  => $file,
                'to'    => [
                    'path'      => $to,
                    'full_path' => $this->getFuillPath($to),
                    'size'      => $this->getSize($to),
                    'mime_type' => $this->getMimetype($to)
                ]
            ];  
        }

        return $file;
    }
}
