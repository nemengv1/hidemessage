# hidemessage
###使用方法：
use hidemessage\ImgHide;
把文本写入到图片：
```
/**
 * 写入内容
 * @param string $img_path 图片位置
 * @param string $new_img_path 新图片位置
 * @param string $content 写入内容
 * @param boolean $ole_delete 是否删除原图
 * @throws \Exception
 */
ImgHide::writeMessage($img_path, $new_img_path, $content, $ole_delete=false);
```


读取图片的文本内容：
```
/**
 * 读取内容
 * @param string $img_path 图片位置
 * @return string 内容
 * @throws \Exception
 */
ImgHide::readMessage($img_path);
```