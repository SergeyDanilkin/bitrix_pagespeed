<?php
use Bitrix\Main\EventManager;

EventManager::getInstance()->addEventHandler(
    "main",
    "OnEndBufferContent",
    "OnEndBufferContentPageSpeed"
);

function OnEndBufferContentPageSpeed(&$content){
    $PS = new PageSpeed();
    $PS->improvePageSpeed($content);
}

/**
 * Class PageSpeed
 */
class PageSpeed{
    private $improveCss;
    private $improveJs;
    private $improveHtml;
    private $improveLazyLoad;
    private $improveWebp;
    public function __construct(){
        $this->improveCss = true;
        $this->improveJs = false;
        $this->improveHtml = true;
        $this->improveLazyLoad = true;
        $this->improveWebp = true;
    }
    public function improvePageSpeed(&$content = ''){
        if($this->useImprove()){
            $this->improveCss($content);
            $this->improveJs($content);
            $this->improveHtml($content);
            $this->improveLazyLoad($content);
            $this->improveWebp($content);
        }
    }
    protected function improveCss(&$content = ''){
        if($this->improveCss){
            preg_match_all('#<link(.*?)/>#is', $content, $css, PREG_SET_ORDER);
            if ($css) {
                $textCss = '';
                foreach ($css as $c) {
                    if (strpos($c[1], 'text/css') !== false) {
                        preg_match('/href=["\']?([^"\'>]+)["\']?/', $c[1], $url);
                        if ($url[1] && substr($url[1], 0, 1) == '/' && strpos($url[1],'.ico') === false) {
                            $exp = explode('?', $_SERVER['DOCUMENT_ROOT'] . $url[1]);
                            $text = file_get_contents($exp[0]);
                            if ($text) {
                                $textCss .= $text;
                                $content = str_replace($c[0], '', $content);
                            }
                        }
                    }
                }
                if ($textCss) {
                    $textCss = $this->minifyCss($textCss);
                    $textCss = str_replace('../images/panel/top-panel-sprite-2.png','/bitrix/js/main/core/images/panel/top-panel-sprite-2.png',$textCss);
                }
                $exp = explode('</head>', $content);
                $content = $exp[0] . '<style>' . $textCss . '</style></head>' . $exp[1];
            }
        }
    }
    protected function improveJs(&$content = ''){
        if ($this->improveJs) {
            preg_match_all('#<script(.*?)</script>#is', $content, $js, PREG_SET_ORDER);
            if ($js) {
                $textJs = '';
                foreach ($js as $j) {
                    if (strpos($j[1], 'src="') !== false) {
                        preg_match('/src=["\']?([^"\'>]+)["\']?/', $j[1], $url);
                        if ($url[1] && substr($url[1], 0, 1) == '/') {
                            $exp = explode('?',  $url[1]);
                            if(in_array($exp[0],[
                                '/bitrix/js/main/admin_tools.js',
                                '/bitrix/js/main/public_tools.min.js'
                            ]))
                                continue;
                            $text = file_get_contents($_SERVER['DOCUMENT_ROOT'] .$exp[0]);

                            if ($text) {
                                $textJs .= $text;
                                $textJs.="\n";
                                $content = str_replace($j[0], '', $content);
                            }
                        }
                    }
                    else{
                        preg_match('#<script(.*?)>(.*?)</script>#is', $j[0], $code);

                        if(strpos($code[2] ,'m,e,t,r,i,k,a')!== false) {
                            continue;
                        }

                        if(strpos($code[2] ,'googletagmanager')!== false)
                            continue;

                        if(strpos($code[2] ,"gtag('config'")!== false)
                            continue;

                        if(strpos($code[2] ,"var __cs = __cs || [];")!== false)
                            continue;

                        $textJs .= $code[2];
                        $textJs.="\n";
                        $content = str_replace($j[0], '', $content);
                    }
                }
                $exp = explode('</body>', $content);
                $content = $exp[0] . '<script defer>' . $textJs . '</script></body>' . $exp[1];
            }
        }
    }

    protected function improveHtml(&$content = ''){
        if ($this->improveHtml) {
            $content = str_replace("\t\n","\n", $content);
            $content = preg_replace('~>\s*\n\s*<~', '><', $content);
        }
    }

    protected function improveLazyLoad(&$content = ''){
        if ($this->improveLazyLoad) {
            $content = str_replace("<img ", "<img loading=\"lazy\" ", $content);
        }
    }

    protected function improveWebp(&$content = ''){
        if ($this->improveLazyLoad) {
            if ((strpos( $_SERVER['HTTP_USER_AGENT'], 'Safari') === false || strpos( $_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false) && strpos($_SERVER['HTTP_USER_AGENT'], 'Trident') === false && function_exists('imagewebp')){
                preg_match_all('/<img[^>]+>/i',$content, $img);
                if($img[0]){
                    foreach($img[0] as $i => $v){
                        preg_match_all('/src="([^"]+)/i',$v, $attr);
                        if($attr[1][0] && strpos($attr[1][0],'.webp') === false){
                            $path = str_replace(['.png','.jpeg','.jpg'],'.webp',$attr[1][0]);
                            if(!file_exists($_SERVER['DOCUMENT_ROOT'].$path)){
                                if (strpos($attr[1][0], '.png')) {
                                    /*$newImg = imagecreatefrompng($_SERVER['DOCUMENT_ROOT'] . $attr[1][0]);
                                    imagealphablending($newImg, false);
                                    imagesavealpha($newImg, true);
                                    $newImgPath = str_replace('.png', '.webp', $attr[1][0]);*/
                                } elseif (strpos($attr[1][0], '.jpg') !== false || strpos($attr[1][0], '.jpeg') !== false) {
/*
                                    $newImg = imagecreatefromjpeg($_SERVER['DOCUMENT_ROOT'] . $attr[1][0]);
                                    $newImgPath = str_replace(array('.jpg', '.jpeg'), '.webp', $attr[1][0]);*/
                                }
                                if ($newImg) {
                                    if (!file_exists($_SERVER['DOCUMENT_ROOT'] . $newImgPath)) {
                                        imagewebp($newImg, $_SERVER['DOCUMENT_ROOT'] . $newImgPath, 90);
                                    }
                                    imagedestroy($newImg);
                                }

                            }
                            else{
                                $content = str_replace($attr[1][0],$path, $content);
                            }
                        }
                    }
                }
            }
        }
    }

    protected function minifyCss($css = ''){
        $css = trim($css);
        $css = str_replace("\r\n", "\n", $css);
        $search = array("/\/\*[^!][\d\D]*?\*\/|\t+/","/\s+/", "/\}\s+/");
        $replace = array(null," ", "}\n");
        $css = preg_replace($search, $replace, $css);
        $search = array("/;[\s+]/","/[\s+];/","/\s+\{\\s+/", "/\\:\s+\\#/", "/,\s+/i", "/\\:\s+\\\'/i","/\\:\s+([0-9]+|[A-F]+)/i","/\{\\s+/","/;}/");
        $replace = array(";",";","{", ":#", ",", ":\'", ":$1","{","}");
        $css = preg_replace($search, $replace, $css);
        $css = str_replace("\n", null, $css);
        return $css;
    }
    protected function useImprove(){
        global $USER;
        $request = \Bitrix\Main\Context::getCurrent()->getRequest();
        return (!$request->isAdminSection() && !$request->isAjaxRequest() /*&& !$USER->isAdmin()*/);
    }
    public function __get($name) {
        return $this->$name;
    }
    public function __set($name, $value) {
        $this->$name = $value;
    }
}
?>