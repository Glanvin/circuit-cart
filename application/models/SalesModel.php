<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class SalesModel extends CI_Model {

    private const ONLINE_SHIPPING = 10.00;

    public function __construct() {
        parent::__construct();
    }

    //Builds a shopper-facing quote using current catalog prices and active
    //online tax rates. recordSale recalculates the same values on submission.
    public function quoteOnlineOrder(array $items, string $promoCode = '', ?string $customerEmail = NULL): array {
        $lines = [];
        $subtotal = 0.0;
        foreach ($items as $item) {
            $part = $this->PartsModel->getPartById((int) ($item['part_id'] ?? 0));
            if ((int) ($part['is_active'] ?? 1) !== 1) {
                throw new AppException('D02', ['item' => $part['part_name']]);
            }
            $qty = max(1, min(99, (int) ($item['quantity'] ?? $item['qty'] ?? 1)));
            $lines[] = [
                'part_id' => (int) $part['part_id'],
                'name' => $part['part_name'],
                'category' => $part['category'],
                'image_path' => $part['image_path'] ?? NULL,
                'price' => (float) $part['selling_price'],
                'quantity' => $qty,
                'stock_quantity' => (int) $part['stock_quantity'],
            ];
            $subtotal += (float) $part['selling_price'] * $qty;
        }
        $subtotal = round($subtotal, 2);

        //Promo discount applies to the subtotal before tax. Validation is
        //repeated inside recordSale so a forged code cannot change the total.
        $promo = NULL;
        $discount = 0.0;
        if (trim($promoCode) !== '') {
            $promo = $this->PromoModel->validatePromoCode($promoCode, $subtotal, $customerEmail);
            $discount = (float) $promo['discount'];
        }

        $taxable = round($subtotal - $discount, 2);
        $tax = 0.0;
        foreach ($this->getActiveTaxRates('online') as $rate) {
            $tax += round($taxable * ((float) $rate['rate_percent'] / 100), 2);
        }
        $shipping = self::ONLINE_SHIPPING;
        return [
            'items' => $lines,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'promo_code' => $promo['code'] ?? NULL,
            'tax' => round($tax, 2),
            'shipping' => $shipping,
            'total' => round($taxable + $tax + $shipping, 2),
        ];
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

            if ((int) ($part['is_active'] ?? 1) !== 1) {
                throw new AppException('D02', ['item' => $part['part_name']]);
            }

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

        //Promo codes apply to online orders only. The code is re-validated here
        //(never trusting the client) against the freshly computed subtotal.
        $promo = NULL;
        $discount = 0.0;
        if ($origin === 'online' && !empty($data['promo_code'])) {
            $promo = $this->PromoModel->validatePromoCode(
                (string) $data['promo_code'],
                $subtotal,
                $data['customer_email'] ?? NULL
            );
            $discount = (float) $promo['discount'];
        }

        $taxable = round($subtotal - $discount, 2);

        //Calculate tax from active tax rates
        $tax_rates = $this->getActiveTaxRates($origin);
        $tax_breakdown = [];
        $tax_total = 0.0;

        foreach ($tax_rates as $tax) {
            $amount = round($taxable * ($tax['rate_percent'] / 100), 2);
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

        $total = round($taxable + $tax_total + $shipping, 2);

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
            //Payment must cover the total. Cash may be tendered above the total
            //(the excess becomes change), but no non-cash tender may exceed the
            //total, and the combined amount may not fall short.
            $paid_total = 0.0;
            $non_cash_total = 0.0;
            foreach ($payment_methods as $pm) {
                $amount = (float) $pm['amount'];
                $paid_total += $amount;
                if (($pm['code'] ?? '') !== 'cash') {
                    $non_cash_total += $amount;
                }
            }
            $paid_total = round($paid_total, 2);
            $non_cash_total = round($non_cash_total, 2);

            if ($paid_total + 0.001 < $total || $non_cash_total > $total + 0.001) {
                throw new AppException('D05');
            }
        } else {
            //Save the shopper's selected channel as pending. Card numbers and
            //security codes are never collected or stored by this application.
            $code = (string) ($data['payment_code'] ?? 'cod');
            $method = $this->getPaymentMethodByCode($code);
            if (!$method) {
                throw new AppException('D05');
            }
            $payment_methods = [[
                'method_id' => (int) $method['id'],
                'code' => $code,
                'amount' => $total,
                'reference' => NULL,
                'status' => 'pending',
            ]];
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
            'discount_total' => $discount,
            'promo_code' => $promo['code'] ?? NULL,
            'tax_total' => $tax_total,
            'total' => $total,
            'payment_methods' => json_encode($payment_methods),
            'tax_breakdown' => json_encode($tax_breakdown),
            'history' => json_encode([[
                'action' => 'created',
                'timestamp' => date('Y-m-d H:i:s'),
                'employee_id' => $data['employee_id'] ?? NULL,
                'note' => $origin === 'online' ? 'Online order placed' : 'Store sale completed',
            ]]),
        ];

        $this->db->trans_start();

        try {
            //Insert first so each stock movement can reference this exact sale.
            //Any stock failure rolls the sale row back with the rest of the
            //transaction.
            $this->db->insert('tbl_sales', $sale);
            $sale_id = $this->db->insert_id();

            foreach ($lines as $line) {
                $this->PartsModel->adjustStock(
                    $line['part_id'],
                    -$line['qty'],
                    'sale',
                    'sale',
                    $sale_id,
                    $data['employee_id'] ?? NULL,
                    'Sale ' . $sale['receipt_no']
                );
            }

            //Burn a promo use only when the order actually commits.
            if ($promo !== NULL) {
                $this->PromoModel->incrementUsage((int) $promo['id']);
            }

            $this->db->trans_complete();
        } catch (Exception $e) {
            //adjustStock completes its nested transaction before it reports an
            //error, leaving this outer transaction available to roll back.
            $this->db->trans_rollback();
            throw $e;
        }

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

    //Public accessor so the POS can mirror the server-side tax calculation.
    public function getStoreTaxRates(): array {
        return $this->getActiveTaxRates('store');
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

    //Returns a single store sale by its receipt number.
    public function getSaleByReceipt(string $receipt_no): array {
        $this->db->where('receipt_no', strtoupper(trim($receipt_no)));
        $query = $this->db->get('tbl_sales');

        if (!$query) {
            throw new AppException('Q09');
        }

        if ($query->num_rows() === 0) {
            throw new AppException('P08', ['id' => $receipt_no]);
        }

        $sale = $query->row_array();
        $this->decodeSale($sale);
        return $sale;
    }

    //Recent store sales for the POS lookup/refund panel.
    public function getRecentStoreSales(int $limit = 25): array {
        $this->db->where('origin', 'store');
        $this->db->order_by('created_at', 'DESC');
        $this->db->order_by('id', 'DESC');
        $this->db->limit($limit);
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

    //Reverses a completed store sale and returns it. Restocks the sold lines
    //(unless $restock is FALSE) through tbl_stock_movements and stamps the
    //refund/void audit columns. $mode is 'refund' (money returned) or 'void'
    //(sale cancelled before tender). Refund policy/limits are enforced by the
    //controller, not here.
    public function refundSale(int $sale_id, int $employee_id = NULL, string $reason = '', bool $restock = TRUE, string $mode = 'refund', ?int $refund_authorized_by = NULL): array {
        $sale = $this->getSaleById($sale_id);

        if ($sale['origin'] !== 'store') {
            throw new AppException('D07');
        }

        if (in_array($sale['status'], ['refunded', 'voided', 'cancelled'], TRUE)) {
            throw new AppException('D06');
        }

        $new_status = $mode === 'void' ? 'voided' : 'refunded';
        $items = $sale['items'] ?? [];

        if (empty($items)) {
            throw new AppException('D01');
        }

        $this->db->trans_start();

        if ($restock) {
            foreach ($items as $line) {
                $this->PartsModel->adjustStock(
                    (int) $line['part_id'],
                    (int) $line['qty'],
                    'refund',
                    'sale',
                    $sale_id,
                    $employee_id,
                    ucfirst($new_status) . ' of ' . $sale['receipt_no']
                );
            }
        }

        $history = $sale['history'] ?? [];
        $history[] = [
            'action' => $new_status,
            'timestamp' => date('Y-m-d H:i:s'),
            'employee_id' => $employee_id,
            'refund_authorized_by' => $refund_authorized_by,
            'note' => $reason,
        ];

        $this->db->where('id', $sale_id);
        $this->db->update('tbl_sales', [
            'status' => $new_status,
            'refund_amount' => $sale['total'],
            'refund_reason' => trim($reason) !== '' ? trim($reason) : NULL,
            'refunded_at' => date('Y-m-d H:i:s'),
            'refunded_by' => $employee_id ?: NULL,
            'refund_authorized_by' => $refund_authorized_by ?: NULL,
            'history' => json_encode($history),
        ]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            throw new AppException('Q15');
        }

        return $this->getSaleById($sale_id);
    }

    //Cancels an online order and restocks its lines. $allowedFrom lists the
    //statuses the caller is permitted to cancel from, so a customer can only
    //cancel before dispatch while staff may also cancel shipped orders.
    public function cancelOrder(int $sale_id, int $employee_id = NULL, string $reason = '', array $allowedFrom = ['processing']): array {
        $sale = $this->getSaleById($sale_id);

        if ($sale['origin'] !== 'online') {
            throw new AppException('D08');
        }

        if (in_array($sale['status'], ['cancelled', 'refunded', 'voided', 'delivered'], TRUE)) {
            throw new AppException('D06');
        }

        if (!in_array($sale['status'], $allowedFrom, TRUE)) {
            throw new AppException('D09');
        }

        $items = $sale['items'] ?? [];

        $this->db->trans_start();

        foreach ($items as $line) {
            $this->PartsModel->adjustStock(
                (int) $line['part_id'],
                (int) $line['qty'],
                'return',
                'sale',
                $sale_id,
                $employee_id,
                'Cancelled ' . $sale['receipt_no']
            );
        }

        $history = $sale['history'] ?? [];
        $history[] = [
            'action' => 'cancelled',
            'timestamp' => date('Y-m-d H:i:s'),
            'employee_id' => $employee_id,
            'note' => $reason,
        ];

        $this->db->where('id', $sale_id);
        $this->db->update('tbl_sales', [
            'status' => 'cancelled',
            'history' => json_encode($history),
        ]);

        $this->db->trans_complete();

        if ($this->db->trans_status() === FALSE) {
            throw new AppException('Q08');
        }

        return $this->getSaleById($sale_id);
    }

    //Decodes JSON fields in sale array.
    private function decodeSale(array &$sale): void {
        foreach (['items', 'payment_methods', 'tax_breakdown', 'history'] as $field) {
            if (is_array($sale[$field] ?? NULL)) {
                continue;
            }

            if (is_string($sale[$field] ?? NULL)) {
                $decoded = json_decode($sale[$field], true);
                $sale[$field] = is_array($decoded) ? $decoded : [];
            } else {
                $sale[$field] = [];
            }
        }
    }

    //Unique human-readable receipt number, e.g. ST-20260924-1F8A9 or ON-20260924-7C2D3.
    private function generateReceipt(string $origin): string {
        $prefix = $origin === 'store' ? 'ST' : 'ON';
        return $prefix . '-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(3)), 0, 5));
    }
}
