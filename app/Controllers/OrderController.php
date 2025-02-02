<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Entities\OrderEntity;
use App\Models\ChartModel;
use App\Models\MenuModel;
use App\Models\OrderModel;
use CodeIgniter\HTTP\ResponseInterface;

class OrderController extends BaseController
{
    protected $order;
    protected $orderModel;
    protected $chartModel;
    protected $storeController;
    protected $paymentController;

    public function __construct()
    {
        $this->order = new OrderEntity();
        $this->orderModel = new OrderModel();
        $this->chartModel = new ChartModel();
        $this->storeController = new StoreController();
        $this->paymentController = new PaymentController();
    }

    public function create()
    {
        $user_id = session()->get('user_id');
        $this->order->user_id = $user_id;
        $this->order->menu_id = $this->request->getPost('menu_id');
        $this->order->quantity = $this->request->getPost('quantity');
        $this->order->price = $this->request->getPost('price');
        $this->order->total_price = $this->order->price * $this->order->quantity;

        if (!$this->orderModel->save($this->order)) {
            return redirect()->back()->withInput()->with('errors', [$this->orderModel->errors()]);
        }

        return redirect()->back()->withInput()->with('messages', ['Pesanana berhasil dibuat']);
    }

    public function add()
    {
        $menu_id = $this->request->getPost('menu_id');
        $quantity = $this->request->getPost('quantity');
        $user_id = session()->get('user_id');
        $store_id = session()->get('store_id');
        $price = $this->request->getPost('price');
        $total_price = $quantity * $price;
        $status = 'diproses';

        $orderModel = new OrderModel();

        $data = [
            'order_id' => uniqid(),
            'user_id' => $user_id,
            'store_id' => $store_id,
            'menu_id' => $menu_id,
            'quantity' => $quantity,
            'price' => $price,
            'total_price' => $total_price,
            'status' => $status
        ];

        $orderModel->insert($data);

        return redirect()->back()->with('success', 'Order has been placed successfully.');
    }


    public function index()
    {
        $user_id = session()->get('user_id');
        $dataChart = $this->chartModel->getAllChartWithMenu($user_id);
        $dataStore = $this->storeController->getAllStore();
        $data = [
            'title' => 'Halaman Utama | Foodie Fiend',
            'hero_img' => 'https://images.squarespace-cdn.com/content/v1/61709486e77e1d27c181981c/a9eb540d-aff8-4360-8354-1d35c856a561/0223_UrbanSpace_ZeroIrving_LizClayman_160.png',
            'items' => $dataStore,
            'charts' => $dataChart,
            'use_chart_button' => false,
            'use_hero_text' => true,
        ];

        return view('pages/my_order', $data);
    }

    // public function checkout()
    // {
    //     $store_id = $this->request->getPost('store_id');
    //     $menu_ids = $this->request->getPost('menu_id');
    //     $quantities = $this->request->getPost('quantity');
    //     $prices = $this->request->getPost('total_price');
    //     $menu_names = $this->request->getPost('menu_name');
    //     $image_urls = $this->request->getPost('image_url');

    //     $order_data = [];
    //     $total_price = 0;

    //     // Loop untuk setiap item di dalam pesanan
    //     foreach ($menu_ids as $index => $menu_id) {
    //         $quantity = $quantities[$index];
    //         $price = $prices[$index];
    //         $total_price += $price; // Menghitung total harga

    //         $order_data[] = [
    //             'menu_id' => $menu_id,
    //             'menu_name' => $menu_names[$index],
    //             'quantity' => $quantity,
    //             'price' => $price,
    //             'image_url' => $image_urls[$index],
    //             'status' => 'pending', 
    //         ];
    //     }

    //     // Memproses pembayaran menggunakan total harga keseluruhan
    //     $payment_data = [
    //         'store_id' => $store_id,
    //         'total_price' => $total_price
    //     ];

    //     $snapToken = $this->paymentController->create($payment_data);

    //     // Menambahkan Snap Token ke setiap item pesanan
    //     foreach ($order_data as &$item) {
    //         $item['snapToken'] = $snapToken;
    //     }

    //     $orderModel = new \App\Models\OrderModel();

    //     try {
    //         // Insert semua item pesanan
    //         foreach ($order_data as $data) {
    //             $orderModel->insert($data);
    //         }

    //         return view('pages/checkout', [
    //             'order_data' => $order_data,
    //             'snapToken' => $snapToken,
    //         ]);
    //     } catch (\Exception $e) {
    //         return redirect()->back()->with('error', 'Pesanan gagal disimpan: ' . $e->getMessage());
    //     }
    // }


    // public function checkout()
    // {
    //     $price = $this->request->getPost('price');
    //     $quantity = $this->request->getPost('quantity');

    //     $data = [
    //         'store_id' => $this->request->getPost('store_id'),
    //         'menu_id' => $this->request->getPost('menu_id'),
    //         'price' => $price,
    //         'quantity' => $quantity,
    //     ];

    //     $snapToken = $this->paymentController->create($data);

    //     $order_data = [
    //         'menu_id' => $this->request->getPost('menu_id'),
    //         'menu_name' => $this->request->getPost('menu_name'),
    //         'menu_description' => $this->request->getPost('menu_description'),
    //         'quantity' => $quantity,
    //         'price' => $price,
    //         'status' => 'pending', 
    //         'image_url' => $this->request->getPost('image_url'),
    //         'snapToken' => $snapToken,
    //     ];


    //     $orderModel = new \App\Models\OrderModel();

    //     try {
    //         $orderModel->insert($order_data);
    //         $order_id = $orderModel->insertID(); 

    //         return view('pages/checkout', [
    //             'order_data' => $order_data,
    //             'order_id' => $order_id
    //         ]);
    //     } catch (\Exception $e) {
    //         return redirect()->back()->with('error', 'Pesanan gagal disimpan: ' . $e->getMessage());
    //     }
    // }



    public function checkout()
    {
        $store_id = $this->request->getPost('store_id');
        $menu_id = $this->request->getPost('menu_id');
        $price = $this->request->getPost('price');
        $quantity = $this->request->getPost('quantity');
        $menu_name = $this->request->getPost('menu_name');
        $menu_description = $this->request->getPost('menu_description');
        $image_url = $this->request->getPost('image_url');
        $user_id = session()->get('user_id');
        $charts_id = $this->request->getPost('charts_id'); 

        $data = [];

        if (is_array($menu_id)) {
            foreach ($menu_id as $index => $menu) {
                $data[] = [
                    'store_id' => $store_id,
                    'menu_id' => $menu,
                    'charts_id' => $charts_id[$index], 
                    'price' => $price[$index],
                    'quantity' => $quantity[$index],
                    'menu_name' => $menu_name[$index],
                    'menu_description' => $menu_description[$index],
                    'image_url' => $image_url[$index]
                ];
            }
        } else {
            $data[] = [
                'store_id' => $store_id,
                'menu_id' => $menu_id,
                'id' => $charts_id,
                'price' => $price,
                'quantity' => $quantity,
                'menu_name' => $menu_name,
                'menu_description' => $menu_description,
                'image_url' => $image_url
            ];
        }

        try {
            $snapToken = $this->paymentController->create($data);
        } catch (\Exception $e) {
            log_message('error', 'Gagal mendapatkan Snap Token: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Gagal mendapatkan Snap Token');
        }

        if (!$snapToken) {
            log_message('error', 'Snap Token tidak valid.');
            return redirect()->back()->with('error', 'Gagal mendapatkan Snap Token');
        }

        $order_data = [];
        foreach ($data as $item) {
            $order_data[] = [
                'menu_id' => $item['menu_id'],
                'menu_name' => $item['menu_name'],
                'menu_description' => $item['menu_description'],
                'quantity' => $item['quantity'],
                'price' => $item['price'],
                'status' => 'pending',
                'image_url' => $item['image_url'],
                'snapToken' => $snapToken,
                'id' => $item['charts_id'],
            ];
        }

        $orderModel = new \App\Models\OrderModel();
        $chartModel = new \App\Models\ChartModel();

        $db = \Config\Database::connect();
        $db->transBegin(); 
        try {
            if (count($order_data) > 1) {
                $orderModel->insertBatch($order_data);
            } else {
                foreach ($order_data as $order) {
                    $orderModel->insert($order);
                }
            }

            $order_id = $orderModel->insertID();
            
            if (is_array($charts_id)) {
                foreach ($charts_id as $id) {
                    $chartModel->delete($id); 
                }
            } else {
                $chartModel->delete($charts_id); 
            }

            if ($db->transStatus() === false) {
                $db->transRollback();
                throw new \Exception('Pesanan gagal disimpan.');
            } else {
                $db->transCommit();
            }

            return view('pages/checkout', [
                'order_data' => $order_data,
                'order_id' => $order_id
            ]);
        } catch (\Exception $e) {
            log_message('error', 'Pesanan gagal disimpan: ' . $e->getMessage());

            $db->transRollback(); 
            $session = session();
            $session->setFlashdata('error', 'Pesanan gagal disimpan: ' . $e->getMessage());
            return redirect()->back();
        }
    }


    public function getAllOrders($user_id, $order_status)
    {
        return $this->orderModel->getAllOrdersWithMenus($user_id, $order_status);
    }
}
