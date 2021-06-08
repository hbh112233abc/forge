<?php
namespace bingher\forge\demo\controller;

use bingher\forge\AutoDeskForge;

class Index
{
    public function __construct()
    {
        $this->forge = new AutoDeskForge(config('forge'));
    }

    /**
     * FORGE首页
     * http://www.efileyun.cn.cc/forge/index/index
     *
     * @return void
     */
    public function index()
    {
        return view();
    }

    /**
     * 展示模型
     * http://www.efileyun.cn.cc/forge/index/view?urn=dXJuOmFkc2sub2JqZWN0czpvcy5vYmplY3Q6eTZjZWV3d2NpY3YybWd2Z2lldTRhdnI3YmZ5aWg1a2hfdGVzdC9DQUQlRTglOUUlQkElRTQlQjglOUQlRTUlOUIlQkUlRTUlOUUlOEIlRTUlQkElOTMuZHdn
     *
     * @return void
     */
    public function view()
    {
        $urn = input('urn');
        if (empty($urn)) {
            return 'urn empty';
        }

        return view('', ['urn' => $urn]);
    }

    /**
     * 响应操作
     *
     * @param array $result 响应内容
     *
     * @return \think\Response
     */
    public function response($result)
    {
        if ($result === false) {
            return $this->forge->getError();
        }
        return json($result);
    }

    /**
     * 获取token
     *
     * @return array
     */
    public function token()
    {
        $res = $this->forge->getTokenPublic();
        return $this->response($res);
    }

    /**
     * 获取桶列表
     *
     * @return void
     */
    public function buckets()
    {
        $id = input('id');
        if ($id === '#') {
            $res = $this->forge->getBuckets();
        } else {
            $res = $this->forge->getObjects($id);
        }
        return $this->response($res);
    }

    /**
     * 创建桶
     *
     * @return void
     */
    public function createBucket()
    {
        $bucketKey = input('bucketKey');
        $res       = $this->forge->createBucket($bucketKey);
        return $this->response($res);
    }

    /**
     * 上传文件
     *
     * @return void
     */
    public function upload()
    {
        $file      = request()->file('fileToUpload');
        $filepath  = $file->getPath();
        $filename  = $file->getFilename();
        $bucketKey = input('bucketKey');
        $res       = $this->forge->upload($filepath, $filename, $bucketKey);
        return $this->response($res);
    }

    /**
     * 文件转换
     *
     * @return void
     */
    public function translate()
    {
        $objectId = input('objectName');
        $res      = $this->forge->translate($objectId);
        return $this->response($res);
    }

}
