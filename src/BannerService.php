<?php
/**
 * Created by PhpStorm.
 * User: admin
 * Date: 9/7/18
 * Time: 12:29 PM
 */

namespace Fynduck\Banners;


use Fynduck\Banners\Models\Banner;
use Fynduck\Banners\Models\BannerShow;
use Fynduck\Banners\Models\BannerTrans;

class BannerService
{
    public static function addUpdate(array $data)
    {
        $itemAdd = Banner::create([
                'image'     => $data['image'],
                'target'    => $data['target'],
                'type_page' => $data['type_page'],
                'page_id'   => $data['page_id'],
                'type'      => $data['type'],
                'sort'      => $data['sort'],
                'date_from' => $data['date_from'],
                'date_to'   => $data['date_to']
            ]
        );

        if ($itemAdd == false)
            return back()->withErrors(trans('admin.data_not_save'));

        return $itemAdd->id;
    }

    public static function addUpdateTrans($id, $items)
    {
        foreach ($items as $lang => $item) {

            $itemLang = BannerTrans::updateOrCreate([
                'banner_id' => $id,
                'lang'      => $lang
            ], [
                    'title'       => $item['title'],
                    'description' => $item['description'],
                    'status'      => isset($item['status']) ? 1 : 0,
                ]
            );

            if ($itemLang == false) {
                return back()->withErrors(trans('admin.data_not_save'));
            }
        }
    }

    public static function addUpdateShow($id, $show_pages)
    {
        BannerShow::where('banner_id', $id)->delete();

        foreach (explode(',', $show_pages) as $item) {
            $item = explode('_', $item);
            $itemShow = BannerShow::create(
                [
                    'banner_id' => $id,
                    'type_page' => $item[0],
                    'page_id'   => $item[1]
                ]
            );
            if ($itemShow == false) {
                return back()->withErrors(trans('admin.data_not_save'));
            }
        }
    }
}
