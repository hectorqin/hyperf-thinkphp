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

use Hyperf\HttpServer\Contract\RequestInterface;
use Hyperf\HttpServer\Contract\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Hyperf\Database\Model\Builder;
use Hyperf\Utils\Str;

class ConfigProvider
{
    public function __invoke(): array
    {
        // 添加 withSearch 方法
        Builder::macro('withSearch', function (array $fields, array $data = [], $prefix = '', $model = null) {
            /** @var Builder $this */
            $model = $model ? (is_string($model) ? new $model() : $model) : $this->getModel();
            $searchers = [];
            if (property_exists($model, 'searchers')) {
                $searchers = get_class($model)::$searchers;
            }
            foreach ($fields as $key => $field) {
                if ($field instanceof \Closure) {
                    $field($this, isset($data[$key]) ? $data[$key] : null, $data, $prefix);
                } elseif ($model) {
                    // 检测搜索器
                    $fieldName = Str::studly(is_numeric($key) ? $field : $key);
                    $method    = 'search' . $fieldName . 'Attribute';
                    $tpMethod  = 'search' . $fieldName . 'Attr';
                    if (method_exists($model, $method)) {
                        $model->$method($this, isset($data[$field]) ? $data[$field] : null, $data, $prefix);
                    } else if (method_exists($model, $tpMethod)) {
                        $model->$tpMethod($this, isset($data[$field]) ? $data[$field] : null, $data, $prefix);
                    } else if (isset($searchers[$fieldName]) && \is_callable($searchers[$fieldName])) {
                        \call_user_func_array($searchers[$fieldName], [$this, isset($data[$field]) ? $data[$field] : null, $data, $prefix]);
                    }
                }
            }

            return $this;
        });

        Builder::macro('order', function ($field, $order = null) {
            /** @var Builder $this */
            if (empty($field)) {
                return $this;
            }

            if (is_string($field)) {
                if (strpos($field, ',')) {
                    $field = array_map('trim', explode(',', $field));
                } else {
                    $field = empty($order) ? $field : [$field => $order];
                }
            }

            if (is_array($field)) {
                $this->{$this->unions ? 'unionOrders' : 'orders'} = array_merge($this->{$this->unions ? 'unionOrders' : 'orders'}, $field);
            } else {
                $this->{$this->unions ? 'unionOrders' : 'orders'}[] = [
                    'column' => $field,
                    'direction' => 'asc',
                ];;
            }

            return $this;
        });

        Builder::macro('page', function ($page, $listRows = null) {
            /** @var Builder $this */
            if (is_null($listRows) && strpos($page, ',')) {
                list($page, $listRows) = explode(',', $page);
            }

            return $this->skip(($page - 1) * $listRows)->take($listRows);
        });

        Builder::macro('whereFromBuilder', function (Builder $builder) {
            /** @var Builder $this */
            $this->getQuery()->mergeWheres($builder->getQuery()->wheres, $builder->getQuery()->bindings['where']);
            return $this;
        });

        return [
            'dependencies' => [
                RequestInterface::class       => Request::class,
                ServerRequestInterface::class => Request::class,
                ResponseInterface::class      => Response::class,
            ],
            'commands'     => [
            ],
            'annotations'  => [
                'scan' => [
                    'paths' => [
                        __DIR__,
                    ],
                ],
            ],
        ];
    }
}
