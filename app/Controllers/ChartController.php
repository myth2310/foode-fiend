<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Entities\ChartEntity;
use App\Models\ChartModel;
use App\Models\MenuModel;
use CodeIgniter\HTTP\ResponseInterface;

class ChartController extends BaseController
{
    protected $chartEntity;
    protected $chartModel;
    protected $menuModel;

    public function __construct()
    {
        $this->chartEntity = new ChartEntity();
        $this->chartModel = new ChartModel();
        $this->menuModel = new MenuModel();
    }

    public function get($user_id)
    {
        return $this->chartModel->where('user_id', $user_id)->findAll();
    }


    public function addToChart($menu_id)
    {
      
        $user_id = session()->get('user_id');
        $existingItem = $this->chartModel->where('user_id', $user_id)
            ->where('menu_id', $menu_id)
            ->first();

        $quantity = $this->request->getPost('quantity') ?: 1;

        if ($existingItem) {

            $existingItem->quantity += $quantity;
            if (!$this->chartModel->save($existingItem)) {
                return redirect()->back()->with('errors', $this->chartModel->errors());
            }
        } else {
            $chart = $this->chartEntity;
            $chart->user_id = $user_id;
            $chart->menu_id = $menu_id;
            $chart->store_id = $this->menuModel->select('store_id')->find($menu_id)->store_id;
            $chart->quantity = $quantity;

            if (!$this->chartModel->save($chart)) {
                return redirect()->back()->with('errors', $this->chartModel->errors());
            }
        }

        return redirect()->back()->with('messages', ['Berhasil ditambahkan ke keranjang']);
    }


    // fungsi untuk menghapus menu dari keranjang
    public function removeFromChart()
    {
        $chart_id = $this->request->getPost('chart_id');
        if (!$this->chartModel->delete($chart_id)) {
            return redirect()->back()->with('errors', $this->chartModel->errors());
        }
        return redirect()->back()->with('messages', ['Berhasil dihapus dari keranjang']);
    }
}
