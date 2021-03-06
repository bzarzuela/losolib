<?php
use Doctrine\Common\ClassLoader,
    Doctrine\ORM\Configuration,
    Doctrine\ORM\EntityManager,
    Doctrine\ORM\Mapping\Driver\YamlDriver,
    Doctrine\ORM\Mapping\Driver\XmlDriver,
    Doctrine\ORM\Mapping\Driver\PhpDriver,
    Doctrine\Common\Cache\ApcCache,
    Doctrine\Common\Cache\MemcacheCache,
    Doctrine\Common\Cache\XcacheCache,
    Doctrine\Common\Cache\ArrayCache;

/**
 * An application resource for initializing your Doctrine2 environment
 *
 * @category   Zend
 * @package    LoSo_Zend_Application
 * @subpackage Resource
 * @author     Loïc Frering <loic.frering@gmail.com>
 */
class LoSo_Zend_Application_Resource_Doctrine2 extends Zend_Application_Resource_ResourceAbstract
{
    /**
     * Configuration options for Doctrine.
     *
     * @var \Doctrine\ORM\Configuration
     */
    protected $_config;

    /**
     * Initialize Doctrine.
     *
     * @return Doctrine\ORM\EntityManager
     */
    public function init()
    {
        $options = $this->getOptions();

        $this->_config = new Configuration;

        // Metadata driver
        $this->_initMetadataDriver();

        // Parameters
        $this->_initParameters();

        // Set up caches
        $this->_initCache();

        // Proxy configuration
        $this->_initProxy();

        // Database connection information
        $connectionOptions = $this->_initConnection();

        // Create EntityManager
        $em = EntityManager::create($connectionOptions, $this->_config);
        $this->getBootstrap()->getContainer()->em = $em;
        return $em;
    }

    /**
     * Initialize metadata driver from resource options.
     *
     * @return void
     */
    protected function _initMetadataDriver()
    {
        $options = $this->getOptions();

        $mappingPaths = $options['metadata']['mappingPaths'];

        $driver = $options['metadata']['driver'];
        switch($driver) {
            case 'yaml':
                $driver = new YamlDriver($mappingPaths);
                break;

            case 'xml':
                $driver = new XmlDriver($mappingPaths);
                break;

            case 'php':
                $driver = new PhpDriver($mappingPaths);
                break;

            default:
                $driver = $this->_config->newDefaultAnnotationDriver($mappingPaths);
        }

        $this->_config->setMetadataDriverImpl($driver);
    }

    /**
     * Initialize Doctrine cache configuration from resource options.
     *
     * @return void
     */
    protected function _initCache()
    {
        $options = $this->getOptions();
        switch($options['cache']) {
            case 'apc':
                $cache = new ApcCache();
                break;

            case 'memcache':
                $cache = new MemcacheCache();
                break;

            case 'xcache':
                $cache = new XcacheCache();
                break;

            default:
                $cache = new ArrayCache();
        }
        $this->_config->setMetadataCacheImpl($cache);
        $this->_config->setQueryCacheImpl($cache);
    }

    /**
     * Initialize Doctrine proxy configuration from resource options.
     *
     * @return void
     */
    protected function _initProxy()
    {
        $options = $this->getOptions();
        $this->_config->setProxyDir(isset($options['proxy']['directory']) ? $options['proxy']['directory'] : APPLICATION_PATH . '/data/doctrine2/Proxies');
        $this->_config->setProxyNamespace(isset($options['proxy']['namespace']) ? $options['proxy']['namespace'] : 'Proxies');
    }

    /**
     * Initialize Doctrine connection configuration from resource options.
     *
     * @return void
     */
    protected function _initConnection()
    {
        $options = $this->getOptions();
        return $options['connection'];
    }

    /**
     * Save Doctrine parameters for latter retrieving.
     *
     * @return void
     */
    protected function _initParameters()
    {
        $options = $this->getOptions();
        $container = $this->getBootstrap()->getApplication()->getBootstrap()->getContainer();
        if($container instanceof \Symfony\Components\DependencyInjection\ContainerInterface) {
            $container->setParameter('doctrine.orm.mapping_paths', $options['metadata']['mappingPaths']);
            $container->setParameter('doctrine.orm.entities_paths', $options['metadata']['entitiesPaths']);
        }
        else {
            Zend_Registry::set('doctrine.config', array(
                'doctrine.orm.mapping_paths' => $options['metadata']['mappingPaths'],
                'doctrine.orm.entities_paths' => $options['metadata']['entitiesPaths']
            ));
        }
    }
}
