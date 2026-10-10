<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SalesModel extends CI_Model {

    private const ONLINE_SHIPPING = 10.00;

    public function __construct() {
        parent::__construct();
    }

    //Records a sale inside a DB transaction and decreases stock for each line.
    //$data: origin, sold_by, employee_id, customer_email, customer_address, items ([part_id, qty]), payment_methods (optional)
    public function recordSale(array $data) {
        if (empty($data) || empty($data['items']) || !is_array($data['items'])) {
            throw new AppException('D01');
        }

        $origin = $data['origin'] === 'online' ? 'online' : 'store';

        $lines = [];
        $subtotal = 0.0;

        foreach ($data['items'] as $line) {
            $part_id = (int) ($line['part_id'] ?? 0);
            $qty = (int) ($line['qty'] ?? 0);

            if ($part_id <= 0 || $qty <= 0) {
                throw new AppException('D01');
            }

            $part = $this->PartsModel->getPartById($part_id);

            if ((int) $part['stock_quantity'] < $qty) {
                throw new AppException('D02', ['item' => $part['part_name']]);
            }

            $lines[] = [
                'part_id' => (int) $part['part_id'],
                'name' => $part['part_name'],
                'qty' => $qty,
                'price' => (float) $part['selling_price'],
            ];

            $subtotal += (float) $part['selling_price'] * $qty;
        }

        $subtotal = round($subtotal, 2);

        //Calculate tax from active tax rates
        $tax_rates = $this->getActiveTaxRates($origin);
        $tax_breakdown = [];
        $tax_total = 0.0;

        foreach ($tax_rates as $tax) {
            $amount = round($subtotal * ($tax['rate_percent'] / 100), 2);
            if ($amount > 0) {
                $tax_breakdown[] = [
                    'tax_id' => $tax['id'],
                    'name' => $tax['name'],
                    'rate' => (float) $tax['rate_percent'],
                    'amount' => $amount,
                ];
                $tax_total += $amount;
            }
        }

        $tax_total = round($tax_total, 2);

        //Shipping for online
        $shipping = $origin === 'online' ? self::ONLINE_SHIPPING : 0.0;

        $total = round($subtotal + $tax_total + $shipping, 2);

        //Handle payment methods
        $payment_methods = $data['payment_methods'] ?? [];
        if ($origin === 'store') {
            if (empty($payment_methods)) {
                //Default to cash for backwards compatibility
                $cash_method = $this->getPaymentMethodByCode('cash');
                if ($cash_method) {
                    $payment_methods = [[
                        'method_id' => $cash_method['id'],
                        'code' => 'cash',
                        'amount' => $total,
                        'reference' => NULL,
                    ]];
                }
            }
            //Validate payment methods total matches sale total
            $paid_total = 0.0;
            foreach ($payment_methods as $pm) {
                $paid_total += (float) $pm['amount'];
            }
            $paid_total = round($paid_total, 2);
            if (abs($paid_total - $total) > 0.01) {
                throw new AppException('D05', ['msg' => 'Payment total does not match sale total']);
            }
        } else {
            //Online: no payment methods at sale time (handled by payment provider)
            $payment_methods = [];
        }

        $sale = [
            'receipt_no' => $this->generateReceipt($origin),
            'origin' => $origin,
            'status' => $origin === 'online' ? 'processing' : 'completed',
            'sold_by' => $data['sold_by'] ?? NULL,
            'employee_id' => $data['employee_id'] ?? NULL,
            'customer_email' => $data['customer_email'] ?? NULL,
            'customer_address' => $data['customer_address'] ?? NULL,
            'items' => json_encode($lines),
            'subtotal' => $subtotal,
            'tax_total' => $tax_total,
            'total' => $total,
            'payment_methods' => json_encode($payment_methods),
            'tax_breakdown' => json_encode($tax_breakdown),
        ];

        $this->db->trans_start();

        foreach ($lines as $line) {
            //Use PartsModel::adjustStock to record movement
            $this->PartsModel->adjustStock(
                $line['part_id'],
                -$line['qty'],
                'sale',
                'sale',
                NULL, //sale ID not known yet, will be set after insert
                $data['employee_id'] ?? NULL,
                'Sale ' . $sale['receipt_no']
            );
        }

        $this->db->insert('tbl_sales', $sale);
        $sale_id = $this->db->insert_id();

        //Update stock movements with the sale ID
        foreach ($lines as $line) {
            $this->db->where('part_id', $line['part_id']);
            $this->db->where('reason', 'sale');
            $this->db->where('reference_id IS NULL');
            $this->db->update('tbl_stock_movements', ['reference_id' => $sale_id]);
        }

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            throw new AppException('Q08');
        }

        $sale['total'] = $total;
        $sale['subtotal'] = $subtotal;
        $sale['tax_total'] = $tax_total;
        $sale['payment_methods'] = $payment_methods;
        $sale['tax_breakdown'] = $tax_breakdown;

        return $sale;
    }

    //Gets active tax rates for a given origin.
    private function getActiveTaxRates(string $origin): array {
        $this->db->where('is_active', 1);
        $this->db->where('applies_to IN', "('{$origin}','both')", FALSE, FALSE);
        $this->db->order_by('id', 'ASC');
        $query = $this->db->get('tbl_tax_rates');
        if (!$query) {
            throw new AppException('Q12');
        }
        return $query->result_array();
    }

    //Gets a payment method by code.
    public function getPaymentMethodByCode(string $code): ?array {
        $this->db->where('code', $code);
        $this->db->where('is_active', 1);
        $query = $this->db->get('tbl_payment_methods');
        if (!$query) {
            throw new AppException('Q13');
        }
        return $query->num_rows() > 0 ? $query->row_array() : NULL;
    }

    //Gets all active payment methods.
    public function getActivePaymentMethods(): array {
        $this->db->where('is_active', 1);
        $this->db->order_by('sort_order', 'ASC');
        $query = $this->db->get('tbl_payment_methods');
        if (!$query) {
            throw new AppException('Q13');
        }
        return $query->result_array();
    }

    public function getSaleById($id) {
        $this->db->where('id', $id);
        $query = $this->db->get('tbl_sales');

        if (!$query) {
            throw new AppException('Q09');
        }

        if ($query->num_rows() === 0) {
            throw new AppException('P08', ['id' => $id]);
        }

        $sale = $query->row_array();
        $this->decodeSale($sale);
        return $sale;
    }

    //Total sales grouped by day for a given origin.
    public function getDayTotals(string $from, string $origin, $status = NULL) {
        $this->db->select('DATE(created_at) AS day, SUM(total) AS total');
        $this->db->where('created_at >=', $from);
        $this->db->where('origin', $origin);

        if ($status !== NULL) {
            $this->db->where('status', $status);
        }

        $this->db->group_by('day');
        $query = $this->db->get('tbl_sales');

        if (!$query) {
            throw new AppException('Q09');
        }

        $rows = [];

        foreach ($query->result_array() as $row) {
            $rows[$row['day']] = (float) $row['total'];
        }

        return $rows;
    }

    //Online orders for the fulfilment panel.
    public function getOnlineSales($status = NULL, $sort = 'newest', $q = '') {
        $this->db->where('origin', 'online');

        if (!empty($status)) {
            $this->db->where('status', $status);
        }

        if (trim($q) !== '') {
            $this->db->group_start();
            $this->db->like('receipt_no', $q);
            $this->db->or_like('customer_email', $q);
            $this->db->or_like('customer_address', $q);
            $this->db->or_like('sold_by', $q);
            $this->db->group_end();
        }

        switch ($sort) {
            case 'oldest':
                $this->db->order_by('created_at', 'ASC');
                $this->db->order_by('id', 'ASC');
                break;
            case 'total_high':
                $this->db->order_by('total', 'DESC');
                $this->db->order_by('created_at', 'DESC');
                break;
            case 'total_low':
                $this->db->order_by('total', 'ASC');
                $this->db->order_by('created_at', 'ASC');
                break;
            default:
                $this->db->order_by('created_at', 'DESC');
                $this->db->order_by('id', 'DESC');
        }

        $query = $this->db->get('tbl_sales');

        if (!$query) {
            throw new AppException('Q09');
        }

        $sales = $query->result_array();
        foreach ($sales as &$sale) {
            $this->decodeSale($sale);
        }
        return $sales;
    }

    public function updateStatus($id, $status) {
        $this->db->where('id', $id);
        return $this->db->update('tbl_sales', ['status' => $status]);
    }

    //Returns online orders for a specific customer email, newest first.
    public function getCustomerOrders($customer_email) {
        if (empty($customer_email)) {
            throw new AppException('D01');
        }

        $this->db->where('origin', 'online');
        $this->db->where('customer_email', strtolower($customer_email));
        $this->db->order_by('created_at', 'DESC');
        $this->db->order_by('id', 'DESC');
        $query = $this->db->get('tbl_sales');

        if (!$query) {
            throw new AppException('Q09');
        }

        $orders = $query->result_array();
        foreach ($orders as &$order) {
            $this->decodeSale($order);
        }
        return $orders;
    }

    //Decodes JSON fields in sale array.
    private function decodeSale(array &$sale): void {
        foreach (['items', 'payment_methods', 'tax_breakdown'] as $field) {
            if (isset($sale[$field])) {
                $sale[$field] = json_decode($sale[$field], true) ?? [];
            }
        }
    }

    //Unique human-readable receipt number, e.g. ST-20260924-1F8A9 or ON-20260924-7C2D3.
    private function generateReceipt(string $origin): string {
        $prefix = $origin === 'store' ? 'ST' : 'ON';
        return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }
}