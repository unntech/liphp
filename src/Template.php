<?php
declare (strict_types = 1);

namespace LiPhp;

class Template {
    protected static string $templatePath, $cachePath, $template='default';
    protected static int $chmod = 0755;
    protected static bool $template_refresh = true;
    
    /*
     * 模板类初始化数据，refresh 为false时，不会自动刷新修改的模版文件
     */
    public static function init(string $templatePath, string $template, string $cachePath, bool $refresh = true): void
    {
        self::$templatePath = $templatePath;
        self::$template = $template;
        self::$cachePath = $cachePath;
        self::$template_refresh = $refresh;
	}

    /**
     * 生成模板文件并返回
     * @param string|null $template null时则为PHP脚本相同路径的htm模版文件，示例值： tests/index
     * @param string|null $dir 模版路径 兼容过往写法，此参数将移除
     * @return string|void
     */
    public static function load(?string $template = null, ?string $dir = null)
    {
        if(empty(self::$templatePath) || empty(self::$cachePath)){
            self::setDefaultPath();
        }
        if(is_null($template)){
            $_php = $_SERVER['SCRIPT_NAME'];
            $to = self::$cachePath.'/tpl/'.self::$template. $_php;
        }else{
            self::check_name($template) or exit('BAD TPL NAME');
            if(!empty($dir)){
                self::check_name($dir) or exit('BAD TPL DIR');
            }
            $to = self::$cachePath.'/tpl/'.self::$template.'/'.(empty($dir) ? '' : $dir.'/' ). $template.'.php';
        }
        $isFileTo = is_file($to);
        if(!$isFileTo || self::$template_refresh) {
            if(is_null($template)){
                $_htm = preg_replace('/\.php$/', '.htm', $_php);
                $from = self::$templatePath . '/' . self::$template . $_htm;
            }else{
                $from = self::$templatePath . '/' . self::$template.'/'.(empty($dir) ? '' : $dir.'/' ) . $template . '.htm';
            }
            if(self::$template != 'default' && !is_file($from)) {
                $from = self::$templatePath . '/default/' . $dir . $template . '.htm';
            }
            if(!$isFileTo || filemtime($from) > filemtime($to) || (filesize($to) == 0 && filesize($from) > 0)) {
                self::template_compile($from, $to);
            }
        }
        
        return $to;
        
    }

    /**
     * 使用消息模板输出提示
     * @param string $promptMessage
     * @param string $msgTitle
     * @return void
     */
    public static function message(string $promptMessage='', string $msgTitle=''): void
    {
        $msgTitle = empty($msgTitle) ? '信息提示' : $msgTitle;
        $promptMessage = empty($promptMessage) ? date('Y-m-d H:i:s') : $promptMessage;
        include self::load('message');
        exit(0);
    }
    
    protected static function check_name($name): bool|int
    {
        if(str_contains($name, '__') || str_contains($name, '--')) return false;
        return preg_match("/^[a-z0-9]{1}[a-z0-9_\-\/]{0,}[a-z0-9]{1}$/", $name);
    }
    
    protected static function template_compile($from, $to): void
    {
        $content = self::template_parse(self::file_get($from));
        self::file_put($to, $content);
    }

    protected static function template_parse(string $str): string
    {
        $str = preg_replace("/\<\!\-\-\[(.+?)\]\-\-\>/", "", $str);
        $str = preg_replace("/\<\!\-\-\{(.+?)\}\-\-\>/s", "{\\1}", $str);
        $str = preg_replace("/\{template\s+([^\}]+)\}/", "<?php include \\LiPhp\\Template::load(\\1);?>", $str);
        $str = preg_replace("/\{php\s+(.+)\}/", "<?php \\1?>", $str);
        $str = preg_replace("/\{if\s+(.+?)\}/", "<?php if(\\1) { ?>", $str);
        $str = preg_replace("/\{else\}/", "<?php } else { ?>", $str);
        $str = preg_replace("/\{elseif\s+(.+?)\}/", "<?php } else if(\\1) { ?>", $str);
        $str = preg_replace("/\{\/if\}/", "<?php } ?>\r\n", $str);
        $str = preg_replace("/\{loop\s+(\S+)\s+(\S+)\}/", "<?php if(is_array(\\1)) { foreach(\\1 as \\2) { ?>", $str);
        $str = preg_replace("/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/", "<?php if(is_array(\\1)) { foreach(\\1 as \\2 => \\3) { ?>", $str);
        $str = preg_replace("/\{\/loop\}/", "<?php } } ?>", $str);
        $str = preg_replace("/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*\(([^{}]*)\))\}/", "<?php echo \\1;?>", $str);
        $str = preg_replace_callback("/<\?php([^\?]+)\?>/s", function($matchs) {
                return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $matchs[0]));
                }, $str);
        $str = preg_replace("/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_>\+\-\x7f-\xff]*)\}/", "<?php echo \\1;?>", $str);  // 规则添加[>](大于号)，支持对象变量直接输出
        $str = preg_replace_callback("/\{(\\$[a-zA-Z0-9_\[\]\'\"\$\+\-\x7f-\xff]+)\}/s", function($matchs) {
                return '<?php echo '.str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $matchs[1])).';?>';
                }, $str);
        $str = preg_replace("/\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\}/s", "<?php echo \\1;?>", $str);
        $str = preg_replace("/\'([A-Za-z]+)\[\'([A-Za-z\.]+)\'\](.?)\'/s", "'\\1[\\2]\\3'", $str);
        $str = preg_replace("/(\r?\n)\\1+/", "\\1", $str);
        $str = str_replace("\t", '', $str);
        $str = "<?php defined('IN_LitePhp') or exit('Access Denied');?>\n".$str;
        return $str;
        
    }

    protected static function template_addquote1($matchs)
    {
        return str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $matchs[0]));
    }

    protected static function template_addquote2($matchs)
    {
        return '<?php echo '.str_replace("\\\"", "\"", preg_replace("/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", $matchs[1])).';?>';
    }
    
    protected static function file_put($filename, $data)
    {
        self::dir_create(dirname($filename));	
        if($fp = @fopen($filename, 'wb')) {
            flock($fp, LOCK_EX);
            $len = fwrite($fp, $data);
            flock($fp, LOCK_UN);
            fclose($fp);
            if(self::$chmod) @chmod($filename, self::$chmod);
            return $len;
        } else {
            return false;
        }
    }

    protected static function file_get($filename)
    {
        $str =  @file_get_contents($filename);
        return $str === false ? '' : $str;
    }
    
    protected static function dir_create($path): bool
    {
        if (!is_dir($path)) {
            return mkdir($path, self::$chmod, true); // 第三个参数true表示递归创建
        }
        return true;
    }
    
    /**
     * 初始化默认目录数据
     * @access protected
     * @return void
     */
    protected static function setDefaultPath(): void
    {
        $dt_root = dirname(__DIR__ , 4);
        self::$templatePath = $dt_root . '/template';
        self::$cachePath = $dt_root."/runtime/cache";
    }

}