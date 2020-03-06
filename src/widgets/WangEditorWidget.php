<?php

namespace kriss\wangEditor\widgets;

use kriss\wangEditor\assets\BaseAsset;
use kriss\wangEditor\assets\FullScreenAsset;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\JsExpression;
use yii\widgets\InputWidget;

class WangEditorWidget extends InputWidget
{
    /**
     * 是否显示全屏
     * @link https://github.com/chris-peng/wangEditor-fullscreen-plugin
     * @var bool
     */
    public $canFullScreen = false;
    /**
     * 扩展配置
     * @link https://www.kancloud.cn/wangfupeng/wangeditor3/335776
     * @var array
     */
    public $customConfig = [];
    /**
     * 图片上传路径
     * @var false|array
     */
    public $uploadImageServer = ['/file/wang-editor'];
    /**
     * 编辑器默认高度
     * @var string
     */
    public $height = '300px';
    /**
     * 显示的菜单
     * @var array
     */
    public $menus = [];
    /**
     * 需要隐藏的菜单
     * @var array
     */
    public $hideMenus = [];

    /**
     * @var string
     */
    private $editorId;
    /**
     * @var string
     */
    private $editorObj;
    /**
     * @var string
     */
    private $toolbarId;
    /**
     * @var string
     */
    private $content;
    /**
     * @var string
     */
    private $inputId;

    public function init()
    {
        parent::init();
        $this->editorId = 'editor-' . $this->id;
        $this->toolbarId = 'editor-toolbar-' . $this->id;
        $this->editorObj = $this->id . 'Editor';
        if ($this->uploadImageServer !== false) {
            $this->uploadImageServer = Url::to($this->uploadImageServer);
        }
    }

    public function run()
    {
        $html = [];
        if ($this->hasModel()) {
            $html[] = Html::activeHiddenInput($this->model, $this->attribute, $this->options);
            $this->content = Html::getAttributeValue($this->model, $this->attribute);
            $this->inputId = Html::getInputId($this->model, $this->attribute);
        } else {
            if(empty($this->options['id']))
                $this->options['id'] = $this->name . uniqid();
            $this->inputId = $this->options['id'];
            $html[] = Html::hiddenInput($this->name, $this->value, $this->options);
            $this->content = $this->value;
        }
        $html[] = $this->renderHtml($this->content);

        $this->registerAssets();

        return implode("\n", $html);
    }

    protected function registerAssets()
    {
        $view = $this->getView();
        BaseAsset::register($view);
        if ($this->canFullScreen) {
            FullScreenAsset::register($view);
        }

        $js[] = "var {$this->editorObj} = new window.wangEditor('#{$this->toolbarId}', '#{$this->editorId}');";
        $customConfig = array_merge($this->getDefaultCustomConfig(), $this->customConfig);
        if ($customConfig) {
            $customConfig = Json::htmlEncode($customConfig);
            $js[] = "{$this->editorObj}.customConfig = {$customConfig}";
        }
        $js[] = "{$this->editorObj}.create();";
        $js[] = "{$this->editorObj}.txt.html('{$this->content}');";
        if ($this->canFullScreen) {
            $js[] = "window.wangEditor.fullscreen.init('#{$this->editorId}');";
        }
        $view->registerJs(implode("\n", $js));
    }

    protected function getDefaultCustomConfig()
    {
        $config = [
            'onchange' => new JsExpression("function(html){\$('#{$this->options['id']}').val(html);}"),
            'menus' => [
                'head',  // 标题
                'bold',  // 粗体
                'fontSize',  // 字号
                'fontName',  // 字体
                'italic',  // 斜体
                'underline',  // 下划线
                'strikeThrough',  // 删除线
                'foreColor',  // 文字颜色
                'backColor',  // 背景颜色
                'link',  // 插入链接
                'list',  // 列表
                'justify',  // 对齐方式
                'quote',  // 引用
                //'emoticon',  // 表情
                'image',  // 插入图片
                'table',  // 表格
                'video',  // 插入视频
                'code',  // 插入代码
                'undo',  // 撤销
                'redo'  // 重复
            ],
            //'uploadImgMaxSize' => 5242880, // 5M
            //'uploadImgMaxLength' => 10,
        ];

        if(!empty($this->menus)){
            $config['menus'] = $this->menus;
        }

        if(!empty($this->hideMenus)){
            $config['menus'] = array_values(array_diff($config['menus'], $this->hideMenus));
        }

        if ($this->uploadImageServer) {
            $config['uploadImgServer'] = $this->uploadImageServer;
            $config['uploadFileName'] = 'filename[]';
            $config['uploadImgHooks'] = [
                'fail' => new JsExpression('function (xhr, editor, result) {alert(result.msg);}'),
            ];
        }
        return $config;
    }

    protected function renderHtml($content)
    {
        return Html::tag('div', '', ['id' => $this->toolbarId, 'style' => 'border:1px solid #ced4da;'])
            .Html::tag('div', '', ['id' => $this->editorId, 'style' => 'border:1px solid #ced4da;height:'.$this->height]);
    }
}

