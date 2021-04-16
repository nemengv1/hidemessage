<?php
namespace hidemessage;

/**
 * 图片隐藏内容
 * Class ImgHide
 * @package hidemessage
 */
class ImgHide
{

    /**
     * 写入内容
     * @param string $img_path 图片位置
     * @param string $new_img_path 新图片位置
     * @param string $content 写入内容
     * @param boolean $ole_delete 是否删除原图
     * @throws \Exception
     */
    public static function writeMessage($img_path, $new_img_path, $content, $ole_delete=false)
    {
        try{
            // 把内容长度和内容统统转换为二进制
            $content_bin    = str_pad(base_convert(mb_strlen($content, 'utf-8') * 24, 10, 2), 30, '0', STR_PAD_LEFT) . self::StrToBin($content);
            // 二进制字符串长度
            $strlen         = strlen($content_bin);
            // 所需保存数据的像素个数
            $ratlen         = $strlen / 3;
            // 图片尺寸
            $imagesize      = getimagesize($img_path);
            // 判断内容大小
            if( ( $imagesize[0] * $imagesize[1] ) < ( $ratlen + 10 ) ) {
                throw new \Exception('内容太大，超出隐写范围');
            }
            // 只允许png格式的文件
            $ext     = pathinfo($img_path, PATHINFO_EXTENSION);
            if($ext != 'png') {
                throw new \Exception('只支持png格式');
            }
            // 创建画布
            $image          = imagecreatefrompng($img_path);
            // 像素索引
            $ratlen_index   = 0;
            // 循环宽度像素
            for($x = 0; $x < ( $imagesize[0] - 1 ); $x++) {
                // 循环高度像素
                for($y = 0; $y < ( $imagesize[1] - 1 ); $y++) {
                    if($ratlen_index < $ratlen) {
                        // 只循环需要写入的像素
                        // 重新编写每个像素的值，红绿蓝每个颜色都用二进制的最后一个位存数据
                        $rat        = imagecolorat($image, $x, $y);
                        $rat        = imagecolorsforindex($image, $rat);
                        $r          = base_convert($rat['red'], 10, 2);
                        $r          = base_convert(substr($r, 0, -1) . $content_bin[$ratlen_index * 3], 2, 10);
                        $g          = base_convert($rat['green'], 10, 2);
                        $g          = base_convert(substr($g, 0, -1) . $content_bin[$ratlen_index * 3 + 1], 2, 10);
                        $b          = base_convert($rat['blue'], 10, 2);
                        $b          = base_convert(substr($b, 0, -1) . $content_bin[$ratlen_index * 3 + 2], 2, 10);
                        $new_color  = imagecolorallocate($image, $r, $g, $b);
                        imagesetpixel($image, $x, $y, $new_color);
                        $ratlen_index++;
                    } else {
                        break 2;
                    }
                }
            }
            // 生成图片并写到文件里
            imagepng($image, $new_img_path);
            // 销毁
            imagedestroy($image);
            // 删除旧文件
            if($ole_delete) {
                @unlink($img_path);
            }
        }catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 读取内容
     * @param string $img_path 图片位置
     * @return string 内容
     * @throws \Exception
     */
    public static function readMessage($img_path)
    {
        try{
            // 图片尺寸
            $imagesize  = getimagesize($img_path);
            $ext        = pathinfo($img_path, PATHINFO_EXTENSION);
            // 只允许png格式的文件
            if($ext != 'png') {
                throw new \Exception('只支持png格式');
            }
            // 创建画布
            $image = imagecreatefrompng($img_path);
            // 初始化
            $content_len    = '';
            $content        = '';
            $ratlen_index   = 0;

            for($x = 0; $x < ($imagesize[0] - 1); $x++) {
                for($y = 0; $y < ($imagesize[1] - 1); $y++) {
                    if($ratlen_index < 10) {
                        // 读取写入的内容的长度
                        $rat    = imagecolorat($image, $x, $y);
                        $rat    = imagecolorsforindex($image, $rat);
                        $r      = base_convert($rat['red'], 10, 2);
                        $r      = substr($r, -1, 1);
                        $content_len .= $r;
                        $g      = base_convert($rat['green'], 10, 2);
                        $g      = substr($g, -1, 1);
                        $content_len .= $g;
                        $b      = base_convert($rat['blue'], 10, 2);
                        $b      = substr($b, -1, 1);
                        $content_len .= $b;
                        $ratlen_index++;
                        if($ratlen_index == 10) {
                            // 设置文本内容的长度
                            $content_len = base_convert($content_len,2,10)/3;
                        }
                    } elseif ($ratlen_index < ($content_len + 10)) {
                        // 读取文本内容
                        $rat    = imagecolorat($image, $x, $y);
                        $rat    = imagecolorsforindex($image, $rat);
                        $r      = base_convert($rat['red'], 10, 2);
                        $r      = substr($r, -1, 1);
                        $content .= $r;
                        $g      = base_convert($rat['green'], 10, 2);
                        $g      = substr($g, -1, 1);
                        $content .= $g;
                        $b      = base_convert($rat['blue'], 10, 2);
                        $b      = substr($b, -1, 1);
                        $content .= $b;
                        $ratlen_index++;
                    } else {
                        break 2;
                    }
                }
            }
            $content = str_split($content, 24);
            foreach($content as &$val) {
                $val = self::binToStr($val);
            }
            return implode('', $content);
        }catch (\Exception $e) {
            throw new \Exception($e->getMessage());
        }
    }

    /**
     * 将字符串转换成二进制
     * @param type $str 字符串
     * @return type
     */
    public static function StrToBin($str)
    {
        //1.列出每个字符
        $arr = preg_split('/(?<!^)(?!$)/u', $str);
        //2.unpack字符
        foreach($arr as &$v) {
            $temp   = unpack('H*', $v);
            $v      = base_convert($temp[1], 16, 2);
            unset($temp);
            $v      = str_pad($v, 24, "0", STR_PAD_LEFT);
        }
        $str = implode('', $arr);
        return $str;
    }

    /**
     * 二进制转字符串
     * @param $binary 二进制
     * @return false|string
     */
    public static function binToStr($binary)
    {
        return pack('H*', base_convert($binary, 2, 16));
    }
}