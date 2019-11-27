<?php
/**
 * Arikaim
 *
 * @link        http://www.arikaim.com
 * @copyright   Copyright (c)  Konstantin Atanasov <info@arikaim.com>
 * @license     http://www.arikaim.com/license
 * 
*/
namespace Arikaim\Modules\Storage\Drivers;

use Arikaim\Core\Driver\Traits\Driver;
use Arikaim\Core\Interfaces\DriverInterface;

/**
 * Ftp flysystem driver class
 */
class FtpDriver implements DriverInterface
{   
    use Driver;
   
    /**
     * Constructor
     */
    public function __construct()
    {
        $this->setDriverParams('ftp','flysystem','Ftp filesystem','Driver for ftp filesystem for flysystem module');
        $this->setDriverClass('League\\Flysystem\\Adapter\\Ftp');
    }

    /**
     * Create driver config properties array
     *
     * @param Arikaim\Core\Collection\Properties $properties
     * @return array
     */
    public function createDriverConfig($properties)
    {              
        // ftp host
        $properties->property('host',function($property) {
            $property
                ->title('Host')
                ->type('text')
                ->default('ftp.example.com');
        });
        // username
        $properties->property('username',function($property) {
            $property
                ->title('Username')
                ->type('text')           
                ->default('');
        });
        // port
        $properties->property('port',function($property) {
            $property
                ->title('Port')
                ->type('number')
                ->default(21);
        });
        // Root path
        $properties->property('root',function($property) {
            $property
                ->title('Root path')
                ->type('text')
                ->default('/');
        });
        // use ssl
        $properties->property('ssl',function($property) {
            $property
                ->title('SSL')
                ->type('boolean')
                ->default(true);
        });
        // passive
        $properties->property('passive',function($property) {
            $property
                ->title('Passive')
                ->type('boolean')
                ->default(true);
        });
        // timeout
        $properties->property('timeout',function($property) {
            $property
                ->title('Timeout')
                ->type('number')
                ->default(30);
        });
        // ignorePassiveAddress
        $properties->property('ignorePassiveAddress',function($property) {
            $property
                ->title('ignorePassiveAddress')
                ->type('boolean')
                ->default(false)
                ->value(false)
                ->readonly(true);
        });
    }
}
