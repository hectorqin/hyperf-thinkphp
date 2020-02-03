<?php

declare (strict_types = 1);
/**
 * This file is part of Hyperf.
 *
 * @link     https://www.hyperf.io
 * @document https://doc.hyperf.io
 * @contact  group@hyperf.io
 * @license  https://github.com/hyperf-cloud/hyperf/blob/master/LICENSE
 */

namespace Z\HyperfThinkphp\Model;

use Hyperf\DbConnection\Model\Model as BaseModel;
use DateTimeInterface;

/**
 *
 * 优先级: dates 属性 + serializeDate > getters > casts 属性
 * 如果casts里面有dates中的属性，serializeDate 尽量不要格式化成不能恢复成Carbon对象的格式，如 Y-m-d H:i:s 丢失了时区的格式
 *
 * asDateTime 支持 Carbon / CarbonInterface / DateTimeInterface / numeric / Y-m-d 带时区日期 Y-m-d H:i:s.u  时间戳 U
 *
 * @method static withSearch(array $fields, array $data = [], $prefix = '') 搜索器
 * @method static order($field, $order = null) 排序
 * @method static page($page, $listRows = null) 设置分页参数
 * @package Z\HyperfThinkphp\Model
 */
class Model extends BaseModel
{

}
