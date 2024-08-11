<?php

namespace App\Controllers;

use App\Controllers\BaseController;
use App\Entities\OrderEntity;
use App\Entities\TransactionEntity;
use App\Libraries\Midtrans;
use App\Models\OrderModel;
use App\Models\TransactionModel;
use Ramsey\Uuid\Uuid;
use CodeIgniter\HTTP\ResponseInterface;

class PaymentController extends BaseController
{
    protected $midtrans;
    protected $transactionModel;
    protected $orderModel;

    public function __construct()
    {
        $this->midtrans = new Midtrans();
        $this->transactionModel = new TransactionModel();
        $this->orderModel = new OrderModel();
    }

    public function create($data)
    {
        $total_price = $data['price'] * $data['quantity'];
        $order_id = Uuid::uuid7()->toString();
        $order_data = [
            'user_id' => session()->get('user_id'),
            'order_id' => $order_id,
            'store_id' => $data['store_id'],
            'menu_id' => $data['menu_id'],
            'quantity' => $data['quantity'],
            'price' => $data['price'],
            'total_price' => $total_price,
            'status' => 'pending',
        ];
        $order = new OrderEntity($order_data);
        $this->orderModel->save($order);

        $transaction_detail = [
            'order_id' => $order_id,
            'gross_amount' => $total_price, // Amount in IDR
        ];
        $customer_detail = [
            'first_name' => session()->get('name'),
            'email' => session()->get('email'),
            'phone' => session()->get('phone'),
        ];
        $transaction_data = [
            'transaction_details' => $transaction_detail,
            'customer_details' => $customer_detail,
            'callbacks' => [
                'finish' => base_url('/user/dashboard/order'),
            ],
        ];

        return $this->midtrans->getSnapToken($transaction_data);
    }

    public function notification()
    {
        $notif = $this->midtrans->handleNotification();

        $transactionData = [
            'order_id' => $notif->order_id,
            'transaction_id' => $notif->transaction_id,
            'gross_amount' => $notif->gross_amount,
            'transaction_status' => $notif->transaction_status,
            'payment_type' => $notif->payment_type,
            'transaction_time' => $notif->transaction_time,
            'fraud_status' => $notif->fraud_status,
            'customer_name' => $notif->customer_details->first_name . ' ' . $notif->customer_details->last_name,
            'customer_email' => $notif->customer_details->email,
            'customer_phone' => $notif->customer_details->phone,
            'payment_code' => $notif->payment_code ?? null,
            'bank' => $notif->bank ?? null,
            'va_numbers' => $notif->va_numbers[0]->va_number ?? null,
            'approval_code' => $notif->approval_code ?? null,
            'signature_key' => $notif->signature_key,
            'currency' => $notif->currency,
            'expiry_time' => $notif->expiry_time ?? null,
            'billing_address' => json_encode($notif->billing_address ?? null),
            'shipping_address' => json_encode($notif->shipping_address ?? null),
            'item_details' => json_encode($notif->item_details),
        ];
        $transactionEntity = new TransactionEntity($transactionData);

        // simpan atau perbarui data transaksi di database
        $existingTransaction = $this->transactionModel->where('transaction_id', $notif->transaction_id)->first();
        if ($existingTransaction) {
            $transactionEntity->id = $existingTransaction->id;
            $this->transactionModel->save($transactionEntity);
        } else {
            $this->transactionModel->insert($transactionEntity);
        }

        // proses status transaksi sesuai kebutuhan
        $transaction = $notif->transaction_status;
        $type = $notif->payment_type;
        $order_id = $notif->order_id;
        $fraud = $notif->fraud_status;

        // update status transaksi
        $this->transactionModel->where('transaction_id', $notif->transaction_id)->set([
            'transaction_status' => $transaction,
        ])->update();

        if ($transaction == 'capture') {
            if ($type == 'credit_card') {
                if ($fraud == 'challenge') {
                    // Update order status to 'challenge' in database
                    $this->orderModel->where('order_id', $order_id)->set(['status' => 'pending'])->update();
                } else {
                    // Update order status to 'success' in database
                    $this->orderModel->where('order_id', $order_id)->set(['status' => 'diproses'])->update();
                }
            }
        } elseif ($transaction == 'settlement') {
            // Update order status to 'settlement' in database
            $this->orderModel->where('order_id', $order_id)->set(['status' => 'diproses'])->update();
        } elseif ($transaction == 'pending') {
            // Updata order status to 'pending' in database
            $this->orderModel->where('order_id', $order_id)->set(['status' => 'ditunda'])->update();
        } else if ($transaction == 'deny') {
            // Update order status to 'deny' in database
            $this->orderModel->where('order_id', $order_id)->set(['status' => 'dibatalkan'])->update();
        } else if ($transaction == 'expire') {
            // Update order status to 'expire' in database
            $this->orderModel->where('order_id', $order_id)->set(['status' => 'kadaluwarsa'])->update();
        } else if ($transaction == 'cancel') {
            // Update order status to 'cancel' in database
            $this->orderModel->where('order_id', $order_id)->set(['status' => 'dibatalkan'])->update();
        }

        return $this->response->setStatusCode(200);
    }
}
