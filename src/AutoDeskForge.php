<?php
namespace bingher\forge;

use Autodesk\Auth\Configuration;
use Autodesk\Auth\OAuth2\TwoLeggedAuth;
use Autodesk\Forge\Client\Api\BucketsApi;
use Autodesk\Forge\Client\Api\DerivativesApi;
use Autodesk\Forge\Client\Api\ObjectsApi;
use Autodesk\Forge\Client\Model\JobPayload;
use Autodesk\Forge\Client\Model\JobPayloadInput;
use Autodesk\Forge\Client\Model\JobPayloadItem;
use Autodesk\Forge\Client\Model\JobPayloadOutput;
use Autodesk\Forge\Client\Model\PostBucketsPayload;

class AutoDeskForge
{
    protected $config = [
        'id'                => '',
        'secret'            => '',
        'prepend_bucketkey' => true,
        'scope_internal'    => [
            'bucket:create',
            'bucket:read',
            'data:read',
            'data:create',
            'data:write',
        ],
        'scope_public'      => ['data:read'],
        'bucket'            => 'default',
    ];

    private $_twoLeggedAuthInternal = null;
    private $_twoLeggedAuthPublic   = null;
    protected $error                = '';

    /**
     * 构造函数
     *
     * @param array $config 配置信息
     */
    public function __construct($config = [])
    {
        $this->config = array_merge($this->config, $config);

        Configuration::getDefaultConfiguration()
            ->setClientId($this->config['id'])
            ->setClientSecret($this->config['secret']);

        // $this->getTokenPublic();
        // $this->getTokenInternal();
    }

    /**
     * 获取错误信息
     *
     * @return string
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * 获取前端用的外部token
     *
     * @return array
     */
    public function getTokenPublic()
    {
        if (!cache('?AccessTokenPublic') || cache('AccessTokenPublicExpiresTime') < time()) {
            $this->_twoLeggedAuthPublic = new TwoLeggedAuth();
            $this->_twoLeggedAuthPublic->setScopes($this->config['scope_public']);
            $this->_twoLeggedAuthPublic->fetchToken();
            cache(
                'AccessTokenPublic',
                $this->_twoLeggedAuthPublic->getAccessToken()
            );
            cache(
                'ExpiresInPublic',
                $this->_twoLeggedAuthPublic->getExpiresIn()
            );
            cache(
                'AccessTokenPublicExpiresTime',
                time() + cache('ExpiresInPublic')
            );
        }
        return [
            'access_token' => cache('AccessTokenPublic'),
            'expires_in'   => cache('ExpiresInPublic'),
        ];
    }

    /**
     * 获取后端用的内部token
     *
     * @return TwoLeggedAuth
     */
    public function getTokenInternal()
    {
        $this->_twoLeggedAuthInternal = new TwoLeggedAuth();
        $this->_twoLeggedAuthInternal->setScopes($this->config['scope_internal']);

        if (!cache('?AccessTokenInternal') || cache('AccessTokenInternalExpiresTime') < time()) {
            $this->_twoLeggedAuthInternal->fetchToken();
            cache(
                'AccessTokenInternal',
                $this->_twoLeggedAuthInternal->getAccessToken()
            );
            cache(
                'ExpiresInInternal',
                $this->_twoLeggedAuthInternal->getExpiresIn()
            );
            cache(
                'AccessTokenInternalExpiresTime',
                time() + cache('ExpiresInInternal')
            );
        }

        $this->_twoLeggedAuthInternal->setAccessToken(cache('AccessTokenInternal'));
        return $this->_twoLeggedAuthInternal;
    }

    /**
     * 桶id设置,判断是否加id前缀
     *
     * @param string $bucketKey 桶id
     *
     * @return string
     */
    public function bucketKeyFix($bucketKey = '')
    {
        if (empty($bucketKey)) {
            $bucketKey = $this->config['bucketKey'];
        }
        if ($this->config['prepend_bucketkey']) {
            if (strpos($bucketKey, $this->config['id']) === 0) {
                return $bucketKey;
            }
            $bucketKey = strtolower($this->config['id']) . '_' . $bucketKey;
        }
        return $bucketKey;
    }

    /**
     * 创建桶
     *
     * @param string $bucketKey 桶名称
     *
     * @return void
     */
    public function createBucket(string $bucketKey)
    {
        if (empty($bucketKey)) {
            $this->error = 'Invalid bucket key';
            return false;
        }
        $bucketKey = $this->bucketKeyFix($bucketKey);

        // $policeKey = $body['policyKey'];
        $policeKey   = "transient";
        $apiInstance = new BucketsApi($this->getTokenInternal());
        $postBucket  = new PostBucketsPayload();
        $postBucket->setBucketKey($bucketKey);
        $postBucket->setPolicyKey($policeKey);

        try {
            $result = $apiInstance->createBucket($postBucket);
            return $result;
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            return false;
        }
    }

    /**
     * 获取桶列表
     *
     * @return array
     */
    public function getBuckets()
    {
        try {
            $apiInstance = new BucketsApi($this->getTokenInternal());
            $result      = $apiInstance->getBuckets();
            $resultArray = json_decode($result, true);
            $buckets     = $resultArray['items'];
            $bucketList  = [];
            foreach ($buckets as $bucket) {
                $key      = $bucket['bucketKey'];
                $exploded = explode('_', $key);
                $text     = $key;
                if ($this->config['prepend_bucketkey']) {
                    if (strpos($key, strtolower($this->config['id'])) === 0) {
                        $text = end($exploded);
                    }
                }
                $bucketList[] = [
                    'id'       => $key,
                    'text'     => $text,
                    'type'     => 'bucket',
                    'children' => true,
                ];
            }
            return $bucketList;
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            return false;
        }
    }

    /**
     * 获取存储对象列表
     *
     * @param string $bucketKey 桶id
     *
     * @return void
     */
    public function getObjects($bucketKey)
    {
        try {
            $apiInstance = new ObjectsApi($this->getTokenInternal());
            $result      = $apiInstance->getObjects($bucketKey);
            $resultArray = json_decode($result, true);
            $objects     = $resultArray['items'];
            $objectList  = [];
            foreach ($objects as $object) {
                $objectList[] = [
                    'id'       => base64_encode($object['objectId']),
                    'text'     => $object['objectKey'],
                    'type'     => 'object',
                    'children' => false,
                ];
            }
            return $objectList;
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            return false;
        }
    }

    /**
     * 上传文件
     *
     * @param string $filepath  文件路径
     * @param string $filename  文件名
     * @param string $bucketKey 桶id
     *
     * @return bool
     */
    public function uploadFile($filepath, $filename = '', $bucketKey = '')
    {
        if (!is_file($filepath)) {
            $this->error = 'File does not exist';
            return false;
        }
        if (empty($filename)) {
            $filename = pathinfo($filepath, PATHINFO_BASENAME);
        }
        if (empty($bucketKey)) {
            $bucketKey = $this->bucketKeyFix($bucketKey);
        }

        $contentLength = filesize($filepath);
        $fileContent   = file_get_contents($filepath);

        try {
            $apiInstance = new ObjectsApi($this->getTokenInternal());
            $result      = $apiInstance->uploadObject(
                $bucketKey,
                $filename,
                $contentLength,
                $fileContent
            );
            return $result;
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            return false;
        }
    }

    /**
     * 文件转换成svf
     *
     * @param string $objectId 文件id
     *
     * @return void
     */
    public function translateFile($objectId)
    {
        $apiInstance = new DerivativesApi($this->getTokenInternal());
        $job         = new JobPayload();

        $jobInput = new JobPayloadInput();
        $jobInput->setUrn($objectId);

        $jobOutputItem = new JobPayloadItem();
        $jobOutputItem->setType('svf');
        $jobOutputItem->setViews(['2d', '3d']);

        $jobOutput = new JobPayloadOutput();
        $jobOutput->setFormats(array($jobOutputItem));

        $job->setInput($jobInput);
        $job->setOutput($jobOutput);

        $x_ads_force = false;
        try {
            $result = $apiInstance->translate($job, $x_ads_force);
            return $result;
        } catch (\Throwable $th) {
            $this->error = $th->getMessage();
            return false;
        }
    }
}
