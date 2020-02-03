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

namespace Z\HyperfThinkphp;

use Hyperf\HttpServer\Request as BaseRquest;
use Z\HyperfThinkphp\Concerns\RequestUtils;

class Request extends BaseRquest
{
    use RequestUtils;
}
