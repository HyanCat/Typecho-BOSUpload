<?php

/**
 * <a href="http://bce.baidu.com" target="_blank">Baidu Object Storage</a> 附件上传插件
 *
 * @package BOSUpload
 * @author  HyanCat
 * @version 0.0.1
 * @link    http://hyancat.com
 * @date    2015-05-22
 */
class BOSUpload_Plugin implements Typecho_Plugin_Interface
{
	/**
	 * 激活插件方法,如果激活失败,直接抛出异常
	 *
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function activate()
	{
		Typecho_Plugin::factory('Widget_Upload')->uploadHandle         = ['BOSUpload_Plugin', 'uploadHandle'];
		Typecho_Plugin::factory('Widget_Upload')->modifyHandle         = ['BOSUpload_Plugin', 'modifyHandle'];
		Typecho_Plugin::factory('Widget_Upload')->deleteHandle         = ['BOSUpload_Plugin', 'deleteHandle'];
		Typecho_Plugin::factory('Widget_Upload')->attachmentHandle     = ['BOSUpload_Plugin', 'attachmentHandle'];
		Typecho_Plugin::factory('Widget_Upload')->attachmentDataHandle = ['BOSUpload_Plugin', 'attachmentDataHandle'];
	}

	/**
	 * 禁用插件方法,如果禁用失败,直接抛出异常
	 *
	 * @static
	 * @access public
	 * @return void
	 * @throws Typecho_Plugin_Exception
	 */
	public static function deactivate()
	{
	}

	/**
	 * 获取插件配置面板
	 *
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form 配置面板
	 * @return void
	 */
	public static function config(Typecho_Widget_Helper_Form $form)
	{
		$ak = new Typecho_Widget_Helper_Form_Element_Text('ak', null, '', _t('Access Key'), _t('<a href="http://console.bce.baidu.com/iam/#/iam/accesslist" target="_blank">获取Access Key</a>'));
		$form->addInput($ak);

		$sk = new Typecho_Widget_Helper_Form_Element_Text('sk', null, '', _t('Secure Key'), _t('<a href="http://console.bce.baidu.com/iam/#/iam/accesslist" target="_blank">获取Secure Key</a>'));
		$form->addInput($sk);

		$bucketName = new Typecho_Widget_Helper_Form_Element_Text('bucket', null, 'bucketName', _t('Bucket名称'), _t(''));
		$form->addInput($bucketName);
	}

	/**
	 * 个人用户的配置面板
	 *
	 * @access public
	 * @param Typecho_Widget_Helper_Form $form
	 * @return void
	 */
	public static function personalConfig(Typecho_Widget_Helper_Form $form)
	{
	}

	/**
	 * 上传文件处理函数
	 *
	 * @access public
	 * @param array $file 上传的文件
	 * @return mixed
	 */
	public static function uploadHandle($file)
	{
		if (empty($file['name'])) {
			return false;
		}
		$fileName     = preg_split("(\/|\\|:)", $file['name']);
		$file['name'] = array_pop($fileName);

		//获取扩展名
		$ext  = '';
		$part = explode('.', $file['name']);
		if (($length = count($part)) > 1) {
			$ext = strtolower($part[$length - 1]);
		}

		if (! Widget_Upload::checkFileType($ext)) {
			return false;
		}

		$options = Typecho_Widget::widget('Widget_Options');
		$date    = new Typecho_Date($options->gmtTime);

		// 构建路径
		$path = Widget_Upload::UPLOAD_DIR . '/' . $date->year . '/' . $date->month;

		// 获取文件名
		$fileName = sprintf('%u', crc32(uniqid())) . '.' . $ext;
		$path     = $path . '/' . $fileName;

		// 上传云存储
		$bos = self::bosInit();

		if (isset($file['tmp_name'])) {
			$bos->uploadFile($file['tmp_name'], $path);
		}
		else if (isset($file['bits'])) {
			$bos->uploadFileWithData($file['bits'], $path);
		}
		else {
			return false;
		}

		$meta = null;
		if (! isset($file['size'])) {
			$meta         = $meta ?: $bos->getObjectMeta($path);
			$file['size'] = $meta->contentLength;
		}
		if (! isset($file['type'])) {
			$meta         = $meta ?: $bos->getObjectMeta($path);
			$file['type'] = $meta->contentType;
		}

		// 返回相对存储路径
		return [
			'name' => $file['name'],
			'path' => $path,
			'size' => $file['size'],
			'type' => $ext,
			'mime' => $file['type'],
		];
	}

	/**
	 * 修改文件处理函数
	 *
	 * @access public
	 * @param array $content 老文件
	 * @param array $file    新上传的文件
	 * @return mixed
	 */
	public static function modifyHandle($content, $file)
	{
		if (empty($file['name'])) {
			return false;
		}

		$fileName     = preg_split("(\/|\\|:)", $file['name']);
		$file['name'] = array_pop($fileName);

		// 获取扩展名
		$ext  = '';
		$part = explode('.', $file['name']);
		if (($length = count($part)) > 1) {
			$ext = strtolower($part[$length - 1]);
		}

		if ($content['attachment']->type != $ext) {
			return false;
		}

		// 获取文件名
		$fileName = $content['attachment']->path;
		$path     = $fileName;

		// 上传云存储
		$bos = self::bosInit();

		if (isset($file['tmp_name'])) {
			$bos->uploadFile($file['tmp_name'], $path);
		}
		else if (isset($file['bits'])) {
			$bos->uploadFileWithData($file['bits'], $path);
		}
		else {
			return false;
		}

		$meta = null;
		if (! isset($file['size'])) {
			$meta         = $meta ?: $bos->getObjectMeta($path);
			$file['size'] = $meta->contentLength;
		}

		// 返回相对存储路径
		return [
			'name' => $content['attachment']->name,
			'path' => $content['attachment']->path,
			'size' => $file['size'],
			'type' => $content['attachment']->type,
			'mime' => $content['attachment']->mime
		];
	}

	/**
	 * 删除文件
	 *
	 * @access public
	 * @param array $content 文件相关信息
	 * @return string
	 */
	public static function deleteHandle(array $content)
	{
		$bos = self::bosInit();

		$bos->removeFile($content['attachment']->path);

		return true;
	}

	/**
	 * 获取实际文件绝对访问路径
	 *
	 * @access public
	 * @param array $content 文件相关信息
	 * @return string
	 */
	public static function attachmentHandle(array $content)
	{
		$bos = self::bosInit();

		return $bos->getObjectUrl($content['attachment']->path);
	}

	/**
	 * 获取实际文件数据
	 *
	 * @access public
	 * @param array $content
	 * @return string
	 */
	public static function attachmentDataHandle(array $content)
	{
		$bos = self::bosInit();

		return $bos->getObject($content['attachment']->path);
	}

	/**
	 * BOS 初始化
	 *
	 * @return object
	 */
	protected static function bosInit()
	{
		$options = Typecho_Widget::widget('Widget_Options')->plugin('BOSUpload');

		require_once 'BosService.php';

		return new BosService($options->ak, $options->sk, $options->bucket);
	}

}
