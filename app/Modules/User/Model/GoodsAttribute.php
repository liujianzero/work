<?php
/**
 * 商品-属性
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use Auth;

class GoodsAttribute extends Model
{
    protected $fillable = [
        'user_id', 'goods_id', 'attribute_id', 'attr_value',
        'attr_color', 'attr_price', 'attr_img', 'attr_thumb'
    ];

    /**
     * 获取相应的属性。
     */
    public function Attribute()
    {
        return $this->belongsTo('App\Modules\User\Model\Attribute');
    }

    /**
     * 修改商品时的属性
     */
    public static function makeAttr($goods_spec_list, $goods_attr_list, $goods_id)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            $insert = [];
            $update = [];
            $attr_id = [];
            $time = date('Y-m-d H:i:s');
            if (!empty($goods_spec_list)) {
                $tmp1 = explode(',', $goods_spec_list);
                $tmp1 = array_filter(array_unique($tmp1));
                sort($tmp1, SORT_NUMERIC);
                foreach ($tmp1 as $v) {
                    $tmp2 = explode('-', $v);
                    if (intval($tmp2[3]) > 0) {
                        $update[] = [
                            'id' => $tmp2[3],
                            'attr_price' => GoodsAttribute::priceFormat(floatval($tmp2[2])),
                            'attr_value' => trim($tmp2[1]),
                        ];
                        $attr_id[] = $tmp2[3];
                    } else {
                        $insert[] = [
                            'user_id' => $uid,
                            'goods_id' => $goods_id,
                            'attribute_id' => $tmp2[0],
                            'attr_price' => GoodsAttribute::priceFormat(floatval($tmp2[2])),
                            'attr_value' => trim($tmp2[1]),
                            'created_at' => $time,
                            'updated_at' => $time
                        ];
                    }

                }
            }
            if (!empty($goods_attr_list)) {
                $tmp1 = explode(',', $goods_attr_list);
                $tmp1 = array_filter(array_unique($tmp1));
                foreach ($tmp1 as $v) {
                    $tmp2 = explode('-', $v);
                    if (intval($tmp2[3]) > 0) {
                        if (!empty(trim($tmp2[2]))) {
                            $update[] = [
                                'id' => $tmp2[3],
                                'attr_price' => null,
                                'attr_value' => trim($tmp2[2])
                            ];
                            $attr_id[] = $tmp2[3];
                        }
                    } else {
                        if (!empty(trim($tmp2[2]))) {
                            $insert[] = [
                                'user_id' => $uid,
                                'goods_id' => $goods_id,
                                'attribute_id' => $tmp2[0],
                                'attr_price' => null,
                                'attr_value' => trim($tmp2[2]),
                                'created_at' => $time,
                                'updated_at' => $time
                            ];
                        }
                    }
                }
            }
            if (count($attr_id) > 0) {//删除失效属性
                GoodsAttribute::whereNotIn('id', $attr_id)
                    ->where('user_id', $uid)
                    ->where('goods_id', $goods_id)
                    ->delete();
            }
            if (count($update) > 0) {//更新属性
                foreach ($update as $v) {
                    $goods_attr_id = $v['id'];
                    unset($v['id']);
                    GoodsAttribute::where('id', $goods_attr_id)
                        ->where('user_id', $uid)
                        ->where('goods_id', $goods_id)
                        ->update($v);
                }
            }
            if (count($insert) > 0) {//新增属性
                GoodsAttribute::insert($insert);
            }
        }
        return true;
    }

    /**
     * 格式化价格 *.**
     */
    protected static function priceFormat($price)
    {
        return number_format($price, 2, '.', '');
    }

    /**
     * 获取商品的属性
     */
    public static function getAttr($goods)
    {
        $tmp = GoodsAttribute::where('goods_id', $goods->id)
            ->get();
        if (count($tmp) > 0) {
            $tmp1 = [];
            foreach ($tmp as $v) {
                if ($v->Attribute->input_type == 'list') {
                    $tmp3 = [
                        'attr_id' => $v->id,
                        'attr_value' => GoodsAttribute::cc_msubstr($v->attr_value, 7),
                        'attr_price' => $v->attr_price,
                        'attribute_id' => $v->attribute_id
                    ];
                    $tmp1[$v->attribute_id]['children'][] = $tmp3;
                    $tmp1[$v->attribute_id]['name'] = $v->Attribute->name;
                    $tmp1[$v->attribute_id]['type'] = $v->Attribute->input_type;
                } else {
                    $tmp1[$v->attribute_id]['attr_id'] = $v->id;
                    $tmp1[$v->attribute_id]['attr_value'] = GoodsAttribute::cc_msubstr($v->attr_value, 6);
                    $tmp1[$v->attribute_id]['name'] = GoodsAttribute::cc_msubstr($v->Attribute->name, 6);
                    $tmp1[$v->attribute_id]['type'] = $v->Attribute->input_type;
                }
            }
            $tmp2 = [
                'list' => [],
                'manual' => []
            ];
            foreach ($tmp1 as $v) {
                $tmp2[$v['type']][] = $v;
            }
        } else {
            $tmp2 = [
                'list' => [],
                'manual' => []
            ];
        }

        return $tmp2;
    }

    /**
     * 截取字符串
     */
    public static function cc_msubstr($str, $length, $start = 0, $charset = "utf-8", $suffix = true)
    {
        if (function_exists("mb_substr")) {
            return mb_substr($str, $start, $length, $charset);
        } elseif (function_exists('iconv_substr')) {
            return iconv_substr($str, $start, $length, $charset);
        }
        $re['utf-8'] = "/[/x01-/x7f]|[/xc2-/xdf][/x80-/xbf]|[/xe0-/xef][/x80-/xbf]{2}|[/xf0-/xff][/x80-/xbf]{3}/";
        $re['gb2312'] = "/[/x01-/x7f]|[/xb0-/xf7][/xa0-/xfe]/";
        $re['gbk'] = "/[/x01-/x7f]|[/x81-/xfe][/x40-/xfe]/";
        $re['big5'] = "/[/x01-/x7f]|[/x81-/xfe]([/x40-/x7e]|/xa1-/xfe])/";
        preg_match_all($re[$charset], $str, $match);
        $slice = join("", array_slice($match[0], $start, $length));
        if ($suffix) {
            return $slice . "..";
        } else {
            return $slice;
        }
    }

    /**
     * 制作属性库存数据
     */
    public static function handleAttrTable($stock, $uid, $id)
    {
        $condition = [];
        $number = [];
        foreach ($stock as $value) {
            $value['attr_id'] = array_unique($value['attr_id']);
            $value['name'] = array_unique($value['name']);
            $value['goods_attr_id'] = array_unique($value['goods_attr_id']);
            $ids = self::where('user_id', $uid)
                ->where('goods_id', $id)
                ->whereIn('attribute_id', $value['attr_id'])
                ->whereIn('attr_value', $value['name'])
                ->orderBy('attribute_id')
                ->lists('id', 'attribute_id')
                ->toArray();
            $ids = implode(',', array_unique($ids));
            $condition[] = $ids;
            $number[] = [
                'ids' => $ids,
                'number' => $value['val']
            ];
        }
        $list = GoodsStock::where('goods_id', $id)
            ->whereIn('goods_attr_id', $condition)
            ->lists('goods_attr_id', 'id')
            ->toArray();
        $update = $insert = $delete = [];
        $time = date('Y-m-d H:i:s');
        foreach ($number as $v) {
            if (in_array($v['ids'], $list)) {
                $update[] = [
                    'id' => array_search($v['ids'], $list),
                    'goods_number' => $v['number'],
                    'updated_at' => $time
                ];
                $delete[] = array_search($v['ids'], $list);
            } else {
                $insert[] = [
                    'goods_id' => $id,
                    'goods_attr_id' => $v['ids'],
                    'goods_number' => $v['number'],
                    'created_at' => $time,
                    'updated_at' => $time
                ];
            }
        }
        return ['update' => $update, 'insert' => $insert, 'delete' => $delete];
    }
}