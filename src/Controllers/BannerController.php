<?php

namespace Fynduck\Banners\Controllers;

use Fynduck\Banners\BannerService;
use Fynduck\Banners\Models\Banner;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class BannerController extends Controller
{
    protected $imageSizes = array(
        'xs'  => ['width' => 70, 'height' => 70],
        'big' => ['width' => 3000, 'height' => 1136]
    );

    protected $imageFolder = 'sliders';
    protected $route = 'sliders';
    protected $types;

    public function __construct()
    {
        $this->middleware('admin:view');
        $this->types = [
            'top'     => trans('admin.top'),
            'content' => trans('admin.content')
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        if ($request->ajax()) {
            $query = Banner::leftJoin('banners_trans', 'banners.id', '=', 'banners_trans.banner_id');
            $this->filter($query, $request);
            $banners = $query->orderBy('sort', 'ASC')->orderBy('updated_at', 'DESC')->paginate(25);

            foreach ($banners as $banner) {
                $banner->show_img = asset('images/' . $this->imageFolder . '/' . key($this->imageSizes) . '/' . $banner->image);
                $banner->show_type = $this->types[$banner->type];
                $banner->route_edit = route('banners.edit', $banner->banner_id);
                $banner->route_delete = route('banners.destroy', $banner->banner_id);
            }

            return response()->json($banners);
        }

        $data['trans'] = [
            'title_tab_filter' => trans('admin.filters'),
            'search'           => trans('admin.search'),
            'insert_query'     => trans('admin.insert_query'),
            'clear'            => trans('admin.clear'),
            'image'            => trans('admin.image'),
            'title'            => trans('admin.title'),
            'position'         => trans('admin.position'),
            'sort'             => trans('admin.sort'),
            'status'           => trans('admin.status'),
            'actions'          => trans('admin.action'),
            'active'           => trans('admin.active_s'),
            'inactive'         => trans('admin.inactive_s'),
            'really_delete'    => trans('admin.you_really_delete'),
            'cancel'           => trans('admin.cancel'),
            'warning'          => trans('admin.warning'),
        ];

        return view('banners::index', $data);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $data['old_image'] = null;
        $data['types'] = $this->types;
        $data['target'] = ['_self', '_blank', '_parent', '_self', '_top'];
        $data['pages'] = PagesTrans::where('status', 1)
            ->where('lang', config('app.locale_id'))
            ->pluck('title', 'page_id');
        $data['item'] = $data['itemTrans'] = false;

        $data['trans'] = json_encode([
            'title' => trans('admin.date_show'),
            'from'  => trans('admin.date_from'),
            'to'    => trans('admin.date_to'),
        ]);

        $data['route'] = route('banners.store');

        return view('banners::view', $data);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $this->validate($request, [
            'image' => 'required'
        ]);

        $imgName = null;
        if ($request->get('items')[config('app.fallback_locale_id')]['title'])
            $imgName = $request->get('items')[config('app.fallback_locale_id')]['title'];

        $image = UploadImages::uploadImages($this->imageFolder, $request->file('image'), $request->get('old_image'), $imgName, $this->imageSizes);

        $type = '';
        $page_id = 0;
        if ($request->get('to_page')) {
            $type = explode('_', $request->get('to_page'))[0];
            $page_id = explode('_', $request->get('to_page'))[1];
        }

        /**
         * Create banner
         */
        $banner = Banner::create([
            'image'     => $image,
            'target'    => $request->get('target'),
            'type_page' => $type,
            'page_id'   => $page_id,
            'type'      => $request->get('type'),
            'sort'      => $request->get('sort') ? $request->get('sort') : 0,
            'date_from' => $request->get('f') ? $request->get('f') : null,
            'date_to'   => $request->get('t') ? $request->get('t') : null
        ]);

        /**
         * Add trans to banner
         */
        BannerService::addUpdateTrans($banner->id, $request->get('items'));

        /**
         * Add show pages
         */
        if ($request->get('show_page'))
            BannerService::addUpdateShow($banner->id, $request->get('show_page'));

        return redirect()->route('banners.index')->with('success', trans('admin.data_save'));
    }

    /**
     * Display the specified resource.
     *
     * @param  \Fynduck\Banners\Models\Banner $banner
     * @return \Illuminate\Http\Response
     */
    public function show(Banner $banner)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $data['item'] = Banner::find($id);
        $data['itemTrans'] = $data['item']->getTrans;
        $data['itemTrans'] = Arrays::setKey($data['itemTrans'], 'lang');
        $data['types'] = $this->types;
        $data['target'] = ['_self', '_blank', '_parent', '_self', '_top'];
        $data['date_range'] = json_encode([
            'from' => $data['item']->date_from,
            'to'   => $data['item']->date_to
        ]);
        $data['trans'] = json_encode([
            'title' => trans('admin.date_show'),
            'from'  => trans('admin.date_from'),
            'to'    => trans('admin.date_to'),
        ]);

        $page_show = [];
        foreach ($data['item']->getShow as $item) {
            $page_show[] = $item->type_page . '_' . $item->page_id;
        }
        $data['pagesShow'] = json_encode($page_show);
        $data['toPage'] = json_encode($data['item']->type_page ? $data['item']->type_page . '_' . $data['item']->page_id : '');
        $data['old_image'] = json_encode([
            'name' => $data['item']->image,
            'url'  => asset('images/' . $this->imageFolder . '/' . key($this->imageSizes) . '/' . $data['item']->image)
        ]);

        $data['route'] = route('banners.update', $data['item']->id);
        $data['method'] = method_field('PUT');

        return view('Banners::view', $data);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request $request
     * @param  int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $this->validate($request, [
            'old_image' => 'required'
        ]);

        $imgName = null;
        if ($request->get('items')[config('app.fallback_locale_id')]['title'])
            $imgName = $request->get('items')[config('app.fallback_locale_id')]['title'];

        $image = UploadImages::uploadImages($this->imageFolder, $request->file('image'), $request->get('old_image'), $imgName, $this->imageSizes);

        $show_page['type'] = '';
        $show_page['page_id'] = 0;
        if ($request->get('to_page')) {
            $show_page['type'] = explode('_', $request->get('to_page'))[0];
            $show_page['page_id'] = explode('_', $request->get('to_page'))[1];
        }

        /**
         * Update banner
         */
        $banner = Banner::updateOrCreate([
            'id' => $id
        ], [
            'image'     => $image,
            'target'    => $request->get('target'),
            'type_page' => $show_page['type'],
            'page_id'   => $show_page['page_id'],
            'type'      => $request->get('type'),
            'sort'      => $request->get('sort') ? $request->get('sort') : 0,
            'date_from' => $request->get('f') ? $request->get('f') : null,
            'date_to'   => $request->get('t') ? $request->get('t') : null
        ]);

        /**
         * Add trans to banner
         */
        BannerService::addUpdateTrans($banner->id, $request->get('items'));

        /**
         * Add show pages
         */
        if ($request->get('show_page'))
            BannerService::addUpdateShow($banner->id, $request->get('show_page'));

        return redirect()->route('banners.index')->with('success', trans('admin.data_save'));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int $id
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function destroy($id, Request $request)
    {
        $banner = Banner::find($id);
        UploadImages::deleteImages('images/' . $this->imageFolder, $this->imageSizes, $banner->image);
        $response = [
            'message' => trans('admin.data_not_deleted'),
            'type'    => 'success',
        ];
        if ($request->get('image')) {
            $response['message'] = trans('admin.data_delete');
            $banner->image = '';
            $banner->save();
        } else {
            if ($banner->delete())
                $response['message'] = trans('admin.data_delete');
        }


        return response()->json($response);
    }

    private function filter(&$query, Request $request)
    {
        if ($request->get('q')) {
            $query->where(function ($q) use ($request) {
                $q->where('title', 'LIKE', '%' . $request->get('q') . '%')
                    ->orWhere('description', 'LIKE', '%' . $request->get('q') . '%');
            });
        }

        if ($request->get('status'))
            $query->where('status', $request->get('status'));

        if ($request->get('sortBy')) {
            $sort = 'ASC';
            if ($request->get('sortDesc'))
                $sort = 'DESC';
            $query->orderBy($request->get('sortBy'), $sort);
        }
    }
}
