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
     * System directories
     *
     * @var array
     */
    protected $systemDir = ['backup','public','repository','temp','bin'];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->manager = new MountManager();
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
     * @param object|string $adapter  Adapter object or driver name
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
     * @param string $fileSystemName
     * @return array|false
     */
    public function listContents($path = '', $recursive = false, $fileSystemName = 'storage')
    {       
        return $this->get($fileSystemName)->listContents($path,$recursive);      
    }

    /**
     * Return true if directory is empty
     *
     * @param string $path
     * @param string $fileSystemName
     * @return boolean
     */
    public function isEmpty($path, $fileSystemName = 'storage')
    {
        $files = $this->listContents($path,false,$fileSystemName);

        return empty($files);
    }

    /**
     * Write files
     *
     * @param string $path
     * @param string $contents
     * @param array $config
     * @param string $fileSystemName
     * @return bool 
     */
    public function write($path, $contents, $config = [], $fileSystemName = 'storage')
    {
        if ($this->has($path) == true) {
            return $this->update($path,$contents,$config,$fileSystemName);
        } 
        
        return $this->get($fileSystemName)->write($path,$contents,$config);                 
    }

    /**
     * Write file stream
     *
     * @param string $path
     * @param resource $resource
     * @param array $config
     * @param string $fileSystemName
     * @return bool
     */
    public function writeStream($path, $resource, $config = [], $fileSystemName = 'storage')
    {        
        return $this->get($fileSystemName)->writeStream($path,$resource,$config);        
    }

    /**
     * Update files
     *
     * @param string $path
     * @param string $contents
     * @param array $config
     * @param string $fileSystemName
     * @return bool 
     */
    public function update($path, $contents, $config = [], $fileSystemName = 'storage')
    {
        return $this->get($fileSystemName)->update($path,$contents,$config);                  
    }

    /**
     * Update files using a stream
     *
     * @param string $path
     * @param resource $contents
     * @param array $config
     * @param string $fileSystemName
     * @return bool 
     */
    public function updateStream($path, $resource, $config = [], $fileSystemName = 'storage')
    {
        return $this->get($fileSystemName)->updateStream($path,$resource,$config);              
    }

    /**
     * Read file
     *
     * @param string $path
     * @param string $fileSystemName
     * @return string|false
     */
    public function read($path, $fileSystemName = 'storage')
    {
        return $this->get($fileSystemName)->read($path);
    }

    /**
     * Read file as a stream
     *
     * @param string $path
     * @param string $fileSystemName
     * @return resource|false
     */
    public function readStream($path, $fileSystemName = 'storage')
    {
        return $this->get($fileSystemName)->readStream($path);
    }

    /**
     * Delete all files in direcotry (recursive)
     *
     * @param string $path
     * @param string $fileSystemName
     * @return void
     */
    public function deleteFiles($path, $fileSystemName = 'storage')
    {
        $files = $this->listContents($path,true,$fileSystemName);

        foreach ($files as $item) {
            if ($item['type'] == 'dir') {             
                if ($this->has($item['path'],$fileSystemName) == true) {
                    $this->deleteDir($path,$fileSystemName);
                }
            }           
            if ($this->has($item['path'],$fileSystemName) == true) {
                $this->delete($item['path'],$fileSystemName);
            }            
        }       
    }

    /**
     * Move all files (recursive)
     *
     * @param string $path
     * @param string $to
     * @param string $fileSystemName
     * @return void
     */
    public function moveFiles($from, $to, $fileSystemName = 'storage')
    {
        $files = $this->listContents($from,true,$fileSystemName);

        foreach ($files as $item) {
            $this->copy($item['path'],$to,$fileSystemName);
            $this->delete($item['path'],$fileSystemName);                    
        }       
    }

    /**
     * Delete file from storage folder
     *
     * @param string $path
     * @param string $fileSystemName
     * @return boolean
     */
    public function delete($path, $fileSystemName = 'storage')
    {
        return $this->get($fileSystemName)->delete($path);           
    }
    
    /**
     * Rename files
     *
     * @param string $from
     * @param string $to
     * @param string $fileSystemName
     * @return boolean
     */
    public function rename($from, $to, $fileSystemName = 'storage')
    {
        return $this->get($fileSystemName)->rename($from,$to);        
    }

    /**
     * Delete directory in storage folder
     *
     * @param string $path
     * @param string $fileSystemName
     * @return boolean
     */
    public function deleteDir($path, $fileSystemName = 'storage')
    {
        return $this->get($fileSystemName)->deleteDir($path);      
    }

    /**
     * Create directory in storage folder
     *
     * @param string $path
     * @param string $fileSystemName
     * @return boolean
     */
    public function createDir($path, $fileSystemName = 'storage')
    {
        return $this->get($fileSystemName)->createDir($path);                   
    }

    /**
     * Return true if file exist
     *
     * @param string $path
     * @param string $fileSystemName
     * @return boolean
     */
    public function has($path, $fileSystemName = 'storage')
    {
        return $this->get($fileSystemName)->has($path);
    }

    /**
     * Copy files
     *
     * @param string $from
     * @param string $to
     * @param string $fileSystemName
     * @return void
     */
    public function copy($from, $to, $fileSystemName = 'storage')
    {
        return $this->get($fileSystemName)->copy($from,$to);       
    }

    /**
     * Move file
     *
     * @param string $from
     * @param string $to
     * @param string $fileSystemName
     * @return boolean
     */
    public function moveFile($from, $to, $fileSystemName = 'storage')
    {
        $result = $this->copy($from,$to,$fileSystemName);  
        if ($result == false) {
            return false;
        }    
        
        return $this->delete($from,$fileSystemName);
    }

    /**
     * Get file info
     *
     * @param string $path
     * @param string $fileSystemName
     * @return array
     */
    public function getMetadata($path, $fileSystemName = 'storage')
    {
        return $this->get($fileSystemName)->getMetadata($path);
    }

    /**
     * Return true if file is directory
     *
     * @param string $path
     * @param string $fileSystemName
     * @return boolean
     */
    public function isDir($path, $fileSystemName = 'storage')
    {
        if ($this->has($path,$fileSystemName) == false) {
            return false;
        }
        $meta = $this->getMetadata($path,$fileSystemName);

        return ($meta['type'] == 'dir');
    }

    /**
     * Get Mimetypes
     *
     * @param string $path
     * @param string $fileSystemName
     * @return string
     */
    public function getMimetype($path, $fileSystemName = 'storage')
    {
        return $this->get($fileSystemName)->getMimetype($path);
    }

    /**
     * Get file size
     *
     * @param string $path
     * @param string $fileSystemName
     * @return integer|false
     */
    public function getSize($path, $fileSystemName = 'storage')
    {
        return $this->get($fileSystemName)->getSize($path);
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
     * Get sytem directories
     *
     * @return array
     */
    public function getSystemDirectories()
    {
        return $this->systemDir;
    }

    /**
     * Return true if path is system dir
     *
     * @param string $path
     * @return boolean
     */
    public function isSystemDir($path)
    {
        return in_array($path,$this->systemDir);
    }
}
