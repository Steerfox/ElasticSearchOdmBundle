<?php

/*
 * This file is part of the Steerfox package.
 *
 * (c) NFQ Technologies UAB <info@nfq.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Steerfox\ElasticsearchBundle\Service;

use Elasticsearch\Client;
use Elasticsearch\Common\Exceptions\Missing404Exception;
use Steerfox\ElasticsearchBundle\Event\BulkEvent;
use Steerfox\ElasticsearchBundle\Event\CommitEvent;
use Steerfox\ElasticsearchBundle\Event\Events;
use Steerfox\ElasticsearchBundle\Exception\BulkWithErrorsException;
use Steerfox\ElasticsearchBundle\Mapping\Caser;
use Steerfox\ElasticsearchBundle\Mapping\MetadataCollector;
use Steerfox\ElasticsearchBundle\Result\Converter;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Stopwatch\Stopwatch;

/**
 * Manager class.
 */
class Manager
{
    /**
     * The size that defines after how much document inserts call commit function.
     *
     * @var int
     */
    private $bulkCommitSize = 100;

    /**
     * Container to count how many documents was passed to the bulk query.
     *
     * @var int
     */
    private $bulkCount = 0;

    /**
     * @var array Holder for consistency, refresh and replication parameters
     */
    private $bulkParams = [];

    /**
     * @var array Container for bulk queries
     */
    private $bulkQueries = [];

    /**
     * @var Client
     */
    private $client;

    /**
     * After commit to make data available the refresh or flush operation is needed
     * so one of those methods has to be defined, the default is refresh.
     *
     * @var string
     */
    private $commitMode = 'refresh';

    /**
     * @var array Manager configuration
     */
    private $config = [];

    /**
     * @var Converter
     */
    private $converter;

    /**
     * @var EventDispatcherInterface
     */
    private $eventDispatcher;

    /**
     * @var array
     */
    private $indexSettings;

    /**
     * @var MetadataCollector
     */
    private $metadataCollector;

    /**
     * @var string Manager name
     */
    private $name;

    /**
     * @var Repository[] Repository local cache
     */
    private $repositories;

    /**
     * @var Stopwatch
     */
    private $stopwatch;

    /**
     * @param string            $name   Manager name
     * @param array             $config Manager configuration
     * @param Client            $client
     * @param array             $indexSettings
     * @param MetadataCollector $metadataCollector
     * @param Converter         $converter
     */
    public function __construct(
        $name,
        array $config,
        $client,
        array $indexSettings,
        $metadataCollector,
        $converter
    ) {
        $this->name = $name;
        $this->config = $config;
        $this->client = $client;
        $this->indexSettings = $indexSettings;
        $this->metadataCollector = $metadataCollector;
        $this->converter = $converter;
    }

    /**
     * Clears elasticsearch client cache.
     */
    public function clearCache()
    {
        $mappings = $this->getMetadataCollector()->getMappings($this->config['mappings']);
        foreach ($mappings as $mappingData) {
            $indexName = $this->getIndexNameByType($mappingData['type']);
            if ($this->indexExists($indexName)) {
                $this->getClient()->indices()->clearCache(['index' => $indexName]);
            }
        }
    }

    /**
     * @return MetadataCollector
     */
    public function getMetadataCollector()
    {
        return $this->metadataCollector;
    }

    /**
     * Get index name for type
     *
     * @param string $type Valid type name
     *
     * @return string Index name
     * @throws \Exception
     */
    public function getIndexNameByType($type)
    {
        if ('' == $type || !array_key_exists($type, $this->indexSettings['body']['mappings'])) {
            throw new \Exception('Type is required and be a valid type.');
        }

        return $this->getIndexName().'_'.Caser::snake($type);
    }

    /**
     * Returns index name this connection is attached to.
     *
     * @return string
     */
    public function getIndexName()
    {
        return $this->indexSettings['index'];
    }

    /**
     * Checks if connection index is already created.
     *
     * @return bool
     */
    public function indexExists($indexName = '')
    {
        if ('' == $indexName) {
            throw new \Exception('Index name is required to check existing index');
        }

        return $this->getClient()->indices()->exists(['index' => $indexName]);
    }

    /**
     * Returns Elasticsearch connection.
     *
     * @return Client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * Clears scroll.
     *
     * @param string $scrollId
     */
    public function clearScroll($scrollId)
    {
        $this->getClient()->clearScroll(['scroll_id' => $scrollId]);
    }

    /**
     * This method create all index for this manager if index not exist.
     *
     * @return array List of actions.
     *
     * @throws \Exception
     */
    public function createAllManagerIndex()
    {
        $actionRecap = [];
        foreach ($this->indexSettings['body']['mappings'] as $typeName => $typeMapping) {
            //Create Index by type
            $indexName = $this->getIndexNameByType($typeName);
            if (!$this->indexExists($indexName)) {
                $indexSettings = [
                    'index' => $indexName,
                    'body' => [
                        'settings' => $this->indexSettings['body']['settings'],
                        'mappings'=> [
                            $typeName => $typeMapping
                        ]
                    ]
                ];


                $result = $this->getClient()->indices()->create($indexSettings);
                if($result['acknowledged'] != true){
                    throw new \Exception('Index cannot created : ' . print_r($result, true));
                }

                $actionRecap[$typeName] = [
                    'index' => $result['index'],
                    'state' => 'Created.',
                ];
            } else {
                $actionRecap[$typeName] = [
                    'index' => $indexName,
                    'state' => 'Already Exist',
                ];
            }
        }

        return $actionRecap;
    }

    /**
     * Drops elasticsearch index.
     */
    public function dropIndex($indexName = '')
    {
        if ('' == $indexName) {
            throw new \Exception('Index name is required to drop index');
        }

        return $this->getClient()->indices()->delete(['index' => $indexName]);
    }

    /**
     * Returns a single document by ID. Returns NULL if document was not found.
     *
     * @param string $className Document class name or Elasticsearch type name
     * @param string $id        Document ID to find
     * @param string $routing   Custom routing for the document
     *
     * @return object
     */
    public function find($className, $id, $routing = null)
    {
        $type = $this->resolveTypeName($className);

        $params = [
            'index' => $this->getIndexNameByType($type),
            'type'  => $type,
            'id'    => $id,
        ];

        if ($routing) {
            $params['routing'] = $routing;
        }

        try {
            $result = $this->getClient()->get($params);
        } catch (Missing404Exception $e) {
            return null;
        }

        return $this->getConverter()->convertToDocument($result, $this);
    }

    /**
     * Resolves type name by class name.
     *
     * @param string $className
     *
     * @return string
     */
    private function resolveTypeName($className)
    {
        if (strpos($className, ':') !== false || strpos($className, '\\') !== false) {
            return $this->getMetadataCollector()->getDocumentType($className);
        }

        return $className;
    }

    /**
     * @return Converter
     */
    public function getConverter()
    {
        return $this->converter;
    }

    /**
     * Gets Elasticsearch aliases information.
     *
     * @param $params
     *
     * @return array
     */
    public function getAliases($params = [])
    {
        return $this->getClient()->indices()->getAliases($params);
    }

    /**
     * @return int
     */
    public function getBulkCommitSize()
    {
        return $this->bulkCommitSize;
    }

    /**
     * @param int $bulkCommitSize
     */
    public function setBulkCommitSize($bulkCommitSize)
    {
        $this->bulkCommitSize = $bulkCommitSize;
    }

    /**
     * @return array
     */
    public function getConfig()
    {
        return $this->config;
    }

    /**
     * Returns mappings of the index for this connection.
     *
     * @return array
     */
    public function getIndexMappings()
    {
        return $this->indexSettings['body']['mappings'];
    }

    /**
     * Returns mappings of one type for this connection.
     *
     * @param string $type
     *
     * @return array
     * @throws \Exception
     */
    public function getIndexMappingsByType($type)
    {
        if(!is_string($type) && !array_key_exists($type, $this->indexSettings['body']['mappings'])){
            throw new \Exception('Cannot get mapping for type : ' . $type);
        }

        return $this->indexSettings['body']['mappings'][$type];
    }

    /**
     * Get mapping for one field on type.
     *
     * @param $type
     * @param $field
     *
     * @return mixed
     * @throws \Exception
     */
    public function getDocumentFieldType($type, $field)
    {
        $typeMapping = $this->getIndexMappingsByType($type);
        if(!is_string($field) && !array_key_exists($field, $typeMapping['properties'])) {
            throw new \Exception('Cannot get mapping for field : ' . $field);
        }

        return $typeMapping['properties'][$field]['type'];
    }


    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * Returns repository by document class.
     *
     * @param string $className FQCN or string in Bundle:Document format
     *
     * @return Repository
     */
    public function getRepository($className)
    {
        if (!is_string($className)) {
            throw new \InvalidArgumentException('Document class must be a string.');
        }

        $directory = null;

        if (strpos($className, ':')) {
            $bundle = explode(':', $className)[0];

            if (isset($this->config['mappings'][$bundle]['document_dir'])) {
                $directory = $this->config['mappings'][$bundle]['document_dir'];
            }
        }

        $namespace = $this->getMetadataCollector()->getClassName($className, $directory);

        if (isset($this->repositories[$namespace])) {
            return $this->repositories[$namespace];
        }

        $repository = $this->createRepository($namespace);
        $this->repositories[$namespace] = $repository;

        return $repository;
    }

    /**
     * Creates a repository.
     *
     * @param string $className
     *
     * @return Repository
     */
    private function createRepository($className)
    {
        return new Repository($this, $className);
    }

    /**
     * Calls "Get Settings API" in Elasticsearch and will return you the currently configured settings.
     *
     * return array
     */
    public function getSettings($indexName = '')
    {
        if ('' === $indexName) {
            throw new \Exception('Index name is require to get settings');
        }

        return $this->getClient()->indices()->getSettings(['index' => $indexName]);
    }

    /**
     * Returns Elasticsearch version number.
     *
     * @return string
     */
    public function getVersionNumber()
    {
        return $this->client->info()['version']['number'];
    }

    /**
     * Adds document to next flush.
     *
     * @param object $document
     */
    public function persist($document)
    {
        $documentArray = $this->converter->convertToArray($document);
        $type = $this->getMetadataCollector()->getDocumentType(get_class($document));

        $this->bulk('index', $type, $documentArray);
    }

    /**
     * Adds query to bulk queries container.
     *
     * @param string       $operation One of: index, update, delete, create.
     * @param string|array $type      Elasticsearch type name.
     * @param array        $query     DSL to execute.
     *
     * @throws \InvalidArgumentException
     *
     * @return null|array
     */
    public function bulk($operation, $type, array $query)
    {
        if (!in_array($operation, ['index', 'create', 'update', 'delete'])) {
            throw new \InvalidArgumentException('Wrong bulk operation selected');
        }

        $this->eventDispatcher->dispatch(
            Events::BULK,
            new BulkEvent($operation, $type, $query)
        );

        $indexName = $this->getIndexNameByType($type);

        $this->bulkQueries[$indexName]['body'][] = [
            $operation => array_filter(
                [
                    '_type'    => $type,
                    '_id'      => isset($query['_id']) ? $query['_id'] : null,
                    '_ttl'     => isset($query['_ttl']) ? $query['_ttl'] : null,
                    '_routing' => isset($query['_routing']) ? $query['_routing'] : null,
                    '_parent'  => isset($query['_parent']) ? $query['_parent'] : null,
                ]
            ),
        ];
        unset($query['_id'], $query['_ttl'], $query['_parent'], $query['_routing']);

        switch ($operation) {
            case 'index':
            case 'create':
            case 'update':
                $this->bulkQueries[$indexName]['body'][] = $query;
                break;
            case 'delete':
                // Body for delete operation is not needed to apply.
            default:
                // Do nothing.
                break;
        }

        // We are using counter because there is to difficult to resolve this from bulkQueries array.
        $this->bulkCount++;

        $response = null;

        if ($this->bulkCommitSize === $this->bulkCount) {
            $response = $this->commit();
        }

        return $response;
    }

    /**
     * Inserts the current query container to the index, used for bulk queries execution.
     *
     * @param array $params Parameters that will be passed to the flush or refresh queries.
     *
     * @return null|array
     *
     * @throws BulkWithErrorsException
     */
    public function commit(array $params = [])
    {
        if (!empty($this->bulkQueries)) {
            foreach ($this->bulkQueries as $indexName => $bullQueriesByIndex) {
                $bulkQueries = array_merge($bullQueriesByIndex, $this->bulkParams);
                $bulkQueries['index']['_index'] = $indexName;
                $this->eventDispatcher->dispatch(
                    Events::PRE_COMMIT,
                    new CommitEvent($this->getCommitMode(), $bulkQueries)
                );

                $this->stopwatch('start', 'bulk');
                $bulkResponse = $this->client->bulk($bulkQueries);
                $this->stopwatch('stop', 'bulk');

                if ($bulkResponse['errors']) {
                    throw new BulkWithErrorsException(
                        json_encode($bulkResponse),
                        0,
                        null,
                        $bulkResponse
                    );
                }

                $this->stopwatch('start', 'refresh');

                $params['index'] = $indexName;

                switch ($this->getCommitMode()) {
                    case 'flush':
                        $this->flush($params);
                        break;
                    case 'refresh':
                        $this->refresh($params);
                        break;
                }

                $this->eventDispatcher->dispatch(
                    Events::POST_COMMIT,
                    new CommitEvent($this->getCommitMode(), $bulkResponse)
                );

                $this->stopwatch('stop', 'refresh');
            }

            $this->bulkQueries = [];
            $this->bulkCount = 0;
        }

        return null;
    }

    /**
     * @return string
     */
    public function getCommitMode()
    {
        return $this->commitMode;
    }

    /**
     * @param string $commitMode
     */
    public function setCommitMode($commitMode)
    {
        if ($commitMode === 'refresh' || $commitMode === 'flush' || $commitMode === 'none') {
            $this->commitMode = $commitMode;
        } else {
            throw new \LogicException('The commit method must be either refresh, flush or none.');
        }
    }

    /**
     * Starts and stops an event in the stopwatch
     *
     * @param string $action only 'start' and 'stop'
     * @param string $name   name of the event
     */
    private function stopwatch($action, $name)
    {
        if (isset($this->stopwatch)) {
            $this->stopwatch->$action('steerfox_es: '.$name, 'steerfox_es');
        }
    }

    /**
     * Flushes elasticsearch index.
     *
     * @param array $params
     *
     * @return array
     */
    public function flush(array $params = [])
    {
        return $this->client->indices()->flush($params);
    }

    /**
     * Refreshes elasticsearch index.
     *
     * @param array $params
     *
     * @return array
     */
    public function refresh(array $params = [])
    {
        return $this->client->indices()->refresh($params);
    }

    /**
     * Adds document for removal.
     *
     * @param object $document
     */
    public function remove($document)
    {
        $data = $this->converter->convertToArray($document, [], ['_id', '_routing']);

        if (!isset($data['_id'])) {
            throw new \LogicException(
                'In order to use remove() method document class must have property with @Id annotation.'
            );
        }

        $type = $this->getMetadataCollector()->getDocumentType(get_class($document));

        $this->bulk('delete', $type, $data);
    }

    /**
     * Fetches next set of results.
     *
     * @param string $scrollId
     * @param string $scrollDuration
     *
     * @return mixed
     *
     * @throws \Exception
     */
    public function scroll(
        $scrollId,
        $scrollDuration = '5m'
    ) {
        $results = $this->getClient()->scroll(['scroll_id' => $scrollId, 'scroll' => $scrollDuration]);

        return $results;
    }

    /**
     * Executes search query in the index.
     *
     * @param array $types             List of types to search in.
     * @param array $query             Query to execute.
     * @param array $queryStringParams Query parameters.
     *
     * @return array
     */
    public function search(array $types, array $query, array $queryStringParams = [])
    {
        if (count($types) > 1) {
            throw new \Exception('Invalid query on mulitTypes');
        }

        $params = [];
        $params['index'] = $this->getIndexNameByType($types[0]);

        $resolvedTypes = [];
        foreach ($types as $type) {
            $resolvedTypes[] = $this->resolveTypeName($type);
        }

        if (!empty($resolvedTypes)) {
            $params['type'] = implode(',', $resolvedTypes);
        }

        $params['body'] = $query;

        if (!empty($queryStringParams)) {
            $params = array_merge($queryStringParams, $params);
        }

        $this->stopwatch('start', 'search');
        $result = $this->client->search($params);
        $this->stopwatch('stop', 'search');

        return $result;
    }

    /**
     * Optional setter to change bulk query params.
     *
     * @param array $params Possible keys:
     *                      ['consistency'] = (enum) Explicit write consistency setting for the operation.
     *                      ['refresh']     = (boolean) Refresh the index after performing the operation.
     *                      ['replication'] = (enum) Explicitly set the replication type.
     */
    public function setBulkParams(array $params)
    {
        $this->bulkParams = $params;
    }

    /**
     * @param EventDispatcherInterface $eventDispatcher
     */
    public function setEventDispatcher(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * Sets index name for this connection.
     *
     * @param string $name
     */
    public function setIndexName($name)
    {
        $this->indexSettings['index'] = $name;
    }

    /**
     * @param Stopwatch $stopwatch
     */
    public function setStopwatch(Stopwatch $stopwatch)
    {
        $this->stopwatch = $stopwatch;
    }
}
