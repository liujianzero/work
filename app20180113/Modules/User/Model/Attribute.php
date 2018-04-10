<?php
/**
 * 商品-属性
 */
namespace App\Modules\User\Model;

use Illuminate\Database\Eloquent\Model;
use Auth;

class Attribute extends Model
{
    protected $fillable = [
        'user_id', 'goods_type_id', 'name', 'value',
        'color', 'cat_type', 'attr_type', 'input_type'
    ];

    /**
     * 获取相应的属性类型。
     */
    public function goodsType()
    {
        return $this->belongsTo('App\Modules\User\Model\GoodsType');
    }

    /**
     * 获取所有商品的属性。
     */
    public function goodsAttributes()
    {
        return $this->hasMany('App\Modules\User\Model\GoodsAttribute');
    }

    /**
     * 获取某个属性分类的所有属性与规格
     */
    public static function getAttr($type_id)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            $tmp = Attribute::select('id', 'name', 'value', 'input_type')
                ->where('goods_type_id', $type_id)->where('user_id', $uid)->get()->toArray();
            if (count($tmp) > 0) {
                $data = [
                    'list' => [],
                    'manual' => [],
                    'l' => 0,
                    'm' => 0
                ];
                foreach ($tmp as $v) {
                    if ($v['input_type'] == 'list') {
                        $data['list'][] = [
                            'id' => $v['id'],
                            'name' => $v['name'],
                            'value' => explode(',', str_replace("\r\n", ',', $v['value']))
                        ];
                        $data['l']++;
                    } else {
                        $data['manual'][] = [
                            'id' => $v['id'],
                            'name' => $v['name'],
                            'value' => '',
                            'goods_attr_id' => 0
                        ];
                        $data['m']++;
                    }
                }
                return $data;
            } else {
                return [];
            }
        } else {
            return [];
        }
    }

    /**
     * 获取某个属性分类的所有属性与规格
     */
    public static function getAttrByOther($goods)
    {
        if (Auth::check()) {
            $uid = Auth::user()->id;
            $tmp = Attribute::select('id', 'name', 'value', 'input_type')
                ->where('goods_type_id', $goods->goods_type_id)->where('user_id', $goods->uid)->get()->toArray();
            if (count($tmp) > 0) {
                $data = [
                    'list' => [],
                    'manual' => [],
                    'l' => 0,
                    'm' => 0
                ];
                foreach ($tmp as $v) {
                    if ($v['input_type'] == 'list') {
                        $data['list'][] = [
                            'id' => $v['id'],
                            'name' => $v['name'],
                            'value' => explode(',', str_replace("\r\n", ',', $v['value']))
                        ];
                        $data['l']++;
                    } else {
                        $data['manual'][] = [
                            'id' => $v['id'],
                            'name' => $v['name'],
                            'value' => '',
                            'goods_attr_id' => 0
                        ];
                        $data['m']++;
                    }
                }
                return $data;
            } else {
                return [];
            }
        } else {
            return [];
        }
    }

    /**
     * 获取某个商品的所有属性与规格
     */
    public static function goodsAttr($goods)
    {
        $ret = Attribute::getAttr($goods->goods_type_id);
        $tmp = GoodsAttribute::where('goods_id', $goods->id)
            ->get()->toArray();
        if (count($tmp) > 0) {
            $price_id = [];
            foreach ($ret['list'] as &$v) {//筛选规格
                $checked = [];
                $attr_id = [];
                foreach ($v['value'] as $k1 => $v1) {
                    $checked[$k1] = 0;
                    $attr_id[$k1] = 0;
                    foreach ($tmp as $v2) {
                        if ($v['id'] == $v2['attribute_id'] && $v1 == $v2['attr_value']) {
                            $checked[$k1] = 1;
                            $price_id[] = $attr_id[$k1] = $v2['id'];
                        }
                    }
                }
                $v['checked'] = $checked;
                $v['attr_id'] = $attr_id;
            }
            foreach ($ret['manual'] as $k1 => $v1) {//筛选属性
                foreach ($tmp as $v2) {
                    if ($v1['id'] == $v2['attribute_id']) {
                        $ret['manual'][$k1]['value'] = $v2['attr_value'];
                        $ret['manual'][$k1]['goods_attr_id'] = $v2['id'];
                    }
                }
            }
            if (count($price_id) > 0) {//规格价格列表
                $price = [];
                $tmp = GoodsAttribute::where('goods_id', $goods->id)
                    ->where('user_id', Auth::user()->id)->whereIn('id', $price_id)
                    ->get()->toArray();
                foreach ($tmp as $v1) {
                    $price[] = [
                        $v1['attribute_id'],
                        $v1['attr_value'],
                        $v1['attr_price'],
                        $v1['id']
                    ];
                }
                $ret['price'] = $price;
            }
            return $ret;
        } else {
            return $ret;
        }
    }
}